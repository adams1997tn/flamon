<?php
/**
 * Demo account guard helpers.
 */

if (!defined('DEMO_ACCOUNT_USER_ID')) {
    define('DEMO_ACCOUNT_USER_ID', 7);
}

if (!isset($GLOBALS['IS_DEMO_ACCOUNT_ACTIVE'])) {
    $GLOBALS['IS_DEMO_ACCOUNT_ACTIVE'] = false;
}

if (!isset($GLOBALS['DEMO_GUARD_CURRENT_USER'])) {
    $GLOBALS['DEMO_GUARD_CURRENT_USER'] = null;
}

if (!function_exists('demo_guard_message')) {
    function demo_guard_message(): string {
        return 'This is demo account. You can not change anything.';
    }
}

if (!function_exists('demo_guard_is_demo')) {
    function demo_guard_is_demo(?int $userId = null): bool {
        if ($userId !== null) {
            return (int)$userId === (int)DEMO_ACCOUNT_USER_ID;
        }
        return !empty($GLOBALS['IS_DEMO_ACCOUNT_ACTIVE']);
    }
}

if (!function_exists('demo_guard_exit')) {
    function demo_guard_exit(string $format = 'plain'): void {
        $message = demo_guard_message();
        if (!headers_sent()) {
            http_response_code(403);
        }
        switch ($format) {
            case 'json':
                if (!headers_sent()) {
                    header('Content-Type: application/json; charset=utf-8');
                }
                echo json_encode([
                    'status'  => 'error',
                    'message' => $message,
                ]);
                break;
            case 'html':
                if (!headers_sent()) {
                    header('Content-Type: text/html; charset=utf-8');
                }
                echo '<div class="demo-guard-alert">' .
                    htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
                    '</div>';
                break;
            case 'admin_popup':
                if (!headers_sent()) {
                    header('Content-Type: text/html; charset=utf-8');
                }
                $jsMessage = json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                echo '<script>(function(){var m=' . $jsMessage . ';'
                    . 'if(window.top&&typeof window.top.showDemoGuardModal==="function"){window.top.showDemoGuardModal(m);return;}'
                    . 'if(typeof window.showDemoGuardModal==="function"){window.showDemoGuardModal(m);return;}'
                    . 'alert(m);})();</script>';
                echo '<div class="demo-guard-alert">' .
                    htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') .
                    '</div>';
                break;
            case 'plain':
            default:
                if (!headers_sent()) {
                    header('Content-Type: text/plain; charset=utf-8');
                }
                echo $message;
                break;
        }
        exit;
    }
}

if (!function_exists('demo_guard_should_block')) {
    function demo_guard_should_block(?string $type): bool {
        if (!demo_guard_is_demo() || $type === null || $type === '') {
            return false;
        }
        static $blocked = [
            // Profile & settings
            'saveEditPr','editS','editSC','editMyPage','editMyEmail','editMyPass',
            'changeMyLang','updateTheme','whoSee','uwcs','wcs','p_preferences',
            'updateAvatarCover','avatarUpload','coverUpload',
            'acceptConditions','device_key','remove_device_key',
            // Financial/account sensitive actions
            'payoutSet','updatePayoutSet','setSubscriptionPayments','updateSubscriptionPayments',
            'makewithDraw','creditCard','pPurchase','pLivePurchase',
            'subscribeMe','subscribeMeAut','subscribeWithIyzico','communitySubscribeWithIyzico','communityPlanSubscribeWithIyzico','subscribeWithCcbill','subscribeWithFlutterwave','subscribeWithYookassa','subscribeWithEpoch','communitySubscribeWithEpoch','communityPlanSubscribeWithEpoch','subWithPoints',
            'buyProduct','buyFrameGift','buyVideoCall',
            'moveMyAffilateBalance','moveMyEarnedPoints','creditPoint',
            'choosePaymentMethod','tip_payment_methods','process','processProduct',
            // Verification / account management
            'uploadVerificationFiles','verificationRequest','verificationRequestForBankPayment',
            'requestAccountDeletion','cancelAccountDeletion','createAccountExport',
        ];
        return in_array($type, $blocked, true);
    }
}

