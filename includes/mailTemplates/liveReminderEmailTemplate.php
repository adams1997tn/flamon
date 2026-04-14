<?php
$bodyLiveReminderEmail = <<<STARTEMAIL
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="format-detection" content="date=no" />
  <meta name="format-detection" content="address=no" />
  <meta name="format-detection" content="telephone=no" />
  <title>Email Template</title>
  <style type="text/css">
    body { margin:0; padding:0; background:#f3f4f6; width:100%; }
    table { border-collapse:collapse; }
    .container { width:100%; max-width:640px; margin:0 auto; padding:40px 0; }
    .card { background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 18px 30px rgba(15,23,42,.08); }
    .header { padding:20px 24px; border-bottom:1px solid #e5e7eb; }
    .content { padding:24px; text-align:left; }
    .title { font-size:24px; font-weight:700; color:#0f172a; margin:0 0 10px 0; }
    .text { font-size:14px; line-height:22px; color:#475569; margin:0 0 16px 0; }
    .meta { font-size:12px; color:#94a3b8; margin:0 0 18px 0; }
    .btn { display:inline-block; padding:10px 18px; background:#0f172a; color:#ffffff; border-radius:999px; text-decoration:none; font-size:12px; font-weight:700; letter-spacing:.3px; }
    .footer { padding:16px 24px; font-size:11px; color:#94a3b8; text-align:center; }
  </style>
</head>
<body>
  <table width="100%" bgcolor="#f3f4f6">
    <tr>
      <td align="center">
        <table class="container" width="100%">
          <tr>
            <td>
              <table class="card" width="100%">
                <tr>
                  <td class="header">
                    <table width="100%">
                      <tr>
                        <td><img src="$siteLogoUrl" width="40" height="40" alt="Logo" /></td>
                        <td align="right" style="font-size:12px;color:#64748b;">$siteName</td>
                      </tr>
                    </table>
                  </td>
                </tr>
                <tr>
                  <td class="content">
                    <h1 class="title">$reminderTitle</h1>
                    <p class="text">$reminderText</p>
                    <p class="meta">$reminderTimeLabel: $reminderTime</p>
                    <a href="$reminderUrl" class="btn">$reminderButton</a>
                  </td>
                </tr>
                <tr>
                  <td class="footer">$siteName</td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
STARTEMAIL;
