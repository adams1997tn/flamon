<?php
if (!isset($iN)) {
    include_once __DIR__ . '/../includes/inc.php';
}

if ($logedIn != 1) {
    header('Location: ' . route_url(''));
    exit;
}

$ageVerifFlash = $_SESSION['ageverif_flash'] ?? null;
if (isset($_SESSION['ageverif_flash'])) {
    unset($_SESSION['ageverif_flash']);
}
$ageVerifRequiredNotice = $ageVerificationForceSitewide === '1' && (string)$userAgeVerifyStatus !== '1';

include "themes/$currentTheme/age_verification.php";
?>
