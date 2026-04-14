(function() {
    "use strict";

    var storageKey = "dizzy_age_confirmed";

    var onReady = function() {
        var modal = document.getElementById("ageConfirmModal");
        if (!modal) {
            return;
        }

        try {
            if (window.localStorage && localStorage.getItem(storageKey) === "1") {
                return;
            }
        } catch (e) {
            // localStorage blocked; keep showing modal per request.
        }

        modal.classList.add("is-visible");
        modal.setAttribute("aria-hidden", "false");
        document.body.classList.add("age-confirm-open");

        var yesBtn = modal.querySelector(".age_confirm_yes");
        var noBtn = modal.querySelector(".age_confirm_no");

        if (yesBtn) {
            yesBtn.addEventListener("click", function() {
                try {
                    if (window.localStorage) {
                        localStorage.setItem(storageKey, "1");
                    }
                } catch (e) {
                    // ignore storage errors
                }
                modal.classList.remove("is-visible");
                modal.setAttribute("aria-hidden", "true");
                document.body.classList.remove("age-confirm-open");
            });
        }

        if (noBtn) {
            noBtn.addEventListener("click", function() {
                window.location.href = "https://www.google.com";
            });
        }
    };

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", onReady);
    } else {
        onReady();
    }
})();
