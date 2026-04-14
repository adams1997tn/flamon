<div class="age_confirm_overlay" id="ageConfirmModal" aria-hidden="true">
    <div class="age_confirm_card" role="dialog" aria-modal="true" aria-labelledby="ageConfirmTitle">
        <div class="age_confirm_title" id="ageConfirmTitle">
            <?php echo iN_HelpSecure($LANG['age_confirm_title']); ?>
        </div>
        <div class="age_confirm_desc">
            <?php echo iN_HelpSecure($LANG['age_confirm_desc']); ?>
        </div>
        <ul class="age_confirm_list">
            <li><?php echo iN_HelpSecure($LANG['age_confirm_bullet_one']); ?></li>
            <li><?php echo iN_HelpSecure($LANG['age_confirm_bullet_two']); ?></li>
            <li><?php echo iN_HelpSecure($LANG['age_confirm_bullet_three']); ?></li>
        </ul>
        <div class="age_confirm_actions">
            <button type="button" class="age_confirm_btn age_confirm_yes"><?php echo iN_HelpSecure($LANG['age_confirm_yes']); ?></button>
            <button type="button" class="age_confirm_btn age_confirm_no"><?php echo iN_HelpSecure($LANG['age_confirm_no']); ?></button>
        </div>
        <div class="age_confirm_terms">
            <?php echo iN_HelpSecure($LANG['age_confirm_terms_text']); ?>
            <a class="age_confirm_terms_link" href="<?php echo iN_HelpSecure($base_url); ?>terms-of-use">
                <?php echo iN_HelpSecure($LANG['age_confirm_terms_link']); ?>
            </a>.
        </div>
    </div>
</div>