if (!function_exists('demo_guard_reject_uploads')) {
    function demo_guard_reject_uploads(): void {
        if (!demo_guard_is_demo()) {
            return;
        }
        if (!empty($_FILES)) {
            demo_guard_exit('plain');
        }
    }
}

if (!function_exists('demo_guard_detect_format')) {
    function demo_guard_detect_format(string $script): string {
        $map = [
            '/admin/default/request/request.php' => 'admin_popup',
            '/requests/request.php'              => 'plain',
            '/requests/inviteEmail.php'          => 'plain',
            '/requests/contact.php'              => 'plain',
            '/requests/register.php'             => 'plain',
        ];
        foreach ($map as $needle => $format) {
            if (strpos($script, $needle) !== false) {
                return $format;
            }
        }
        return 'plain';
    }
}

if (!function_exists('demo_guard_handle_request')) {
    function demo_guard_handle_request(): void {
        if (!demo_guard_is_demo()) {
            return;
        }

        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $script = $_SERVER['SCRIPT_NAME'] ?? ($_SERVER['PHP_SELF'] ?? '');
        if ($script === '' && isset($_SERVER['SCRIPT_FILENAME'])) {
            $script = str_replace('\\', '/', (string)$_SERVER['SCRIPT_FILENAME']);
        } else {
            $script = str_replace('\\', '/', (string)$script);
        }

        if (strtoupper($method) !== 'POST') {
            return;
        }

        if (strpos($script, '/requests/login.php') !== false ||
            strpos($script, '/logout.php') !== false ||
            strpos($script, '/requests/forgot.php') !== false) {
            return;
        }

        if (strpos($script, '/admin/') !== false) {
            demo_guard_exit(demo_guard_detect_format($script));
        }

        if (strpos($script, '/requests/request.php') !== false) {
            $type = $_POST['f'] ?? $_POST['type'] ?? $_POST['action'] ?? null;
            if (!empty($_FILES) && ($type === null || $type === '')) {
                demo_guard_exit(demo_guard_detect_format($script));
            }
            if (demo_guard_should_block($type)) {
                demo_guard_exit(demo_guard_detect_format($script));
            }
            return;
        }

        $blockedScripts = [
            '/requests/inviteEmail.php',
            '/requests/contact.php',
            '/requests/request.php',
            '/requests/payment.php',
            '/requests/requestPoint.php',
        ];

        foreach ($blockedScripts as $needle) {
            if (strpos($script, $needle) !== false) {
                demo_guard_exit(demo_guard_detect_format($script));
            }
        }

        demo_guard_exit(demo_guard_detect_format($script));
    }
}

if (!class_exists('DemoGuardPDOStatement')) {
    class DemoGuardPDOStatement extends PDOStatement {
        protected function __construct()
        {
            // Internal use only
        }

        public function execute(?array $params = null): bool
        {
            if (!demo_guard_is_demo() || !function_exists('demo_guard_sql_is_write')) {
                return parent::execute($params);
            }

            $sql = $this->queryString ?? '';
            if (!demo_guard_sql_is_write((string)$sql)) {
                return parent::execute($params);
            }

            $pdo = DB::pdo();
            $inTransaction = $pdo->inTransaction();
            if ($inTransaction) {
                $pdo->exec('SAVEPOINT demo_guard_stmt');
            } else {
                $pdo->beginTransaction();
            }

            try {
                $result = parent::execute($params);
                if ($inTransaction) {
                    $pdo->exec('ROLLBACK TO SAVEPOINT demo_guard_stmt');
                } else {
                    $pdo->rollBack();
                }
                $GLOBALS['DEMO_GUARD_WRITE_ATTEMPT'] = true;
                return $result;
            } catch (\Throwable $e) {
                if ($inTransaction) {
                    $pdo->exec('ROLLBACK TO SAVEPOINT demo_guard_stmt');
                } elseif ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }
        }
    }
}

