<?php
if (!isset($agencyModuleStatus) || $agencyModuleStatus !== 'yes') {
    header('Location: ' . route_url('404'));
    exit;
}
$agencyIdValue = isset($agencyId) ? (int)$agencyId : 0;
$page = 'agency';
include("themes/$currentTheme/layouts/agency.php");
?>
