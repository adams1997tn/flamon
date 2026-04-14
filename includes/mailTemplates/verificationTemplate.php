<?php
$verifyEmailTitle = $LANG['verify_your_email'];
$verifyEmailIntro = $LANG['confirm_email'];
$verifyEmailButton = $LANG['verify_your_email'];

$bodyVerifyEmail = <<<STARTEMAIL
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="x-apple-disable-message-reformatting">
    <title>{$verifyEmailTitle}</title>
</head>
<body>
    <table width="100%" cellpadding="0" cellspacing="0" border="0" bgcolor="#f1f1f1">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" border="0" bgcolor="#ffffff">
                    <tr>
                        <td align="center">
                            <table width="100%" cellpadding="24" cellspacing="0" border="0">
                                <tr>
                                    <td align="left" valign="middle">
                                        <img src="{$siteLogoUrl}" alt="{$siteName}" width="48" height="48" border="0">
                                    </td>
                                    <td align="right" valign="middle">
                                        <font face="Arial, sans-serif" size="2" color="#666666">{$siteName}</font>
                                    </td>
                                </tr>
                            </table>
                            <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" bgcolor="#f7f7f7">
                                        <table width="100%" cellpadding="30" cellspacing="0" border="0">
                                            <tr>
                                                <td align="center">
                                                    <font face="Arial, sans-serif" size="5" color="#111111"><b>{$verifyEmailTitle}</b></font>
                                                    <br>
                                                    <font face="Arial, sans-serif" size="3" color="#666666">{$verifyEmailIntro}</font>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center">
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td height="28"></td>
                                            </tr>
                                        </table>
                                        <table cellpadding="12" cellspacing="0" border="0">
                                            <tr>
                                                <td align="center" bgcolor="#30e3ca">
                                                    <a href="{$theCode}" target="_blank">
                                                        <font face="Arial, sans-serif" size="3" color="#ffffff"><b>{$verifyEmailButton}</b></font>
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                        <table width="100%" cellpadding="0" cellspacing="0" border="0">
                                            <tr>
                                                <td height="34"></td>
                                            </tr>
                                        </table>
                                    </td>
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
?>