if (!function_exists('demo_guard_install_pdo')) {
    function demo_guard_install_pdo(?PDO $pdo): void {
        static $installed = false;
        if ($installed || !$pdo) {
            return;
        }
        try {
            $pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, [DemoGuardPDOStatement::class, []]);
            $installed = true;
        } catch (\Throwable $e) {
            error_log('[demo_guard] Failed to install PDO guard: ' . $e->getMessage());
        }
    }
}

if (!function_exists('demo_guard_block_sql')) {
    function demo_guard_block_sql(string $sql): void {
        // Compatibility no-op; handled in DB layer.
    }
}

if (!function_exists('demo_guard_sql_is_write')) {
    function demo_guard_sql_is_write(string $sql): bool {
        return (bool) preg_match('/^\\s*(INSERT|UPDATE|DELETE|REPLACE|ALTER|CREATE|DROP|TRUNCATE|MERGE|LOCK|UNLOCK)/i', $sql);
    }
}

if (!function_exists('demo_guard_bootstrap')) {
    function demo_guard_bootstrap(): void {
        static $registered = false;
        if ($registered || !demo_guard_is_demo()) {
            return;
        }
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return;
        }
        $registered = true;
        register_shutdown_function(function () {
            if (!demo_guard_is_demo()) {
                return;
            }
            $message = demo_guard_message();
            ?>
<style>
    #demoGuardModal {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        z-index: 2147483646;
    }
    #demoGuardModal.is-visible {
        display: flex;
    }
    #demoGuardModal .demo_guard_backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.45);
    }
    #demoGuardModal .demo_guard_modal_box {
        position: relative;
        background: #ffffff;
        border-radius: 16px;
        padding: 28px 32px;
        box-shadow: 0 30px 60px rgba(15, 23, 42, 0.25);
        max-width: 420px;
        width: 100%;
        text-align: center;
        z-index: 1;
        font-family: system-ui, -apple-system, sans-serif;
    }
    body.darkMode #demoGuardModal .demo_guard_modal_box {
        background: #1f2532;
        box-shadow: 0 30px 60px rgba(0, 0, 0, 0.45);
        color: #cbd5f5;
    }
    #demoGuardModal .demo_guard_modal_title {
        font-weight: 600;
        font-size: 18px;
        margin-bottom: 8px;
        color: #1f2937;
    }
    body.darkMode #demoGuardModal .demo_guard_modal_title {
        color: #e2e8f0;
    }
    #demoGuardModal .demo_guard_modal_body {
        font-size: 15px;
        line-height: 1.6;
        color: #4b5563;
    }
    body.darkMode #demoGuardModal .demo_guard_modal_body {
        color: #cbd5f5;
    }
    #demoGuardModal .demo_guard_close {
        position: absolute;
        top: 10px;
        right: 10px;
        width: 32px;
        height: 32px;
        border-radius: 12px;
        border: none;
        background: rgba(148, 163, 184, 0.2);
        color: #4b5563;
        font-size: 20px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.2s ease, color 0.2s ease;
    }
    body.darkMode #demoGuardModal .demo_guard_close {
        background: rgba(148, 163, 184, 0.15);
        color: #cbd5f5;
    }
    #demoGuardModal .demo_guard_close:hover {
        background: rgba(148, 163, 184, 0.35);
        color: #111827;
    }
    body.darkMode #demoGuardModal .demo_guard_close:hover {
        background: rgba(148, 163, 184, 0.3);
        color: #ffffff;
    }
</style>
<div class="demo_guard_modal" id="demoGuardModal" role="dialog" aria-modal="true" aria-labelledby="demoGuardModalTitle" hidden>
    <div class="demo_guard_backdrop"></div>
    <div class="demo_guard_modal_box">
        <button type="button" class="demo_guard_close" aria-label="Close">&times;</button>
        <div class="demo_guard_modal_title" id="demoGuardModalTitle">Demo Mode</div>
        <div class="demo_guard_modal_body"><?php echo htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
    </div>
