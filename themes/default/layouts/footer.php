<?php
if ($getPages) {
    foreach ($getPages as $footerPage) {
        $pageName = $footerPage['page_name'];
        $pageTitle = $footerPage['page_title'];
        $page_Name = isset($LANG[$pageName]) ? $LANG[$pageName] : $pageTitle;
        echo '<div class="footer_menu_item"><a href="' . $base_url . $pageName . '">' . $page_Name . '</a></div>';
    }
    if (!isset($blogFeatureStatus) || (string)$blogFeatureStatus === '1') {
        echo '<div class="footer_menu_item"><a href="' . route_url('blog') . '">' . iN_HelpSecure($LANG['blog'] ?? 'Blog') . '</a></div>';
    }
    echo '<div class="footer_menu_item">' . $siteName . ' © ' . date("Y") . '</div>';
}
?>
