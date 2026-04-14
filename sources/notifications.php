<?php  
if($logedIn == 0){
    header('Location: ' . route_url('404'));
}else{
    $metaRobots = 'noindex, nofollow';
    $checkPageExist = $iN->iN_CheckpageExist($urlMatch);
    include("themes/$currentTheme/contents.php");  
}
?>
