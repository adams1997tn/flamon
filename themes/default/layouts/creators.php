<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo iN_HelpSecure($siteTitle);?></title>
    <?php
       include("header/meta.php");
       include("header/css.php");
       include("header/javascripts.php");
    ?>
</head>
<body>
<?php if($logedIn == 0){ include('login_form.php'); }?>
<?php include("header/header.php");?>
    <div class="wrapper creators_wrapper">
           <div class="creators_hero">
                <div class="creators_hero_kicker">
                    <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('95'));?>
                    <span><?php echo iN_HelpSecure($LANG['our_creators']);?></span>
                </div>
                <div class="creators_hero_content">
                    <div class="creators_hero_text">
                        <h1 class="creators_hero_title"><?php echo iN_HelpSecure($LANG['best_creators_of_last_week']);?></h1>
                        <p class="creators_hero_subtitle">
                            <?php echo iN_HelpSecure($LANG['landing_two_creators_subtitle'] ?? ($LANG['l_join_our_community_now_and_start_growing_users'] ?? ''));?>
                        </p>
                    </div>
                </div>
           </div>
           <?php
           include("creators/creators_menu.php");
           $checkCreatorTypeExist = null;
           if($pageCreator){
               if(isset($PROFILE_CATEGORIES[$iN->iN_Secure($pageCreator)])){
                   $checkCreatorTypeExist = $PROFILE_CATEGORIES[$iN->iN_Secure($pageCreator)];
               }else if(isset($PROFILE_SUBCATEGORIES[$iN->iN_Secure($pageCreator)])){
                    $checkCreatorTypeExist = $PROFILE_SUBCATEGORIES[$iN->iN_Secure($pageCreator)];
                }
            }
            if($pageCreator && $checkCreatorTypeExist){
                include("creators/creatorsFromType.php");
            }else{
                include("creators/featuredCreators.php");
           }
          // Fallback list in same layout style
           include("creators/suggestedCreators.php");
           ?>
    </div>
    <script>
    (function () {
        const closeAll = () => {
            document.querySelectorAll('.creator_stats_more').forEach(el => {
                el.classList.remove('open');
                const btn = el.querySelector('.creator_stats_more_trigger');
                if (btn) { btn.setAttribute('aria-expanded', 'false'); }
            });
        };
        document.addEventListener('click', function (e) {
            const trigger = e.target.closest('.creator_stats_more_trigger');
            if (trigger) {
                const wrap = trigger.closest('.creator_stats_more');
                const isOpen = wrap && wrap.classList.contains('open');
                closeAll();
                if (wrap && !isOpen) {
                    wrap.classList.add('open');
                    trigger.setAttribute('aria-expanded', 'true');
                }
                e.stopPropagation();
                e.preventDefault();
                return;
            }
            if (e.target.closest('.creator_stats_dropdown')) {
                return;
            }
            closeAll();
        });
        document.addEventListener('keyup', function (e) {
            if (e.key === 'Escape') { closeAll(); }
        });
    })();
    (function () {
        const shell = document.querySelector('.creators_menu_shell');
        const list = document.getElementById('creatorsMenuList');
        const more = document.getElementById('creatorsMenuMore');
        const dropdown = document.getElementById('creatorsMenuMoreDropdown');
        if (!shell || !list || !more || !dropdown) { return; }
        const moreBtn = more.querySelector('.creator_menu_more_btn');
        const allItems = Array.from(list.querySelectorAll('[data-menu-item]'));
            const closeMore = () => {
                more.classList.remove('open');
                if (moreBtn) { moreBtn.setAttribute('aria-expanded', 'false'); }
            };
            const openMore = () => {
                more.classList.add('open');
                if (moreBtn) { moreBtn.setAttribute('aria-expanded', 'true'); }
            };
            const balanceMenu = () => {
                dropdown.innerHTML = '';
                allItems.forEach(item => {
                    item.style.display = '';
                    if (item.parentElement !== list) {
                        list.appendChild(item);
                    }
                });
                // measure with more button visible
                more.style.display = 'flex';
                const moreWidth = more.offsetWidth || 48;
                const gap = 8;
                let available = shell.clientWidth - moreWidth - gap;
                if (available < 0) { available = 0; }
                let used = 0;
                const items = Array.from(list.querySelectorAll('[data-menu-item]'));
                let visibleCount = 0;
            items.forEach(item => {
                const itemWidth = item.offsetWidth || 0;
                if ((used + itemWidth) > available && visibleCount >= 1) {
                    const clone = item.cloneNode(true);
                    const sub = clone.querySelector('.subcategoryname');
                    const caret = clone.querySelector('.creator_item_caret');
                    if (caret) { caret.style.display = 'inline-flex'; }
                    dropdown.appendChild(clone);
                    item.style.display = 'none';
                } else {
                    used += itemWidth + gap;
                    visibleCount += 1;
                }
            });
            // ensure dropdown hosts at least two items to give breathing room near the ellipsis
            if (dropdown.children.length < 2 && items.length > 2) {
                let visibleItems = items.filter(it => it.style.display !== 'none');
                while (dropdown.children.length < 2 && visibleItems.length > 1) {
                    const toMove = visibleItems.pop();
                    const clone = toMove.cloneNode(true);
                    const sub = clone.querySelector('.subcategoryname');
                    const caret = clone.querySelector('.creator_item_caret');
                    if (caret) { caret.style.display = 'inline-flex'; }
                    dropdown.appendChild(clone);
                    toMove.style.display = 'none';
                }
            }
            if (!dropdown.children.length) {
                more.style.display = 'none';
            }
            closeMore();
        };
        let resizeTimer;
        window.addEventListener('resize', function () {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(balanceMenu, 120);
        });
        document.addEventListener('click', function (e) {
            if (e.target.closest('#creatorsMenuMore .creator_menu_more_btn')) {
                const isOpen = more.classList.contains('open');
                closeMore();
                if (!isOpen) { openMore(); }
                e.preventDefault();
                e.stopPropagation();
                return;
            }
            if (e.target.closest('.creator_menu_more_dropdown')) {
                return;
            }
            closeMore();
        });
        document.addEventListener('keyup', function (e) {
            if (e.key === 'Escape') { closeMore(); }
        });
        balanceMenu();
    })();
    </script>
</body>
</html>
