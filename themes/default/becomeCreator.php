<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1">
    <title><?php echo iN_HelpSecure($siteTitle); ?></title>
    <?php
    include("layouts/header/meta.php");
    include("layouts/header/css.php");
    include("layouts/header/javascripts.php");
    ?>
</head>
<body>
<?php
if ($logedIn == 0) {
    include 'layouts/login_form.php';
}

$completedLvlItem = $completedLvlItemBold = '';
$completedLvlItemTwo = $completedLvlItemBoldTwo = '';
$completedLvlItemTree = $completedLvlItemBoldTree = '';
$completedLvlItemFour = $completedLvlItemBoldFour = '';
$completedLvlItemFive = $completedLvlItemBoldFive = '';
$completedLvlItemSix = $completedLvlItemBoldSix = '';
$completedLvlWidth = '0%';

// Determine current step based on DB statuses
// Step order: Certificate(1) → Terms(2) → Fees(3) → Payment(4) → Verification(5) → Activation(6)
$currentStep = 0;
if ($certificationStatus >= '1') $currentStep = 1;
if ($certificationStatus == '2' && $conditionStatus == '2') $currentStep = 2;
if ($currentStep >= 2 && $feesStatus == '2') $currentStep = 3;
if ($currentStep >= 3 && $payoutStatus == '2') $currentStep = 4;
if ($currentStep >= 4 && $validationStatus >= '1') $currentStep = 5;
if ($currentStep >= 5 && $validationStatus == '2') $currentStep = 6;

$stepWidths = array('0%', '16%', '33%', '50%', '66%', '83%', '100%');
$completedLvlWidth = $stepWidths[$currentStep];

$lvlClasses = array(
    array(&$completedLvlItem, &$completedLvlItemBold),
    array(&$completedLvlItemTwo, &$completedLvlItemBoldTwo),
    array(&$completedLvlItemTree, &$completedLvlItemBoldTree),
    array(&$completedLvlItemFour, &$completedLvlItemBoldFour),
    array(&$completedLvlItemFive, &$completedLvlItemBoldFive),
    array(&$completedLvlItemSix, &$completedLvlItemBoldSix),
);
for ($i = 0; $i <= $currentStep && $i < 6; $i++) {
    $lvlClasses[$i][0] = 'i_completed_level_item';
    $lvlClasses[$i][1] = 'i_completed_levet_item_bold';
}
?>
<?php include("layouts/header/header.php"); ?>
<div class="wrapper bCreatorBg bCreator_padding">
    <div class="i_become_creator_container">
        <div class="i_become_creator_levels">
            <div class="i_become_creator_levels_title"><?php echo iN_HelpSecure($LANG['become_creator']); ?></div>
            <div class="i_levels_container">
                <div class="i_levels_container_position"></div>
                <div class="i_levels_container_position_lvl dynamic-bar" data-width="<?php echo iN_HelpSecure($completedLvlWidth); ?>"></div>

                <div class="i_complete_level">
                    <div class="i_complete_level_box <?php echo iN_HelpSecure($completedLvlItem); ?>">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('14')); ?>
                    </div>
                    <div class="i_complete_level_name <?php echo iN_HelpSecure($completedLvlItemBold); ?>">
                        <?php echo iN_HelpSecure($LANG['certification']); ?>
                    </div>
                </div>

                <div class="i_complete_level">
                    <div class="i_complete_level_box <?php echo iN_HelpSecure($completedLvlItemTwo); ?>">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('75')); ?>
                    </div>
                    <div class="i_complete_level_name <?php echo iN_HelpSecure($completedLvlItemBoldTwo); ?>">
                        <?php echo iN_HelpSecure($LANG['conditions']); ?>
                    </div>
                </div>

                <div class="i_complete_level">
                    <div class="i_complete_level_box <?php echo iN_HelpSecure($completedLvlItemTree); ?>">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('76')); ?>
                    </div>
                    <div class="i_complete_level_name <?php echo iN_HelpSecure($completedLvlItemBoldTree); ?>">
                        <?php echo iN_HelpSecure($LANG['fees']); ?>
                    </div>
                </div>

                <div class="i_complete_level">
                    <div class="i_complete_level_box <?php echo iN_HelpSecure($completedLvlItemFour); ?>">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('77')); ?>
                    </div>
                    <div class="i_complete_level_name <?php echo iN_HelpSecure($completedLvlItemBoldFour); ?>">
                        <?php echo iN_HelpSecure($LANG['payouts']); ?>
                    </div>
                </div>

                <div class="i_complete_level">
                    <div class="i_complete_level_box <?php echo iN_HelpSecure($completedLvlItemFive); ?>">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('47')); ?>
                    </div>
                    <div class="i_complete_level_name <?php echo iN_HelpSecure($completedLvlItemBoldFive); ?>">
                        <?php echo iN_HelpSecure($LANG['validation']); ?>
                    </div>
                </div>

                <div class="i_complete_level">
                    <div class="i_complete_level_box <?php echo iN_HelpSecure($completedLvlItemSix); ?>">
                        <?php echo html_entity_decode($iN->iN_SelectedMenuIcon('82')); ?>
                    </div>
                    <div class="i_complete_level_name <?php echo iN_HelpSecure($completedLvlItemBoldSix); ?>">
                        <?php echo iN_HelpSecure($LANG['activation'] ?? 'Activation'); ?>
                    </div>
                </div>
            </div>
        </div>

        <?php
        // Step routing: Certificate → Terms → Fees → Payment → Verification → Activation
        if ($certificationStatus == '0') {
            // Step 1: Certificate - Upload ID documents
            include("layouts/widgets/certification.php");
        } elseif ($certificationStatus >= '1' && $conditionStatus != '2') {
            // Step 2: Terms - Accept conditions
            include("layouts/widgets/terms.php");
        } elseif ($conditionStatus == '2' && $feesStatus != '2') {
            // Step 3: Fees - Set subscription pricing
            if ($subscriptionType == '2') {
                include "layouts/widgets/feesPoint.php";
            } elseif ($subscriptionType == '1') {
                include "layouts/widgets/fees.php";
            }
        } elseif ($feesStatus == '2' && $payoutStatus != '2') {
            // Step 4: Payment - Set payout method
            include("layouts/widgets/payout.php");
        } elseif ($payoutStatus == '2' && $validationStatus != '2') {
            // Step 5: Verification - Pending admin review
            include("layouts/widgets/verification.php");
        } elseif ($validationStatus == '2') {
            // Step 6: Activation - Congratulations!
            $iN->iN_UpdateVerificationAnswerReadStatus($userID);
            echo '
            <div class="i_become_creator_terms_box">
                <div class="certification_form_container">
                    <div class="creator_conguratulation_title">' . $LANG['conguratulation'] . '</div>
                    <div class="creator_conguratulation">' . $iN->iN_SelectedMenuIcon('82') . '</div>
                    <div class="creator_conguratulation_note">' . $LANG['conguratulation_note'] . '</div>
                </div>
            </div>
            ';
        }
        ?>
    </div>
</div> 
  <script type="text/javascript" src="<?php echo iN_HelpSecure($base_url);?>themes/<?php echo iN_HelpSecure($currentTheme);?>/js/becomeCreator.js?v=<?php echo iN_HelpSecure($version); ?>"></script>
</body>
</html>