</div>
<script>
(function () {
    var demoMessage = <?php echo json_encode($message); ?>;
    window.demoGuardMessage = demoMessage;
    window.isDemoAccount = true;
    function ready(fn) {
        if (document.readyState !== 'loading') { fn(); }
        else { document.addEventListener('DOMContentLoaded', fn); }
    }
    ready(function () {
        var modal = document.getElementById('demoGuardModal');
        if (!modal) {
            window.showDemoGuardModal = function (text) { alert(text || demoMessage); };
            return;
        }
        var bodyEl = modal.querySelector('.demo_guard_modal_body');
        var closeBtn = modal.querySelector('.demo_guard_close');
        var backdrop = modal.querySelector('.demo_guard_backdrop');
        function hideModal() {
            modal.classList.remove('is-visible');
            modal.setAttribute('hidden', 'hidden');
        }
        function showModal(text) {
            if (bodyEl) {
                bodyEl.textContent = text || demoMessage;
            }
            modal.classList.add('is-visible');
            modal.removeAttribute('hidden');
        }
        window.showDemoGuardModal = showModal;
        if (closeBtn) { closeBtn.addEventListener('click', hideModal); }
        if (backdrop) { backdrop.addEventListener('click', hideModal); }
        modal.addEventListener('click', function (evt) {
            if (evt.target === modal) { hideModal(); }
        });
        document.addEventListener('keydown', function (evt) {
            if (evt.key === 'Escape' && modal.classList.contains('is-visible')) {
                hideModal();
            }
        });
        if (window.jQuery) {
            jQuery(document).ajaxError(function (_event, jqxhr) {
                if (!jqxhr) { return; }
                var response = jqxhr.responseText || '';
                if (jqxhr.status === 403 && response.indexOf(demoMessage) !== -1) {
                    jQuery('.loaderWrapper').remove();
                    jQuery('.i_nex_btn_btn, button[type="submit"], input[type="submit"]').prop('disabled', false);
                    showModal(response.trim());
                }
            });
            jQuery(document).ajaxSuccess(function (_event, xhr) {
                if (!xhr) { return; }
                var responseText = typeof xhr.responseText === 'string' ? xhr.responseText : '';
                if (responseText && responseText.indexOf(demoMessage) === 0) {
                    jQuery('.loaderWrapper').remove();
                    jQuery('.i_nex_btn_btn, button[type="submit"], input[type="submit"]').prop('disabled', false);
                    showModal(responseText.trim());
                }
            });
        }
    });
})();
</script>
<?php
        });
    }
}

if (!function_exists('demo_guard_runtime_init')) {
    /**
     * Runtime entry point. Call this once after $userID is available in inc.php.
     */
    function demo_guard_runtime_init(?int $userId, $pdoInstance = null): string {
        if (empty($userId)) {
            $GLOBALS['DEMO_GUARD_CURRENT_USER'] = null;
            $GLOBALS['IS_DEMO_ACCOUNT_ACTIVE'] = false;
            return '';
        }

        $uid = (int) $userId;
        $GLOBALS['DEMO_GUARD_CURRENT_USER'] = $uid;
        $GLOBALS['IS_DEMO_ACCOUNT_ACTIVE'] = demo_guard_is_demo($uid);

        if (empty($GLOBALS['IS_DEMO_ACCOUNT_ACTIVE'])) {
            return '';
        }

        $message = demo_guard_message();
        // SQL rollback guard is disabled to allow demo content actions
        // (post/comment/story/reel create-delete). Restrictions are enforced
        // by request/action guards above.
        demo_guard_bootstrap();
        demo_guard_handle_request();

        return $message;
    }
}

if (!function_exists('demo_guard_assign_default_category')) {
    /**
     * Keep demo user untouched; set defaults only for normal users.
     */
    function demo_guard_assign_default_category(int $userId, $category) {
        if (demo_guard_is_demo($userId)) {
            return $category;
        }
        if (!empty($category)) {
            return $category;
        }
        DB::exec("UPDATE i_users SET profile_category = 'normal_user' WHERE iuid = ?", [$userId]);
        return 'normal_user';
    }
}
