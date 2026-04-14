<?php
if (!isset($agencyModuleStatus) || $agencyModuleStatus !== 'yes') {
    header('Location: ' . route_url('404'));
    exit;
}
$page = 'agencies';
include "themes/$currentTheme/layouts/agencies.php";
