<?php 
if($logedIn == 0){
    header('Location: ' . route_url('404'));
}else{
    $metaRobots = 'noindex, nofollow';
    include("themes/$currentTheme/settings.php");   
} 
?>
