#!/usr/bin/env python3
"""
deploy.py — One-shot deploy: git push to GitHub + rsync changed files to the
production server via PuTTY (plink/pscp), excluding sensitive files.

Behaviour mirrors the manual flow:
  1. Reads current git status to discover modified / added / deleted files
     since the last push (or against origin/<branch>).
  2. Skips files listed in EXCLUDE (e.g. includes/connect.php — DB creds).
  3. git add -A (excluding ignored), commit, push to origin/<branch>.
  4. pscp uploads each modified/added file to the server, recreating any
     missing parent directories.
  5. Removes deleted files on the server.
  6. Fixes ownership / permissions, then reloads php-fpm (opcache clear).

Usage:
    python deploy.py                       # uses defaults below
    python deploy.py -m "Commit message"   # custom commit message
    python deploy.py --no-push             # only deploy to server
    python deploy.py --no-deploy           # only push to GitHub
    python deploy.py --dry-run             # show what would happen

Requires: git in PATH, PuTTY suite (plink.exe + pscp.exe) in PATH.
"""

from __future__ import annotations

import argparse
import os
import shutil
import subprocess
import sys
from dataclasses import dataclass
from pathlib import Path
from typing import Iterable, List, Tuple

# ----------------------- CONFIG (edit me) ----------------------------------- #
SSH_HOST       = "148.230.116.74"
SSH_USER       = "root"
SSH_PASSWORD   = "aduadu123Ms@@"
REMOTE_ROOT    = "/www/wwwroot/flamonfans.com"
GIT_REMOTE     = "origin"
GIT_BRANCH     = "main"
PHP_FPM_INIT   = "/etc/init.d/php-fpm-83"  # set to "" to skip reload
WWW_USER       = "www"
WWW_GROUP      = "www"

# Files / dirs that must NEVER be uploaded (sensitive credentials, etc.).
EXCLUDE: set[str] = {
    "includes/connect.php",
    ".env",
    ".env.local",
    ".env.production",
}

# Path-prefixes that should never be touched on the server even if changed.
EXCLUDE_PREFIXES: tuple[str, ...] = (
    "tmp/",
    "uploads/",
    "img/dayimages/",
    "includes/logs/",
    ".git/",
    ".vscode/",
    "node_modules/",
    "memories/",
    "__pycache__/",
)
# ---------------------------------------------------------------------------- #


# ============================== Helpers ===================================== #
def run(cmd: list[str] | str, *, capture: bool = True, check: bool = True,
        cwd: str | None = None) -> subprocess.CompletedProcess:
    """Run a command. If `cmd` is a string, runs through the shell."""
    shell = isinstance(cmd, str)
    return subprocess.run(
        cmd, cwd=cwd, shell=shell, check=check,
        capture_output=capture, text=True, encoding="utf-8", errors="replace",
    )


def ensure_tools() -> None:
    missing = [t for t in ("git", "plink", "pscp") if shutil.which(t) is None]
    if missing:
        sys.exit(f"[!] Missing required tools in PATH: {', '.join(missing)}\n"
                 f"    Install Git for Windows + PuTTY suite (plink, pscp).")


def git_repo_root() -> Path:
    r = run(["git", "rev-parse", "--show-toplevel"])
    return Path(r.stdout.strip())


def is_excluded(rel: str) -> bool:
    rel = rel.replace("\\", "/")
    if rel in EXCLUDE:
        return True
    for pref in EXCLUDE_PREFIXES:
        if rel.startswith(pref):
            return True
    return False


# ============================== Git ========================================= #
@dataclass
class GitChanges:
    modified: List[str]   # to upload (M, A, R-target, copy)
    deleted: List[str]    # to remove on server


