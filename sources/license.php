<?php
$currentThemePath = "themes/$currentTheme/license.php";
if (file_exists($currentThemePath)) {
    include $currentThemePath;
} else {
    echo "License activation required.";
}