def git_changed_since_origin(branch: str) -> GitChanges:
    """Files in HEAD that differ from origin/<branch> + any uncommitted ones."""
    # Fetch latest origin to get accurate diff base.
    run(["git", "fetch", GIT_REMOTE, branch], check=False)

    base = f"{GIT_REMOTE}/{branch}"
    try:
        run(["git", "rev-parse", "--verify", base])
        diff_range = base
    except subprocess.CalledProcessError:
        diff_range = ""  # remote branch missing — fall back to full snapshot

    modified: list[str] = []
    deleted: list[str] = []

    # Committed but unpushed changes vs origin.
    if diff_range:
        r = run(["git", "diff", "--name-status", diff_range, "HEAD"], check=False)
        for line in r.stdout.splitlines():
            if not line.strip():
                continue
            parts = line.split("\t")
            status = parts[0]
            path = parts[-1]
            if status.startswith("D"):
                deleted.append(path)
            else:
                modified.append(path)

    # Uncommitted (working tree) changes — included so a deploy after a manual
    # edit still works.
    r = run(["git", "status", "--porcelain"], check=False)
    for line in r.stdout.splitlines():
        if len(line) < 4:
            continue
        x, y, path = line[0], line[1], line[3:].strip()
        # Handle renames "old -> new"
        if " -> " in path:
            path = path.split(" -> ", 1)[1]
        path = path.strip().strip('"')
        if x == "D" or y == "D":
            deleted.append(path)
        elif x == "?" or x in "MARC" or y in "MARC":
            modified.append(path)

    # Dedup, preserving order; drop excluded.
    def _dedup(seq: Iterable[str]) -> list[str]:
        seen, out = set(), []
        for p in seq:
            if p in seen or is_excluded(p):
                continue
            seen.add(p)
            out.append(p)
        return out

    return GitChanges(modified=_dedup(modified), deleted=_dedup(deleted))


def git_commit_and_push(message: str, branch: str, dry_run: bool) -> None:
    # Stage everything except excluded paths.
    pathspecs = ["-A"]
    for ex in EXCLUDE:
        pathspecs.append(f":(exclude){ex}")
    for ex in EXCLUDE_PREFIXES:
        pathspecs.append(f":(exclude){ex.rstrip('/')}")

    print(f"[git] git add {' '.join(pathspecs)}")
    if not dry_run:
        # --ignore-errors so paths that are also in .gitignore (e.g. tmp/,
        # uploads/, includes/connect.php) don't cause the whole stage to fail.
        run(["git", "add", "--ignore-errors"] + pathspecs, check=False)

    # Anything staged?
    r = run(["git", "diff", "--cached", "--name-only"], check=False)
    staged = [p for p in r.stdout.splitlines() if p.strip()]
    if not staged:
        print("[git] Nothing to commit.")
    else:
        print(f"[git] Committing {len(staged)} staged file(s).")
        if not dry_run:
            run(["git", "commit", "-m", message])

    # Push (push will be a no-op if there's nothing new).
    print(f"[git] Pushing to {GIT_REMOTE}/{branch}…")
    if not dry_run:
        r = subprocess.run(["git", "push", GIT_REMOTE, branch],
                           capture_output=True, text=True, encoding="utf-8",
                           errors="replace")
        # Show the meaningful tail (push sends progress to stderr).
        out = (r.stderr or "") + (r.stdout or "")
        for line in out.splitlines()[-12:]:
            print(f"      {line}")
        if r.returncode != 0:
            sys.exit(f"[!] git push failed (exit {r.returncode}).")


# ============================== SSH / SCP =================================== #
def plink(remote_cmd: str, *, check: bool = True) -> str:
    cmd = ["plink", "-ssh", "-batch", "-pw", SSH_PASSWORD,
           f"{SSH_USER}@{SSH_HOST}", remote_cmd]
    r = subprocess.run(cmd, capture_output=True, text=True,
                       encoding="utf-8", errors="replace")
    if check and r.returncode != 0:
        raise RuntimeError(f"plink failed:\n{r.stdout}\n{r.stderr}")
    return (r.stdout or "") + (r.stderr or "")


def pscp_upload(local: Path, remote_path: str) -> Tuple[bool, str]:
    cmd = ["pscp", "-batch", "-pw", SSH_PASSWORD, str(local),
           f"{SSH_USER}@{SSH_HOST}:{remote_path}"]
    r = subprocess.run(cmd, capture_output=True, text=True,
                       encoding="utf-8", errors="replace")
    msg = (r.stdout + r.stderr).strip()
    return r.returncode == 0, msg


def deploy_files(repo: Path, changes: GitChanges, dry_run: bool) -> None:
    if not (changes.modified or changes.deleted):
        print("[ssh] No file changes to deploy.")
        return

    # 1) Pre-create all required remote parent directories in one shot.
    parents = sorted({
        os.path.dirname(p).replace("\\", "/")
        for p in changes.modified
        if os.path.dirname(p)
    })
    if parents:
        mkdir_cmd = " && ".join(
            f"mkdir -p {REMOTE_ROOT}/{d}" for d in parents
        )
        print(f"[ssh] Pre-creating {len(parents)} remote dir(s)…")
        if not dry_run:
            plink(mkdir_cmd)

    # 2) Upload each modified/added file.
    failed: list[str] = []
    for rel in changes.modified:
        local = repo / rel
        if not local.exists():
            print(f"[skip] Missing locally: {rel}")
            continue
        remote = f"{REMOTE_ROOT}/{rel}".replace("\\", "/")
        print(f"[scp ] {rel}  ->  {remote}")
        if dry_run:
            continue
        ok, msg = pscp_upload(local, remote)
        if not ok:
            failed.append(rel)
            print(f"        [!] FAILED: {msg}")

    # 3) Remove deleted files on the server.
    for rel in changes.deleted:
        if is_excluded(rel):
            continue
        remote = f"{REMOTE_ROOT}/{rel}".replace("\\", "/")
        print(f"[rm  ] {remote}")
        if dry_run:
            continue
        plink(f"rm -f -- '{remote}'", check=False)

    # 4) Fix ownership / permissions on touched paths and reload php-fpm.
    if not dry_run and changes.modified:
        touched_top = sorted({rel.split("/", 1)[0] for rel in changes.modified})
        chown_targets = " ".join(
            f"{REMOTE_ROOT}/{t}" for t in touched_top
            if (repo / t).exists() or t  # always include
        )
        cmds = [
            f"chown -R {WWW_USER}:{WWW_GROUP} {chown_targets}",
            f"find {chown_targets} -type f \\( -name '*.php' -o -name '*.css' "
            f"-o -name '*.js' -o -name '*.html' -o -name '*.svg' \\) "
            f"-exec chmod 644 {{}} +",
        ]
        if PHP_FPM_INIT:
            cmds.append(f"{PHP_FPM_INIT} reload 2>&1 | tail -3")
        cmds.append("echo DEPLOY_DONE")
        print("[ssh] Fixing perms + reloading php-fpm…")
        out = plink(" && ".join(cmds), check=False)
        for line in out.strip().splitlines()[-8:]:
            print(f"      {line}")

    if failed:
        sys.exit(f"[!] {len(failed)} upload(s) failed: {failed}")


# ============================== Main ======================================== #
def main() -> None:
    ap = argparse.ArgumentParser(description=__doc__,
                                 formatter_class=argparse.RawDescriptionHelpFormatter)
    ap.add_argument("-m", "--message", default="Deploy: automated push",
                    help="Commit message if there is uncommitted work.")
    ap.add_argument("--branch", default=GIT_BRANCH, help="Git branch to push.")
    ap.add_argument("--no-push", action="store_true",
                    help="Skip git commit/push, only deploy to server.")
    ap.add_argument("--no-deploy", action="store_true",
                    help="Skip server deploy, only push to GitHub.")
    ap.add_argument("--dry-run", action="store_true",
                    help="Print actions without executing.")
    args = ap.parse_args()

    ensure_tools()
    repo = git_repo_root()
    os.chdir(repo)
    print(f"[i] Repo root: {repo}")

    # Discover changes BEFORE pushing so the deploy set matches what we push.
    changes = git_changed_since_origin(args.branch)
    print(f"[i] Modified/added: {len(changes.modified)}  Deleted: {len(changes.deleted)}")
    for p in changes.modified:
        print(f"      M  {p}")
    for p in changes.deleted:
        print(f"      D  {p}")

    if not args.no_push:
        git_commit_and_push(args.message, args.branch, args.dry_run)

    if not args.no_deploy:
        deploy_files(repo, changes, args.dry_run)

    print("[✓] Done.")


if __name__ == "__main__":
    try:
        main()
    except KeyboardInterrupt:
        sys.exit("\n[!] Interrupted.")
    except subprocess.CalledProcessError as e:
        msg = (e.stderr or e.stdout or str(e)).strip()
        sys.exit(f"[!] Command failed: {msg}")
