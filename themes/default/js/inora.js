(function($) {
    "use strict";

    // Normalize all text AJAX responses by trimming whitespace/newlines
    if ($ && $.ajaxSetup) {
      $.ajaxSetup({
        converters: {
          "text text": function (text) {
            return (typeof text === 'string') ? text.trim() : text;
          }
        }
      });
    }

    $(document).on("click", ".copyUrl", function() {
        PopUPAlerts('urlCopied', 'ialert');
    });
    document.addEventListener("DOMContentLoaded", function () {
      const mappings = [
        { selector: '.i_profile_cover_blur', attr: 'data-background' },
        { selector: '.i_profile_avatar', attr: 'data-avatar' }
      ];

      mappings.forEach(function (map) {
        document.querySelectorAll(map.selector).forEach(function (el) {
          const val = el.getAttribute(map.attr);
          if (val) {
            el.style.backgroundImage = 'url(' + val + ')';
          }
        });
      });
    });
    document.querySelectorAll('.hshCl[data-color]').forEach(function(el) {
      el.style.color = el.getAttribute('data-color');
    });
    var preLoadingAnimation = '<div class="i_loading product_page_loading"><div class="dot-pulse"></div></div>';
    var plreLoadingAnimationPlus = '<div class="loaderWrapper"><div class="loaderContainer"><div class="loader">' + preLoadingAnimation + '</div></div></div>';
    var likeBox = '<div class="like_heart flex_ tabing">' +
        '<svg width="800px" height="800px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">' +
        '<path d="M2 9.1371C2 14 6.01943 16.5914 8.96173 18.9109C10 19.7294 11 20.5 12 20.5C13 20.5 14 19.7294 15.0383 18.9109C17.9806 16.5914 22 14 22 9.1371C22 4.27416 16.4998 0.825464 12 5.50063C7.50016 0.825464 2 4.27416 2 9.1371Z" fill="#d32f2f"/>' +
        '</svg>' +
        '</div>';
    var UnlikeBox = '<div class="like_heart flex_ tabing">' +
    '<svg width="800px" height="800px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">' +
    '<path d="M8.10627 18.2468C5.29819 16.0833 2 13.5422 2 9.1371C2 4.53656 6.9226 1.20176 11.2639 4.81373L9.81064 8.20467C9.6718 8.52862 9.77727 8.90554 10.0641 9.1104L12.8973 11.1341L10.4306 14.012C10.1755 14.3096 10.1926 14.7533 10.4697 15.0304L12.1694 16.7302L11.2594 20.3702C10.5043 20.1169 9.74389 19.5275 8.96173 18.9109C8.68471 18.6925 8.39814 18.4717 8.10627 18.2468Z" fill="#d32f2f"/>' +
    '<path d="M12.8118 20.3453C13.5435 20.0798 14.2807 19.5081 15.0383 18.9109C15.3153 18.6925 15.6019 18.4717 15.8937 18.2468C18.7018 16.0833 22 13.5422 22 9.1371C22 4.62221 17.259 1.32637 12.9792 4.61919L11.4272 8.24067L14.4359 10.3898C14.6072 10.5121 14.7191 10.7007 14.7445 10.9096C14.7699 11.1185 14.7064 11.3284 14.5694 11.4882L12.0214 14.4609L13.5303 15.9698C13.7166 16.1561 13.7915 16.4264 13.7276 16.682L12.8118 20.3453Z" fill="#d32f2f"/>' +
    '</svg>' +
    '</div>';
    $(document).ready(function () {
        if ($('.commenta').length > 0 && $.fn.autoResize) {
          $('.commenta').autoResize();
        }

        if (typeof ClipboardJS !== "undefined") {
          new ClipboardJS('.copyUrl');
        }

        if ($('#newPostT').length > 0 && typeof $.fn.characterCounter === "function") {
          $("#newPostT").characterCounter({
            limit: typeof availableLength !== "undefined" ? availableLength : 250
          });
        }
      });
    /*Notifications*/
    var g = '';
    var NOTIFICATION_POLL_VISIBLE_MS = 15000;
    var NOTIFICATION_POLL_HIDDEN_MS = 45000;
    var notificationPollTimer = null;
    var notificationPollInFlight = false;

    function nextNotificationPollDelay() {
        return document.hidden ? NOTIFICATION_POLL_HIDDEN_MS : NOTIFICATION_POLL_VISIBLE_MS;
    }

    function scheduleNotificationPoll(delayMs) {
        if (notificationPollTimer) {
            clearTimeout(notificationPollTimer);
        }
        notificationPollTimer = setTimeout(function () {
            getm(g);
        }, typeof delayMs === 'number' ? delayMs : nextNotificationPollDelay());
    }

    getm(g);
    function playNotificationSound() {
        var audio = document.getElementById('notification-sound-not');
        if (audio) {
            var playPromise = audio.play();

            if (playPromise !== undefined) {
                playPromise.then(_ => {}).catch(error => {});
            }
        }
    }
    function getm(g) {
        if (notificationPollInFlight) {
            return;
        }
        var type = 'get';
        if ($.trim(type).length === 0) {
            scheduleNotificationPoll();
        } else {
            notificationPollInFlight = true;
            $.ajax({
                type: 'GET',
                url: siteurl + 'requests/get.php?f=1',
                dataType: "json",
                cache: false,
                beforeSend: function() {},
                success: function(response) {
                    var messageNotificationStatus = response.messageNotificationStatus;
                    var notificationStatus = response.notificationStatus;
                    var unReadedNotfications = response.unReadedNotfications;
                    var unReadMessageNotifications = response.unReadMessageNotifications;
                    var videoCallFound = response.videoCallFound;
                    var acceptStatus = response.acceptStatus;
                    if (messageNotificationStatus == '1') {
                        $(".msg_not").show();
                        $(".sum_m").html(unReadMessageNotifications);
                        if ($(".sum_m").attr("data-id") != messageNotificationStatus) {
                            $(".sum_m").attr("data-id", messageNotificationStatus);
                            playNotificationSound();
                        }
                    }
                    if (notificationStatus == '1') {
                        $(".not_not").show();
                        $(".sum_not").html(unReadedNotfications);
                        if ($(".sum_not").attr("data-id") != notificationStatus) {
                            $(".sum_not").attr("data-id", notificationStatus);
                            document.getElementById('notification-sound-not').play();
                        }
                    }
                    if (videoCallFound) {
                        if (!$("div").hasClass("videoCall")) {
                            VideoCallAlert(videoCallFound);
                        }
                    }
                    if (acceptStatus == '2') {
                        // Callee accepted: stop ring and remove caller's ringing modal if present
                        try { $("#notification-sound-call")[0].pause(); } catch(e){}
                        $(".videoCall").remove();
                    }
                    if (acceptStatus == '3') {
                        $(".caller_det").hide();
                        $(".call_declined").show();
                        $("#notification-sound-call")[0].pause();
                    }
                },
                complete: function() {
                    notificationPollInFlight = false;
                    if (!g) {
                        scheduleNotificationPoll();
                    }
                }
            });
        }
    }
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden && !notificationPollInFlight) {
            scheduleNotificationPoll(1200);
        }
    });
    $(document).on("click", ".loginForm", function() {
        $('.i_modal_bg').addClass('i_modal_display');
    });

    function initBoostedCreatorsRotator() {
        var $grids = $('.boosted-creators-grid');
        if (!$grids.length) {
            return;
        }

        function updateBoostedViewportWidth($grid) {
            var $items = $grid.children('.boosted-creator-card');
            if ($items.length < 3) {
                $grid.css('width', '');
                return;
            }
            var firstEl = $items.get(0);
            var thirdEl = $items.get(2);
            if (!firstEl || !thirdEl) {
                return;
            }
            var firstRect = firstEl.getBoundingClientRect();
            var thirdRect = thirdEl.getBoundingClientRect();
            var width = Math.ceil(thirdRect.right - firstRect.left);
            if (width > 0) {
                $grid.css('width', width + 'px');
            }
        }

        function setBoostedSlotClasses($grid) {
            var $cards = $grid.children('.boosted-creator-card');
            $cards.removeClass('boosted-slot-left boosted-slot-center boosted-slot-right');
            if ($cards.length < 3) {
                return;
            }
            $cards.eq(0).addClass('boosted-slot-left');
            $cards.eq(1).addClass('boosted-slot-center');
            $cards.eq(2).addClass('boosted-slot-right');
        }

        var resizeBound = false;
        function bindResize() {
            if (resizeBound) {
                return;
            }
            resizeBound = true;
            $(window).on('resize.boostedCreators', function () {
                $('.boosted-creators-grid.boosted-creators-rotator').each(function () {
                    updateBoostedViewportWidth($(this));
                });
            });
        }

        $grids.each(function () {
            var $grid = $(this);
            if ($grid.data('boosted-rotator') === 1) {
                return;
            }
            var $cards = $grid.children('.boosted-creator-card');
            if ($cards.length < 3) {
                return;
            }
            $grid.data('boosted-rotator', 1);
            $grid.addClass('boosted-creators-rotator');
            setBoostedSlotClasses($grid);
            requestAnimationFrame(function () {
                updateBoostedViewportWidth($grid);
            });
            bindResize();

            var intervalId = setInterval(function () {
                if (!$grid.closest('body').length) {
                    clearInterval(intervalId);
                    return;
                }

                var $items = $grid.children('.boosted-creator-card');
                if ($items.length < 3) {
                    clearInterval(intervalId);
                    return;
                }
                if ($grid.hasClass('boosted-rotating')) {
                    return;
                }

                var firstRects = new Map();
                $items.each(function () {
                    firstRects.set(this, this.getBoundingClientRect());
                });

                $items.first().appendTo($grid);
                setBoostedSlotClasses($grid);

                var $reordered = $grid.children('.boosted-creator-card');
                $reordered.each(function () {
                    var firstRect = firstRects.get(this);
                    if (!firstRect) {
                        return;
                    }
                    var lastRect = this.getBoundingClientRect();
                    var deltaX = firstRect.left - lastRect.left;
                    var deltaY = firstRect.top - lastRect.top;
                    this.style.transform = 'translate(' + deltaX + 'px, ' + deltaY + 'px)';
                });

                $reordered.each(function () {
                    this.offsetHeight;
                });

                $grid.addClass('boosted-rotating');
                $reordered.each(function () {
                    this.style.transform = '';
                });

                setTimeout(function () {
                    $grid.removeClass('boosted-rotating');
                }, 460);
            }, 5000);
        });
    }

    $(document).ready(function () {
        initBoostedCreatorsRotator();
    });

    // Generic handler for upload/network failures
    function handleUploadError(xhr, status, error) {
        try { console.error('Upload error', { status: status, error: error, code: xhr && xhr.status }); } catch (e) {}
        // Abort = user canceled; skip noisy alert
        if (status === 'abort') { return; }
        if (status === 'timeout' || (xhr && xhr.status === 0)) {
            PopUPAlerts('upload_connection_lost', 'ialert');
        } else {
            PopUPAlerts('sWrong', 'ialert');
        }
        $(".processing-msg").remove();
        $(".i_upload_progress").removeClass('processing-animation').width('0%');
        $('.publish').prop('disabled', false);
        $(".publish").css("pointer-events", "auto");
    }
    $(document).on("click", ".i_modal_close", function() {
        $('.i_modal_bg').removeClass('i_modal_display');
        $(".i_modal_in").attr("style", "");
        $(".i_modal_forgot").hide();
    });
    $(document).on("click", ".password-reset", function() {
        $(".i_modal_in").hide();
        $(".i_modal_forgot").show();
    });
    $(document).on("click", ".already-member", function() {
        $(".i_modal_in").show();
        $(".i_modal_forgot").hide();
    });

    $(".i_comment_form_textarea").focusin(function() {
        var words = $(this).val();
        var ID = $(this).attr("data-id");
    });
    $(document).on("click", ".openPostMenu", function() {
        var ID = $(this).attr("id");
        $(".mnoBox" + ID).addClass("dblock");
    });
	    $(document).on("click", ".openShareMenu", function (e) {
	        e.preventDefault();
	        const $button = $(this);
	        const $menu = $button.find(".mnsBox").first();
	
	        if (!$menu.length) {
	            return;
	        }
	
	        // Close any other open share menus (prevents opening an off-DOM/duplicate menu)
	        $(".mnsBox.dblock").not($menu).removeClass("dblock");
	        $menu.toggleClass("dblock");
	    });
    $(document).on("click", ".openComMenu", function() {
        var ID = $(this).attr("id");
        $(".comMBox" + ID).addClass("dblock");
    });
    $(document).on("click", ".msg_Set", function() {
        var ID = $(this).attr("id");
        if ($(".msg_Set")[0]) {
            $(".msg_Set").removeClass("dblock");
        }
        $(".msg_Set_" + ID).addClass("dblock");
    });
    $(document).on("click", ".smscd", function() {
        var ID = $(this).attr("id");
        var targetMenu = $(".msg_set_plus_" + ID);
        var isOpen = targetMenu.hasClass("dblock");
        if ($(".smscd")[0]) {
            $(".me_msg_plus").removeClass("dblock");
            $(".msg").removeClass("msg-menu-open");
        }
        if (!isOpen) {
            targetMenu.addClass("dblock");
            $("#msg_" + ID).addClass("msg-menu-open");
        }
    });
    $(document).on("click", ".whs", function() {
        $(".i_choose_ws_wrapper").addClass("dblock");
    });
    $(document).on("click", ".in_comment", function() {
        var ID = $(this).attr("id");
        $("#comment" + ID).focus();
    });
    $(document).on("mouseup touchend", function(e) {
        /*e.preventDefault();*/
        var postMenuContainer = $('.mnoBox , .mnsBox , .comMBox , .msg_Set , .me_msg_plus ,  .cSetc , .i_choose_ws_wrapper , .i_postFormContainer , .emojiBox , .emojiBoxC , .stickersContainer');
        var notif = $('.topMessages, .topPoints, .topNotifications , .getMenu , .emojiBox , .emojiBoxC , .camList , .stickersContainer , .chtBtns');
        if (!postMenuContainer.is(e.target) && postMenuContainer.has(e.target).length === 0) {
            $(postMenuContainer).removeClass('dblock');
            $(".msg").removeClass("msg-menu-open");
        }
        if (!notif.is(e.target) && notif.has(e.target).length === 0) {
            $(".i_general_box_container , .i_general_box_message_notifications_container , .i_general_box_notifications_container , .emojiBox , .emojiBoxC , .stickersContainer , .ch_fl_btns_container").remove();
        }
    });
    $(document).on("click", ".emoji_item", function() {
        var copyEmoji = $(this).attr("data-emoji");
        var getValue = $(".newPostT").val();
        $(".newPostT").val(getValue + copyEmoji);
        $(".emojiBox").remove();
    });
    $(document).on("click", ".emoji_item_c", function() {
        var copyEmoji = $(this).attr("data-emoji");
        var ID = $(this).attr("data-id");
        var getValue = $("#comment" + ID).val();
        $("#comment" + ID).val(getValue + ' ' + copyEmoji + ' ');
    });

    function GetSlimScroll() {
        if ($(window).width() < 330) {
            $(".btest").slimScroll({
                height: '100%',
                width: '100%',
                railVisible: false,
                alwaysVisible: false,
                wheelStep: 1,
                railOpacity: .1
            });
        }
    }
    GetSlimScroll();
    $(document).on("click", ".getMenu", function() {
        var type = $(this).attr("data-type");
        var data = 'f=' + type;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {},
            success: function(response) {
                if (!$(".i_general_box_container")[0]) {
                    $("#" + type).append(response);
                    GetSlimScroll();
                } else {
                    $(".i_general_box_container").remove();
                }
                if ($("div").hasClass("stickersContainer") || $("div").hasClass("emojiBoxC") || $("div").hasClass("emojiBox") || $("div").hasClass("i_general_box_message_notifications_container") || $("div").hasClass("i_general_box_notifications_container")) {
                    $(".stickersContainer , .emojiBox , .emojiBoxC ,  .i_general_box_message_notifications_container , .i_general_box_notifications_container").remove();
                }
            }
        });
    });
    $(document).on("click", ".getEmojis", function() {
        var type = 'emoji';
        var ID = $(this).attr("data-type");
        var dataID = '';
        var data = 'f=' + type + '&id=' + ID + '&ec=' + dataID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {},
            success: function(response) {
                if (!$(".emojiBox")[0]) {
                    $(".i_pb_emojis").append(response);
                    GetSlimScroll();
                } else {
                    $(".emojiBox").remove();
                }
                if ($("div").hasClass("stickersContainer") || $("div").hasClass("emojiBoxC") || $("div").hasClass("i_general_box_container") || $("div").hasClass("i_general_box_message_notifications_container") || $("div").hasClass("i_general_box_notifications_container")) {
                    $(".stickersContainer , .emojiBoxC , .i_general_box_message_notifications_container , .i_general_box_notifications_container , .i_general_box_container").remove();
                }
            }
        });
    });
    $(document).on("click", ".getAiBox", function() {
        var type = 'aiBox';
        var ID = $(this).attr("data-type");
        var data = 'f=' + type;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {},
            success: function(response) {
                if (response) {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                    var $modal = $(".i_modal_bg_in");
                    var existingText = $(".newPostT").val() || '';
                    $modal.find(".aiSourceT").val(existingText.trim());
                    $modal.find(".aiAction").trigger("change");
                }
            }
        });
    });
    $(document).on("click", ".createAiContent", function() {
        var type = 'generateAiContent';
        var $modal = $(".i_modal_bg_in");
        var prompt = $modal.find(".aiContT").val().trim();
        var source = $modal.find(".aiSourceT").val().trim();
        var action = $modal.find(".aiAction").val() || 'generate';
        var template = $modal.find(".aiTemplate").val() || 'caption';
        var tone = $modal.find(".aiTone").val() || 'neutral';
        var length = $modal.find(".aiLength").val() || 'medium';
        var language = $modal.find(".aiLanguage").val() || 'auto';
        var variants = $modal.find(".aiVariants").val() || '2';
        var csrfToken = $modal.find(".ai_csrf_token").val() || $('meta[name=csrf-token]').attr('content') || '';
        var errorBox = $modal.find(".i_warning_ai_error");
        var variantsBox = $modal.find(".ai_variants");
        var variantsList = $modal.find(".ai_variants_list");
        $modal.find(".i_warning_ai, .i_warning_ai_credit").hide();
        errorBox.hide().text('').addClass('nonePoint');
        variantsBox.hide();
        variantsList.empty();
        if (action === 'generate' && prompt === '') {
            var promptRequired = $modal.data('ai-prompt-required') || '';
            if (promptRequired !== '') {
                errorBox.text(promptRequired).removeClass('nonePoint').show();
            }
            return;
        }
        if (action !== 'generate' && source === '') {
            var sourceRequired = $modal.data('ai-source-required') || '';
            if (sourceRequired !== '') {
                errorBox.text(sourceRequired).removeClass('nonePoint').show();
            }
            return;
        }
        var data = {
            f: type,
            uPrompt: prompt,
            ai_source: source,
            ai_action: action,
            ai_template: template,
            ai_tone: tone,
            ai_length: length,
            ai_language: language,
            ai_variants: variants
        };
        if (csrfToken) {
            data.csrf_token = csrfToken;
        }

        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {
                $(".i_modal_content").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                $(".loaderWrapper").remove();
                $(".i_loading").remove();
                var data = null;
                if (response && typeof response === 'object') {
                    data = response;
                } else {
                    try {
                        data = JSON.parse(response);
                    } catch (e) {
                        data = null;
                    }
                }
                if (!data) {
                    var genericError = $modal.data('ai-generic-error') || '';
                    if (genericError !== '') {
                        errorBox.text(genericError).removeClass('nonePoint').show();
                    }
                    return;
                }
                if (data.status !== 'success') {
                    if (data.code === 'invalid_api_key') {
                        $(".i_warning_ai").removeClass('nonePoint').show();
                        setTimeout(() => {
                            $(".i_warning_ai").hide();
                        }, 5000);
                        return;
                    }
                    if (data.code === 'no_enough_credit') {
                        $(".i_warning_ai_credit").removeClass('nonePoint').show();
                        setTimeout(() => {
                            $(".i_warning_ai_credit").hide();
                        }, 5000);
                        return;
                    }
                    var fallbackError = $modal.data('ai-generic-error') || '';
                    errorBox.text(data.message || fallbackError).removeClass('nonePoint').show();
                    return;
                }
                variantsList.empty();
                var useText = variantsBox.data('use') || '';
                (data.items || []).forEach(function(item, idx) {
                    var text = (item.text || '').trim();
                    if (text === '') {
                        return;
                    }
                    var $item = $('<div class="ai_variant_item"></div>');
                    $('<div class="ai_variant_title"></div>').text((idx + 1).toString()).appendTo($item);
                    $('<div class="ai_variant_text"></div>').text(text).appendTo($item);
                    $('<button type="button" class="ai_variant_use"></button>').text(useText).data('text', text).appendTo($item);
                    variantsList.append($item);
                });
                if (variantsList.children().length > 0) {
                    variantsBox.removeClass("nonePoint").show();
                }
            },
            error: function() {
                $(".loaderWrapper").remove();
                // Avoid using console in production. You may optionally show an error box to the user here.
            },
            complete: function() {
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on("click", ".ai_variant_use", function() {
        var text = ($(this).data('text') || '').toString().trim();
        if (text === '') {
            return;
        }
        $(".i_modal_bg_in").remove();
        $(".newPostT").val(text + "\n").focus();
        $('.newPostT').autoResize();
    });
    $(document).on("change", ".aiAction", function() {
        var $modal = $(this).closest(".i_modal_bg_in");
        var action = $(this).val() || 'generate';
        if (action === 'generate') {
            $modal.find(".ai_source_wrap").addClass("nonePoint");
        } else {
            $modal.find(".ai_source_wrap").removeClass("nonePoint");
        }
    });
    $(document).on("click", ".getEmojisC", function() {
        var type = 'emoji';
        var ID = $(this).attr("data-type");
        var dataID = $(this).attr("data-id");
        var data = 'f=' + type + '&id=' + ID + '&ec=' + dataID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {},
            success: function(response) {
                if (!$(".emojiBoxC")[0]) {
                    $(".getEmojisC" + dataID).append(response);
                    GetSlimScroll();
                } else {
                    $(".emojiBoxC").remove();
                }
                if ($("div").hasClass("stickersContainer") || $("div").hasClass("emojiBox") || $("div").hasClass("i_general_box_container") || $("div").hasClass("i_general_box_message_notifications_container") || $("div").hasClass("i_general_box_notifications_container")) {
                    $(".i_general_box_message_notifications_container , .i_general_box_notifications_container , .i_general_box_container").remove();
                }
            }
        });
    });
    $(document).on("click", ".topMessages", function() {
        var type = $(this).attr("data-type");
        var data = 'f=' + type;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {},
            success: function(response) {
                if (!$(".i_general_box_message_notifications_container")[0]) {
                    $("#" + type).append(response);
                    $(".msg_not").hide();
                    $(".sum_m").attr("data-id", 0);
                    GetSlimScroll();
                } else {
                    $(".i_general_box_message_notifications_container").remove();
                }
                if ($("div").hasClass("stickersContainer") || $("div").hasClass("emojiBoxC") || $("div").hasClass("emojiBox") || $("div").hasClass("i_general_box_container") || $("div").hasClass("i_general_box_notifications_container")) {
                    $(".stickersContainer , .emojiBox , .emojiBoxC , .i_general_box_container , .i_general_box_notifications_container").remove();
                }
            }
        });
    });
    $(document).on("click", ".topPoints", function() {
        var type = $(this).attr("data-type");
        var data = 'f=' + type;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {},
            success: function(response) {
                if (!$(".i_general_box_container")[0]) {
                    $("#" + type).append(response);
                } else {
                    $(".i_general_box_container").remove();
                }
                if ($("div").hasClass("stickersContainer") || $("div").hasClass("emojiBoxC") || $("div").hasClass("emojiBox") || $("div").hasClass("i_general_box_container") || $("div").hasClass("i_general_box_notifications_container")) {
                    $(".stickersContainer , .emojiBox , .emojiBoxC , .i_general_box_notifications_container , .i_general_box_message_notifications_container").remove();
                }
            }
        });
    });
    $(document).on("click", ".topNotifications", function() {
        var type = $(this).attr("data-type");
        var data = 'f=' + type;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {},
            success: function(response) {
                if (!$(".i_general_box_notifications_container")[0]) {
                    $("#" + type).append(response);
                    if ($(".i_notifications_count")[0]) {
                        $(".not_not").hide();
                    }
                    GetSlimScroll();
                } else {
                    $(".i_general_box_notifications_container").remove();
                }
                if ($("div").hasClass("stickersContainer") || $("div").hasClass("emojiBoxC") || $("div").hasClass("emojiBox") || $("div").hasClass("i_general_box_container") || $("div").hasClass("i_general_box_message_notifications_container")) {
                    $(".stickersContainer , .emojiBox , .emojiBoxC , .i_general_box_container , .i_general_box_message_notifications_container").remove();
                }
            }
        });
    });
    /*Get Stickers*/
    $(document).on("click", ".getStickers", function() {
        var type = 'stickers';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {},
            success: function(response) {
                if (!$(".stickersContainer")[0]) {
                    $(".getStickers" + ID).append(response);
                } else {
                    $(".stickersContainer").remove();
                }
                if ($("div").hasClass("emojiBox") || $("div").hasClass("emojiBoxC") || $("div").hasClass("i_general_box_container") || $("div").hasClass("i_general_box_message_notifications_container") || $("div").hasClass("i_general_box_notifications_container")) {
                    $(".emojiBoxC , .emojiBox , .i_general_box_message_notifications_container , .i_general_box_notifications_container , .i_general_box_container").remove();
                }
            }
        });
    });
    /*Get Stickers*/
    $(document).on("click", ".getGifs", function() {
        var type = 'gifList';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {},
            success: function(response) {
                if (!$(".stickersContainer")[0]) {
                    $(".getStickers" + ID).append(response);
                } else {
                    $(".stickersContainer").remove();
                }
                if ($("div").hasClass("emojiBox") || $("div").hasClass("emojiBoxC") || $("div").hasClass("i_general_box_container") || $("div").hasClass("i_general_box_message_notifications_container") || $("div").hasClass("i_general_box_notifications_container")) {
                    $(".emojiBoxC , .emojiBox , .i_general_box_message_notifications_container , .i_general_box_notifications_container , .i_general_box_container").remove();
                }
            }
        });
    });

    function getGiphySearchSelectors($searchUI) {
        var $container = $searchUI.closest(".stickersContainer, .Message_stickersContainer");
        var isConversation = $container.hasClass("Message_stickersContainer");
        return {
            $container: $container,
            containerSelector: isConversation ? ".Message_stickersContainer" : ".stickersContainer",
            resultsSelector: isConversation ? ".giphy_results_container_conversation" : ".giphy_results_container"
        };
    }

    function getGiphySearchRequestValue($searchUI, attributeName, dataKey) {
        return $searchUI.attr(attributeName) || $searchUI.data(dataKey) || '';
    }

    function getGiphySearchUIFromElement(sourceEl) {
        if (!sourceEl) {
            return $();
        }
        var $source = $(sourceEl);
        return $source.hasClass("giphy_search_form") ? $source : $source.closest(".giphy_search_form");
    }

    function runGiphySearch($searchUI, forceRequest) {
        var type = getGiphySearchRequestValue($searchUI, "data-request-type", "requestType");
        var ID = getGiphySearchRequestValue($searchUI, "data-id", "id");
        var query = $.trim($searchUI.find(".giphy_search_input").val() || '');
        var selectors = getGiphySearchSelectors($searchUI);
        var $container = selectors.$container;
        var $results = $container.find(selectors.resultsSelector).first();
        var $button = $searchUI.find(".giphy_search_btn");
        var activeRequest = $searchUI.data("giphyActiveRequest");

        if (!type || !ID || !$container.length) {
            return;
        }

        if (!forceRequest && $searchUI.data("lastSubmittedQuery") === query) {
            return;
        }

        if (activeRequest && activeRequest.readyState !== 4) {
            activeRequest.abort();
        }

        $searchUI.data("lastSubmittedQuery", query);

        var request = $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: {
                f: type,
                id: ID,
                q: query
            },
            beforeSend: function() {
                $button.prop("disabled", true);
                if ($results.length) {
                    $results.css("opacity", "0.45");
                }
            },
            success: function(response) {
                var $parsed = $("<div>").html($.trim(response || ''));
                var $newContainer = $parsed.filter(selectors.containerSelector).first();
                if (!$newContainer.length) {
                    $newContainer = $parsed.find(selectors.containerSelector).first();
                }
                var $newResults = $newContainer.find(selectors.resultsSelector).first();
                var $currentResults = $container.find(selectors.resultsSelector).first();

                if ($newResults.length && $currentResults.length) {
                    $currentResults.html($newResults.html()).css("opacity", "1");
                } else if ($newContainer.length) {
                    $container.replaceWith($newContainer);
                } else if ($currentResults.length) {
                    $currentResults.html(response).css("opacity", "1");
                }
            },
            error: function(xhr, status) {
                if (status === "abort" || !$results.length) {
                    return;
                }
                $results.css("opacity", "1");
            },
            complete: function(xhr, status) {
                if ($searchUI.data("giphyActiveRequest") === request) {
                    $searchUI.removeData("giphyActiveRequest");
                    if ($results.length) {
                        $results.css("opacity", "1");
                    }
                    $button.prop("disabled", false);
                }
            }
        });

        $searchUI.data("giphyActiveRequest", request);
    }

    window.dizzyRunGiphySearch = function(sourceEl, forceRequest) {
        var $searchUI = getGiphySearchUIFromElement(sourceEl);
        if (!$searchUI.length) {
            return false;
        }
        var existingTimer = $searchUI.data("giphySearchTimer");
        if (existingTimer) {
            clearTimeout(existingTimer);
        }
        runGiphySearch($searchUI, !!forceRequest);
        return false;
    };

    window.dizzyHandleGiphyInput = function(sourceEl) {
        var $searchUI = getGiphySearchUIFromElement(sourceEl);
        if (!$searchUI.length) {
            return false;
        }
        var existingTimer = $searchUI.data("giphySearchTimer");
        if (existingTimer) {
            clearTimeout(existingTimer);
        }
        $searchUI.data("giphySearchTimer", setTimeout(function() {
            runGiphySearch($searchUI, false);
        }, 300));
        return false;
    };

    window.dizzyHandleGiphyKeydown = function(sourceEl, event) {
        var e = event || window.event;
        var keyCode = (e && (e.which || e.keyCode)) || 0;
        var $searchUI = getGiphySearchUIFromElement(sourceEl);
        if (!$searchUI.length) {
            return true;
        }
        var existingTimer = $searchUI.data("giphySearchTimer");
        if (keyCode === 13) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }
            if (existingTimer) {
                clearTimeout(existingTimer);
            }
            runGiphySearch($searchUI, true);
            return false;
        }
        if (keyCode === 27) {
            if (e) {
                e.preventDefault();
            }
            $(sourceEl).val('');
            if (existingTimer) {
                clearTimeout(existingTimer);
            }
            $searchUI.data("lastSubmittedQuery", null);
            runGiphySearch($searchUI, true);
            return false;
        }
        return true;
    };

    $(document).on("input", ".giphy_search_input", function() {
        window.dizzyHandleGiphyInput(this);
    });
    $(document).on("click mousedown touchstart", ".giphy_search_form, .giphy_search_input, .giphy_search_btn", function(e) {
        e.stopPropagation();
    });
    $(document).on("click", ".giphy_search_btn", function(e) {
        e.preventDefault();
        e.stopPropagation();
        window.dizzyRunGiphySearch(this, true);
        return false;
    });
    $(document).on("keydown", ".giphy_search_input", function(e) {
        return window.dizzyHandleGiphyKeydown(this, e);
    });
    $(document).on("keyup search change", ".giphy_search_input", function(e) {
        var keyCode = e.which || e.keyCode || 0;
        if (keyCode === 13 || keyCode === 27) {
            return;
        }
        window.dizzyHandleGiphyInput(this);
    });
    $(document).on("click", ".g_feed", function() {
        var get = $(this).attr("data-get");
        var type = $(this).attr("data-type");
        var data = 'f=' + get;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {
                $("#moreType").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response != '404') {
                    $("#moreType").attr("data-type", type);
                    const $newContent = $(response);
                    $("#moreType").html('').append($newContent);
                    // Re-init dynamic plugins for newly injected content
                    reInitPostPlugins($newContent);
                    initImageBackgrounds('.i_post_image_swip_wrapper', $newContent);
                    initSuggestedCreatorsSwiper($newContent);
                    initImageSuggestedBackgrounds();
                    $(".mobile_left").removeClass("leftStickyActive");
                    $(".is_mobile").removeClass("svg_active_icon");
                    $(".i_postFormContainer").hide();
                }
            }
        });
    });
    const initializedGalleries = new Set();
    const lightGalleryOptions = {
      mode: 'lg-fade',
      cssEasing: 'cubic-bezier(0.25, 0, 0.25, 1)',
      download: false,
      share: false
    };
    const lightGalleryVideoJsOptions = {
      controls: true,
      autoplay: false,
      preload: 'auto',
      fluid: true,
      responsive: true,
      controlBar: {
        remainingTimeDisplay: false,
        pictureInPictureToggle: true,
        volumePanel: { inline: false }
      }
    };

    function galleryNeedsVideoJs($gallery) {
      if (!$gallery || !$gallery.length) {
        return false;
      }
      if (window.dizzyVideoJsLoader && typeof window.dizzyVideoJsLoader.galleryNeedsVideoJs === 'function') {
        return window.dizzyVideoJsLoader.galleryNeedsVideoJs($gallery.get(0));
      }
      return $gallery.find('video, .lg-html5, .video-js').length > 0;
    }

    function tryAutoPlayCurrentLgVideo() {
      const $video = $('.lg-outer .lg-current .lg-html5').first();
      if (!$video.length) {
        return;
      }
      const videoEl = $video.get(0);
      if (!videoEl || typeof videoEl.play !== 'function') {
        return;
      }
      const playAttempt = videoEl.play();
      if (playAttempt && typeof playAttempt.catch === 'function') {
        playAttempt.catch(function () {
          if (!videoEl.muted) {
            videoEl.muted = true;
            const mutedRetry = videoEl.play();
            if (mutedRetry && typeof mutedRetry.catch === 'function') {
              mutedRetry.catch(function () {});
            }
          }
        });
      }
    }

    function bindGalleryAutoPlayHooks($gallery) {
      if (!$gallery || !$gallery.length || $gallery.data('lgAutoPlayHooked')) {
        return;
      }
      $gallery.data('lgAutoPlayHooked', true);
      $gallery.on('onAfterOpen.lg.dizzyAutoplay onAferAppendSlide.lg.dizzyAutoplay onAfterSlide.lg.dizzyAutoplay', function () {
        window.setTimeout(tryAutoPlayCurrentLgVideo, 40);
      });
    }

    function initLightGallerySafe($gallery, options) {
      if (
        !$gallery ||
        !$gallery.length ||
        $gallery.hasClass('lg-initialized') ||
        $gallery.data('lgInitPending')
      ) {
        return;
      }
      bindGalleryAutoPlayHooks($gallery);

      const mountGallery = function (forceNativePlayer) {
        const config = $.extend({}, options, {
          videojs: !forceNativePlayer && !!window.videojs,
          videojsOptions: lightGalleryVideoJsOptions
        });
        $gallery.lightGallery(config);
      };

      if (
        galleryNeedsVideoJs($gallery) &&
        window.dizzyVideoJsLoader &&
        typeof window.dizzyVideoJsLoader.ensure === 'function'
      ) {
        $gallery.data('lgInitPending', true);
        let mounted = false;
        const safeMount = function (forceNativePlayer) {
          if (mounted || $gallery.hasClass('lg-initialized')) {
            $gallery.removeData('lgInitPending');
            return;
          }
          mounted = true;
          mountGallery(forceNativePlayer);
          $gallery.removeData('lgInitPending');
        };
        const fallbackTimer = window.setTimeout(function () {
          // Never block gallery open while waiting on large Video.js payload.
          safeMount(true);
        }, 1200);
        window.dizzyVideoJsLoader.ensure().then(
          function () {
            window.clearTimeout(fallbackTimer);
            safeMount(false);
          },
          function () {
            window.clearTimeout(fallbackTimer);
            safeMount(true);
          }
        );
        return;
      }

      mountGallery(false);
    }

    function syncPlayerAccentToken() {
      if (!window.getComputedStyle) {
        return;
      }

      const nodes = document.querySelectorAll('.publish_btn, .alertBtnRight, .send_tip_btn, .form_btn');
      if (!nodes || !nodes.length) {
        return;
      }

      const isUsableColor = function (colorValue) {
        if (!colorValue) {
          return false;
        }
        const normalized = String(colorValue).trim().toLowerCase();
        if (!normalized || normalized === 'transparent') {
          return false;
        }
        const rgbaMatch = normalized.match(/^rgba\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*,\s*([0-9.]+)\s*\)$/);
        if (rgbaMatch && parseFloat(rgbaMatch[1]) <= 0.05) {
          return false;
        }
        return true;
      };

      let pickedColor = '';
      nodes.forEach(function (node) {
        if (pickedColor) {
          return;
        }
        const computed = window.getComputedStyle(node);
        const bg = computed ? computed.backgroundColor : '';
        if (isUsableColor(bg)) {
          pickedColor = bg;
        }
      });

      if (pickedColor) {
        document.documentElement.style.setProperty('--dizzy-player-accent', pickedColor);
      }
    }

    function normalizeInlineVideoSelector(rawSelector) {
      if (typeof rawSelector !== 'string') {
        return '';
      }
      let selector = rawSelector.trim();
      if (!selector) {
        return '';
      }
      if (selector.indexOf('&') !== -1 && typeof document !== 'undefined') {
        const decoder = document.createElement('textarea');
        decoder.innerHTML = selector;
        selector = (decoder.value || selector).trim();
      }
      selector = selector.replace(/^['"]+|['"]+$/g, '').trim();
      return selector;
    }

    function getSingleInlineVideoContext($playButton) {
      if (!$playButton || !$playButton.length) {
        return null;
      }
      const $wrapper = $playButton.closest('.i_post_u_images .i_post_image_swip_wrapper[data-html]');
      if (
        !$wrapper.length ||
        $wrapper.hasClass('inline_video_mode') ||
        $wrapper.attr('data-inline-video-active') === '1'
      ) {
        return null;
      }
      const videoSelector = normalizeInlineVideoSelector($wrapper.attr('data-html') || '');
      if (!videoSelector || videoSelector.charAt(0) !== '#') {
        return null;
      }
      const $gallery = $wrapper.parent('[id^="lightgallery"]');
      if (!$gallery.length) {
        return null;
      }
      const $galleryItems = $gallery.children('.i_post_image_swip_wrapper');
      const $realGalleryItems = $galleryItems.filter(function () {
        return !$(this).hasClass('swiper-slide-duplicate');
      });
      const itemCount = ($realGalleryItems.length || $galleryItems.length);
      if (itemCount !== 1) {
        return null;
      }
      return {
        $wrapper: $wrapper,
        videoSelector: videoSelector
      };
    }

    function createInlineVideoFromTemplate(videoSelector, posterUrl) {
      const $videoTemplate = $(videoSelector).first().find('video').first();
      if (!$videoTemplate.length) {
        return null;
      }

      const $inlineVideo = $('<video class="i_inline_video_player video-js vjs-default-skin dizzy-pro-video" controls preload="metadata" playsinline webkit-playsinline></video>');
      if (posterUrl) {
        $inlineVideo.attr('poster', posterUrl);
      }

      const $sources = $videoTemplate.find('source');
      if ($sources.length) {
        $sources.each(function () {
          const src = $(this).attr('src');
          if (!src) {
            return;
          }
          const $source = $('<source>');
          $source.attr('src', src);
          const type = $(this).attr('type');
          if (type) {
            $source.attr('type', type);
          }
          $inlineVideo.append($source);
        });
      } else {
        const src = $videoTemplate.attr('src');
        if (!src) {
          return null;
        }
        $inlineVideo.attr('src', src);
      }

      return $inlineVideo;
    }

    function autoPlayMediaElement(videoEl) {
      if (!videoEl || typeof videoEl.play !== 'function') {
        return;
      }
      const playAttempt = videoEl.play();
      if (playAttempt && typeof playAttempt.catch === 'function') {
        playAttempt.catch(function () {
          if (!videoEl.muted) {
            videoEl.muted = true;
            const mutedRetry = videoEl.play();
            if (mutedRetry && typeof mutedRetry.catch === 'function') {
              mutedRetry.catch(function () {});
            }
          }
        });
      }
    }

    function pauseMediaElement(videoEl, reason) {
      if (!videoEl || typeof videoEl.pause !== 'function') {
        return;
      }
      if (reason === 'out_of_view' || reason === 'other_video_started' || reason === 'tab_hidden') {
        videoEl.__dizzyAutoResumeOnVisible = '1';
      } else if (reason === 'manual') {
        videoEl.__dizzyAutoResumeOnVisible = '0';
      }
      videoEl.__dizzyPauseReason = reason || '';
      try {
        videoEl.pause();
      } catch (e) {}
    }

    function resolveInlinePlaybackVideo(videoEl) {
      if (!videoEl || typeof videoEl.closest !== 'function') {
        return null;
      }
      if (videoEl.matches && videoEl.matches('video.i_inline_video_player')) {
        return videoEl;
      }
      const $wrapper = videoEl.closest('.i_post_image_swip_wrapper.inline_video_mode');
      if (!$wrapper) {
        return null;
      }
      return $wrapper.querySelector('video.i_inline_video_player') || $wrapper.querySelector('video');
    }

    function getInlinePlaybackVideos() {
      return Array.prototype.slice.call(
        document.querySelectorAll('.i_post_image_swip_wrapper.inline_video_mode video')
      );
    }

    function getInlineGlobalMutedState() {
      if (typeof window.__dizzyInlineGlobalMuted !== 'boolean') {
        window.__dizzyInlineGlobalMuted = false;
      }
      return window.__dizzyInlineGlobalMuted;
    }

    function getInlineAudioLabels() {
      const lang = window.storyLang || {};
      const muteLabel = (typeof lang.audioMute === 'string') ? lang.audioMute.trim() : '';
      const unmuteLabel = (typeof lang.audioUnmute === 'string') ? lang.audioUnmute.trim() : '';
      return {
        mute: muteLabel || unmuteLabel || '',
        unmute: unmuteLabel || muteLabel || ''
      };
    }

    function getInlinePlayLabels() {
      const lang = window.storyLang || {};
      const playLabel = (typeof lang.audioPlay === 'string') ? lang.audioPlay.trim() : '';
      const pauseLabel = (typeof lang.audioPause === 'string') ? lang.audioPause.trim() : '';
      return {
        play: playLabel || pauseLabel || '',
        pause: pauseLabel || playLabel || ''
      };
    }

    function getInlineExpandLabels() {
      const lang = window.storyLang || {};
      const expandLabel = (typeof lang.videoExpand === 'string') ? lang.videoExpand.trim() : '';
      const closeLabel = (typeof lang.videoClose === 'string') ? lang.videoClose.trim() : '';
      return {
        expand: expandLabel || closeLabel || '',
        close: closeLabel || expandLabel || ''
      };
    }

    function getInlineExpandToggleIcon() {
      const lang = window.storyLang || {};
      const iconMarkup = (typeof lang.videoExpandIcon === 'string') ? lang.videoExpandIcon.trim() : '';
      if (iconMarkup) {
        return iconMarkup;
      }
      return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 9V3h6v2H5v4H3Zm16-4h-4V3h6v6h-2V5ZM5 15v4h4v2H3v-6h2Zm16 0h2v6h-6v-2h4v-4Z"></path></svg>';
    }

    function getInlineAudioToggleIcon(isMuted) {
      if (isMuted) {
        return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3.2v17.6c0 .8-.9 1.2-1.5.7L7.8 17H4a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2h3.8l4.7-4.5c.6-.5 1.5-.1 1.5.7ZM17.3 9.3a1 1 0 0 1 1.4 0L20 10.6l1.3-1.3a1 1 0 1 1 1.4 1.4L21.4 12l1.3 1.3a1 1 0 0 1-1.4 1.4L20 13.4l-1.3 1.3a1 1 0 0 1-1.4-1.4l1.3-1.3-1.3-1.3a1 1 0 0 1 0-1.4Z"></path></svg>';
      }
      return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 3.2v17.6c0 .8-.9 1.2-1.5.7L7.8 17H4a2 2 0 0 1-2-2v-6a2 2 0 0 1 2-2h3.8l4.7-4.5c.6-.5 1.5-.1 1.5.7Zm3.8 2.4a1 1 0 0 1 1.4 0A9 9 0 0 1 22 12a9 9 0 0 1-2.8 6.4 1 1 0 1 1-1.4-1.4A7 7 0 0 0 20 12a7 7 0 0 0-2.2-5 1 1 0 0 1 0-1.4Zm-3 3a1 1 0 0 1 1.4 0A4.9 4.9 0 0 1 17.8 12a4.9 4.9 0 0 1-1.6 3.4 1 1 0 1 1-1.4-1.4 2.9 2.9 0 0 0 1-2 2.9 2.9 0 0 0-1-2 1 1 0 0 1 0-1.4Z"></path></svg>';
    }

    function syncInlineAudioToggleButton(buttonEl, isMuted) {
      if (!buttonEl) {
        return;
      }
      const labels = getInlineAudioLabels();
      const muted = !!isMuted;
      const nextLabel = muted ? labels.unmute : labels.mute;
      buttonEl.setAttribute('data-muted', muted ? '1' : '0');
      if (nextLabel) {
        buttonEl.setAttribute('aria-label', nextLabel);
        buttonEl.setAttribute('title', nextLabel);
      } else {
        buttonEl.removeAttribute('aria-label');
        buttonEl.removeAttribute('title');
      }
      buttonEl.innerHTML = getInlineAudioToggleIcon(muted);
    }

    function applyInlineVideoMutedState(videoEl, isMuted) {
      if (!videoEl) {
        return;
      }
      const muted = !!isMuted;
      videoEl.muted = muted;
      videoEl.defaultMuted = muted;
      const player = videoEl.__dizzyVjsPlayer;
      if (player && typeof player.muted === 'function') {
        try {
          player.muted(muted);
        } catch (e) {}
      }
    }

    function setInlineGlobalMutedState(isMuted) {
      const muted = !!isMuted;
      window.__dizzyInlineGlobalMuted = muted;
      getInlinePlaybackVideos().forEach(function (videoNode) {
        applyInlineVideoMutedState(videoNode, muted);
      });
      document.querySelectorAll('.i_inline_audio_toggle').forEach(function (buttonNode) {
        syncInlineAudioToggleButton(buttonNode, muted);
      });
    }

    function syncInlineExpandToggleButton(buttonEl, isExpanded) {
      if (!buttonEl) {
        return;
      }
      const labels = getInlineExpandLabels();
      const expanded = !!isExpanded;
      const label = expanded ? labels.close : labels.expand;
      buttonEl.setAttribute('data-expanded', expanded ? '1' : '0');
      if (label) {
        buttonEl.setAttribute('aria-label', label);
        buttonEl.setAttribute('title', label);
      } else {
        buttonEl.removeAttribute('aria-label');
        buttonEl.removeAttribute('title');
      }
      buttonEl.innerHTML = getInlineExpandToggleIcon();
    }

    function refreshInlineExpandedBodyLock() {
      const hasExpanded = !!document.querySelector('.i_inline_video_shell.is-inline-expanded');
      if (hasExpanded) {
        document.documentElement.classList.add('i_inline_video_modal_open');
        document.body.classList.add('i_inline_video_modal_open');
      } else {
        document.documentElement.classList.remove('i_inline_video_modal_open');
        document.body.classList.remove('i_inline_video_modal_open');
      }
    }

    function getInlineNativeFullscreenElement() {
      return document.fullscreenElement ||
        document.webkitFullscreenElement ||
        document.mozFullScreenElement ||
        document.msFullscreenElement ||
        null;
    }

    function isInlineShellNativeFullscreen(shellEl) {
      if (!shellEl || !shellEl.classList || !shellEl.classList.contains('i_inline_video_shell')) {
        return false;
      }
      const fullscreenEl = getInlineNativeFullscreenElement();
      if (!fullscreenEl) {
        return false;
      }
      return fullscreenEl === shellEl || shellEl.contains(fullscreenEl);
    }

    function requestInlineNativeFullscreen(targetEl) {
      if (!targetEl) {
        return false;
      }
      const requestFullscreen = targetEl.requestFullscreen ||
        targetEl.webkitRequestFullscreen ||
        targetEl.webkitEnterFullscreen ||
        targetEl.mozRequestFullScreen ||
        targetEl.msRequestFullscreen;
      if (typeof requestFullscreen !== 'function') {
        return false;
      }
      try {
        requestFullscreen.call(targetEl);
        return true;
      } catch (e) {
        return false;
      }
    }

    function exitInlineNativeFullscreen() {
      const exitFullscreen = document.exitFullscreen ||
        document.webkitExitFullscreen ||
        document.webkitCancelFullScreen ||
        document.mozCancelFullScreen ||
        document.msExitFullscreen;
      if (typeof exitFullscreen !== 'function') {
        return false;
      }
      try {
        exitFullscreen.call(document);
        return true;
      } catch (e) {
        return false;
      }
    }

    function refreshInlineExpandButtons() {
      document.querySelectorAll('.i_inline_video_shell').forEach(function (shellNode) {
        const buttonNode = shellNode.querySelector('.i_inline_expand_toggle');
        if (!buttonNode) {
          return;
        }
        const isExpanded = shellNode.classList.contains('is-inline-expanded') || isInlineShellNativeFullscreen(shellNode);
        syncInlineExpandToggleButton(buttonNode, isExpanded);
      });
    }

    function isInlineControlsStickyEnabled() {
      const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
      if (viewportWidth < 992) {
        return false;
      }
      if (window.matchMedia && window.matchMedia('(pointer: coarse)').matches) {
        return false;
      }
      return true;
    }

    function clearInlineControlsSticky(shellEl) {
      if (!shellEl) {
        return;
      }
      shellEl.classList.remove('i_inline_controls_fixed');
      shellEl.style.removeProperty('--i-inline-controls-fixed-top');
      shellEl.style.removeProperty('--i-inline-audio-fixed-top');
      shellEl.style.removeProperty('--i-inline-audio-fixed-left');
      shellEl.style.removeProperty('--i-inline-expand-fixed-top');
      shellEl.style.removeProperty('--i-inline-expand-fixed-left');
    }

    function updateInlineStickyControls() {
      const shellNodes = document.querySelectorAll('.i_post_image_swip_wrapper.inline_video_mode .i_inline_video_shell');
      if (!shellNodes.length) {
        return;
      }

      const stickyEnabled = isInlineControlsStickyEnabled();
      const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
      const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
      const clampValue = function (value, min, max) {
        return Math.min(Math.max(value, min), max);
      };
      let stickyTop = 90;
      const headerNode = document.querySelector('.header');
      if (headerNode && typeof headerNode.getBoundingClientRect === 'function') {
        const headerRect = headerNode.getBoundingClientRect();
        stickyTop = Math.max(12, Math.round((headerRect.bottom || 0) + 8));
      }

      shellNodes.forEach(function (shellEl) {
        if (
          !stickyEnabled ||
          !shellEl ||
          shellEl.classList.contains('is-inline-expanded') ||
          isInlineShellNativeFullscreen(shellEl)
        ) {
          clearInlineControlsSticky(shellEl);
          return;
        }

        const wrapper = shellEl.closest('.i_post_image_swip_wrapper.inline_video_mode');
        if (!wrapper || typeof wrapper.getBoundingClientRect !== 'function') {
          clearInlineControlsSticky(shellEl);
          return;
        }

        const rect = wrapper.getBoundingClientRect();
        const inViewport = (
          rect.bottom > 0 &&
          rect.top < viewportHeight &&
          rect.right > 0 &&
          rect.left < viewportWidth
        );
        const controlsHeight = 96;
        const shouldFix = inViewport && rect.top < stickyTop && (rect.bottom - 12) > (stickyTop + controlsHeight);
        if (!shouldFix) {
          clearInlineControlsSticky(shellEl);
          return;
        }

        const alreadyFixed = shellEl.classList.contains('i_inline_controls_fixed');
        if (!alreadyFixed) {
          const audioButton = shellEl.querySelector('.i_inline_audio_toggle');
          const expandButton = shellEl.querySelector('.i_inline_expand_toggle');
          if (audioButton && typeof audioButton.getBoundingClientRect === 'function') {
            const audioRect = audioButton.getBoundingClientRect();
            const minTop = Math.max(12, stickyTop);
            const maxTop = Math.max(minTop, viewportHeight - controlsHeight);
            const maxLeft = Math.max(0, viewportWidth - 52);
            const audioFixedTop = clampValue(Math.round(audioRect.top), minTop, maxTop);
            const audioFixedLeft = clampValue(Math.round(audioRect.left), 0, maxLeft);
            shellEl.style.setProperty('--i-inline-controls-fixed-top', audioFixedTop + 'px');
            shellEl.style.setProperty('--i-inline-audio-fixed-top', audioFixedTop + 'px');
            shellEl.style.setProperty('--i-inline-audio-fixed-left', audioFixedLeft + 'px');

            if (expandButton && typeof expandButton.getBoundingClientRect === 'function') {
              const expandRect = expandButton.getBoundingClientRect();
              const expandFixedTop = clampValue(Math.round(expandRect.top), (minTop + 46), (maxTop + 46));
              const expandFixedLeft = clampValue(Math.round(expandRect.left), 0, maxLeft);
              shellEl.style.setProperty('--i-inline-expand-fixed-top', expandFixedTop + 'px');
              shellEl.style.setProperty('--i-inline-expand-fixed-left', expandFixedLeft + 'px');
            } else {
              shellEl.style.setProperty('--i-inline-expand-fixed-top', (audioFixedTop + 46) + 'px');
              shellEl.style.setProperty('--i-inline-expand-fixed-left', audioFixedLeft + 'px');
            }
          } else {
            const fallbackLeft = Math.max(0, Math.round(rect.right - 52));
            shellEl.style.setProperty('--i-inline-controls-fixed-top', stickyTop + 'px');
            shellEl.style.setProperty('--i-inline-audio-fixed-top', stickyTop + 'px');
            shellEl.style.setProperty('--i-inline-audio-fixed-left', fallbackLeft + 'px');
            shellEl.style.setProperty('--i-inline-expand-fixed-top', (stickyTop + 46) + 'px');
            shellEl.style.setProperty('--i-inline-expand-fixed-left', fallbackLeft + 'px');
          }
        }
        shellEl.classList.add('i_inline_controls_fixed');
      });
    }

    function setInlineShellExpandedState(shellEl, shouldExpand) {
      const expand = !!shouldExpand;
      const hasTargetShell = !!(
        shellEl &&
        shellEl.classList &&
        shellEl.classList.contains('i_inline_video_shell')
      );
      document.querySelectorAll('.i_inline_video_shell.is-inline-expanded').forEach(function (shellNode) {
        if (hasTargetShell && shellNode === shellEl && expand) {
          return;
        }
        shellNode.classList.remove('is-inline-expanded');
        const buttonNode = shellNode.querySelector('.i_inline_expand_toggle');
        if (buttonNode) {
          syncInlineExpandToggleButton(buttonNode, false);
        }
      });
      if (!hasTargetShell) {
        refreshInlineExpandedBodyLock();
        updateInlineStickyControls();
        return;
      }
      shellEl.classList.toggle('is-inline-expanded', expand);
      const toggleButton = shellEl.querySelector('.i_inline_expand_toggle');
      if (toggleButton) {
        syncInlineExpandToggleButton(toggleButton, expand);
      }
      refreshInlineExpandedBodyLock();
      updateInlineStickyControls();
    }

    function pauseOtherInlineVideos(activeVideoEl) {
      const activeVideo = resolveInlinePlaybackVideo(activeVideoEl) || activeVideoEl;
      if (!activeVideo) {
        return;
      }
      const activeWrapper = activeVideo.closest
        ? activeVideo.closest('.i_post_image_swip_wrapper.inline_video_mode')
        : null;
      getInlinePlaybackVideos().forEach(function (videoNode) {
        const wrapper = videoNode && videoNode.closest
          ? videoNode.closest('.i_post_image_swip_wrapper.inline_video_mode')
          : null;
        if (activeWrapper && wrapper && wrapper === activeWrapper) {
          return;
        }
        pauseMediaElement(videoNode, 'other_video_started');
        const syncTarget = resolveInlinePlaybackVideo(videoNode) || videoNode;
        if (typeof syncTarget.__dizzyInlineSyncOverlay === 'function') {
          syncTarget.__dizzyInlineSyncOverlay();
        }
      });
    }

    function hasOtherPlayingInlineVideos(activeVideoEl) {
      const activeVideo = resolveInlinePlaybackVideo(activeVideoEl) || activeVideoEl;
      if (!activeVideo) {
        return false;
      }
      const activeWrapper = activeVideo.closest
        ? activeVideo.closest('.i_post_image_swip_wrapper.inline_video_mode')
        : null;
      return getInlinePlaybackVideos().some(function (videoNode) {
        if (!videoNode || videoNode.paused || videoNode.ended) {
          return false;
        }
        const wrapper = videoNode.closest
          ? videoNode.closest('.i_post_image_swip_wrapper.inline_video_mode')
          : null;
        if (activeWrapper && wrapper && wrapper === activeWrapper) {
          return false;
        }
        return true;
      });
    }

    function isInlineVideoVisibleEnough(videoEl, minRatio) {
      if (!videoEl || typeof videoEl.getBoundingClientRect !== 'function') {
        return true;
      }
      const rect = videoEl.getBoundingClientRect();
      const width = Number(rect.width) || Math.max(0, Number(rect.right) - Number(rect.left));
      const height = Number(rect.height) || Math.max(0, Number(rect.bottom) - Number(rect.top));
      if (!width || !height) {
        return false;
      }
      const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
      const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
      const visibleWidth = Math.max(0, Math.min(rect.right, viewportWidth) - Math.max(rect.left, 0));
      const visibleHeight = Math.max(0, Math.min(rect.bottom, viewportHeight) - Math.max(rect.top, 0));
      const visibleArea = visibleWidth * visibleHeight;
      const totalArea = width * height;
      if (!totalArea) {
        return false;
      }
      const ratio = visibleArea / totalArea;
      return ratio >= (Number(minRatio) || 0.35);
    }

    function getInlineVideoVisibleRatio(videoEl) {
      if (!videoEl || typeof videoEl.getBoundingClientRect !== 'function') {
        return 0;
      }
      const rect = videoEl.getBoundingClientRect();
      const width = Number(rect.width) || Math.max(0, Number(rect.right) - Number(rect.left));
      const height = Number(rect.height) || Math.max(0, Number(rect.bottom) - Number(rect.top));
      if (!width || !height) {
        return 0;
      }
      const viewportWidth = window.innerWidth || document.documentElement.clientWidth || 0;
      const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
      const visibleWidth = Math.max(0, Math.min(rect.right, viewportWidth) - Math.max(rect.left, 0));
      const visibleHeight = Math.max(0, Math.min(rect.bottom, viewportHeight) - Math.max(rect.top, 0));
      const visibleArea = visibleWidth * visibleHeight;
      const totalArea = width * height;
      if (!totalArea) {
        return 0;
      }
      return visibleArea / totalArea;
    }

    function pickPrimaryVisibleInlineVideo(videos) {
      if (!videos || !videos.length) {
        return null;
      }
      const viewportHeight = window.innerHeight || document.documentElement.clientHeight || 0;
      const viewportCenterY = viewportHeight / 2;
      let bestVideo = null;
      let bestRatio = 0;
      let bestCenterDistance = Number.POSITIVE_INFINITY;
      videos.forEach(function (videoNode) {
        const ratio = getInlineVideoVisibleRatio(videoNode);
        if (ratio <= 0) {
          return;
        }
        const rect = videoNode.getBoundingClientRect();
        const centerY = (rect.top + rect.bottom) / 2;
        const centerDistance = Math.abs(centerY - viewportCenterY);
        if (
          ratio > bestRatio + 0.01 ||
          (Math.abs(ratio - bestRatio) <= 0.01 && centerDistance < bestCenterDistance)
        ) {
          bestVideo = videoNode;
          bestRatio = ratio;
          bestCenterDistance = centerDistance;
        }
      });
      if (!bestVideo || bestRatio < 0.15) {
        return null;
      }
      return bestVideo;
    }

    function pauseOutOfViewInlineVideos() {
      const videos = getInlinePlaybackVideos().filter(function (videoNode) {
        return !!videoNode && !videoNode.ended;
      });
      if (!videos.length) {
        return;
      }

      const primaryVideo = pickPrimaryVisibleInlineVideo(videos);
      videos.forEach(function (videoNode) {
        if (primaryVideo && videoNode === primaryVideo) {
          return;
        }
        if (!videoNode.paused) {
          pauseMediaElement(videoNode, 'out_of_view');
        }
      });

      if (
        primaryVideo &&
        primaryVideo.paused &&
        primaryVideo.__dizzyAutoResumeOnVisible === '1'
      ) {
        applyInlineVideoMutedState(primaryVideo, getInlineGlobalMutedState());
        autoPlayMediaElement(primaryVideo);
      }

      videos.forEach(function (videoNode) {
        const syncTarget = resolveInlinePlaybackVideo(videoNode) || videoNode;
        if (typeof syncTarget.__dizzyInlineSyncOverlay === 'function') {
          syncTarget.__dizzyInlineSyncOverlay();
        }
      });
    }

    function bindInlineVideoGuards() {
      if (window.__dizzyInlineVideoGuardsBound) {
        return;
      }
      window.__dizzyInlineVideoGuardsBound = true;
      let scheduled = false;
      const scheduleVisibilityCheck = function () {
        if (scheduled) {
          return;
        }
        scheduled = true;
        const run = function () {
          scheduled = false;
          pauseOutOfViewInlineVideos();
          updateInlineStickyControls();
        };
        if (typeof window.requestAnimationFrame === 'function') {
          window.requestAnimationFrame(run);
        } else {
          window.setTimeout(run, 16);
        }
      };
      document.addEventListener('play', function (event) {
        const activeVideo = resolveInlinePlaybackVideo(event && event.target);
        if (!activeVideo) {
          return;
        }
        pauseOtherInlineVideos(activeVideo);
        if (typeof activeVideo.__dizzyInlineSyncOverlay === 'function') {
          activeVideo.__dizzyInlineSyncOverlay();
        }
      }, true);
      window.addEventListener('scroll', scheduleVisibilityCheck, { passive: true });
      window.addEventListener('resize', scheduleVisibilityCheck, { passive: true });
      window.addEventListener('orientationchange', scheduleVisibilityCheck);
      document.addEventListener('visibilitychange', function () {
        if (document.hidden) {
          getInlinePlaybackVideos().forEach(function (videoNode) {
            if (!videoNode || videoNode.paused || videoNode.ended) {
              return;
            }
            pauseMediaElement(videoNode, 'tab_hidden');
            const syncTarget = resolveInlinePlaybackVideo(videoNode) || videoNode;
            if (typeof syncTarget.__dizzyInlineSyncOverlay === 'function') {
              syncTarget.__dizzyInlineSyncOverlay();
            }
          });
        } else {
          scheduleVisibilityCheck();
        }
      });
      document.addEventListener('keydown', function (event) {
        const key = event && (event.key || event.code || '');
        if (key !== 'Escape' && key !== 'Esc') {
          return;
        }
        const hasInlineExpanded = !!document.querySelector('.i_inline_video_shell.is-inline-expanded');
        const nativeFullscreenEl = getInlineNativeFullscreenElement();
        const isInlineNativeFullscreen = !!(
          nativeFullscreenEl &&
          typeof nativeFullscreenEl.closest === 'function' &&
          nativeFullscreenEl.closest('.i_inline_video_shell')
        );
        if (!hasInlineExpanded && !isInlineNativeFullscreen) {
          return;
        }
        exitInlineNativeFullscreen();
        setInlineShellExpandedState(null, false);
      });
      document.addEventListener('fullscreenchange', refreshInlineExpandButtons);
      document.addEventListener('webkitfullscreenchange', refreshInlineExpandButtons);
      document.addEventListener('fullscreenchange', updateInlineStickyControls);
      document.addEventListener('webkitfullscreenchange', updateInlineStickyControls);
    }

    function ensureInlineVideoVisibilityObserver() {
      if (window.__dizzyInlineVideoVisibilityObserver) {
        return window.__dizzyInlineVideoVisibilityObserver;
      }
      if (typeof window.IntersectionObserver !== 'function') {
        return null;
      }
      window.__dizzyInlineVideoVisibilityObserver = new window.IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          const targetVideo = entry && entry.target;
          if (!targetVideo || !targetVideo.classList || !targetVideo.classList.contains('i_inline_video_player')) {
            return;
          }
          const visibleEnough = !!entry.isIntersecting && entry.intersectionRatio >= 0.35;
          if (visibleEnough || targetVideo.paused || targetVideo.ended) {
            return;
          }
          pauseMediaElement(targetVideo, 'out_of_view');
          if (typeof targetVideo.__dizzyInlineSyncOverlay === 'function') {
            targetVideo.__dizzyInlineSyncOverlay();
          }
        });
      }, {
        threshold: [0, 0.1, 0.35, 0.65]
      });
      return window.__dizzyInlineVideoVisibilityObserver;
    }

    function observeInlineVideoVisibility(videoEl) {
      if (!videoEl || videoEl.__dizzyInlineObserved === '1') {
        return;
      }
      const observer = ensureInlineVideoVisibilityObserver();
      if (!observer) {
        return;
      }
      try {
        observer.observe(videoEl);
        videoEl.__dizzyInlineObserved = '1';
      } catch (e) {}
    }

    function initVideoJsInlinePlayer(videoEl) {
      if (
        !videoEl ||
        videoEl.getAttribute('data-dizzy-vjs-ready') === '1' ||
        typeof window.videojs !== 'function'
      ) {
        return false;
      }

      videoEl.setAttribute('data-dizzy-vjs-ready', '1');
      let currentTime = 0;
      let wasPaused = true;
      let wasMuted = false;

      try {
        currentTime = videoEl.currentTime || 0;
        wasPaused = videoEl.paused;
        wasMuted = !!videoEl.muted;
      } catch (e) {}

        try {
            const player = window.videojs(videoEl, {
          controls: true,
          autoplay: false,
          preload: 'auto',
          fluid: false,
          responsive: false,
          fill: true,
          controlBar: {
            remainingTimeDisplay: false,
            pictureInPictureToggle: true,
            volumePanel: { inline: false }
          }
        }, function () {
          if (currentTime > 0) {
            try { this.currentTime(currentTime); } catch (e) {}
          }
          if (typeof this.muted === 'function') {
            this.muted(wasMuted);
          }
          if (!wasPaused) {
            const resume = this.play();
            if (resume && typeof resume.catch === 'function') {
              resume.catch(function () {});
            }
          }
        });
        if (player && typeof player.addClass === 'function') {
          player.addClass('dizzy-pro-video');
        }
        videoEl.__dizzyVjsPlayer = player || null;
        return true;
      } catch (e) {
        videoEl.removeAttribute('data-dizzy-vjs-ready');
        return false;
      }
    }

    function activateSingleInlineVideo(context) {
      if (!context || !context.$wrapper || !context.$wrapper.length) {
        return false;
      }
      bindInlineVideoGuards();

      const $wrapper = context.$wrapper;
      const isFullSingleLayout = $wrapper.closest('.i_image_one_full').length > 0;
      const posterUrl = ($wrapper.attr('data-bg') || $wrapper.attr('data-img') || '').trim();
      const previewImageEl = $wrapper.find('.i_p_image').get(0) || null;
      const originalPlayButtonMarkup = (($wrapper.find('.playbutton').first().html() || '') + '').trim();
      const $inlineVideo = createInlineVideoFromTemplate(context.videoSelector, posterUrl);
      if (!$inlineVideo || !$inlineVideo.length) {
        return false;
      }

      const resolveRatioPercent = function (width, height) {
        if (!isFullSingleLayout) {
          return 0;
        }
        const w = Number(width) || 0;
        const h = Number(height) || 0;
        if (!w || !h) {
          return 0;
        }
        const ratioPercent = (h / w) * 100;
        return isFinite(ratioPercent) && ratioPercent > 0 ? ratioPercent : 0;
      };
      const applyInlineRatio = function (width, height) {
        const ratioPercent = resolveRatioPercent(width, height);
        if (ratioPercent > 0) {
          $wrapper.css('--inline-video-padding', ratioPercent.toFixed(4) + '%');
        }
        return ratioPercent;
      };
      let lockedPreviewRatio = 0;
      if (previewImageEl) {
        lockedPreviewRatio = applyInlineRatio(
          previewImageEl.naturalWidth || previewImageEl.videoWidth || previewImageEl.width,
          previewImageEl.naturalHeight || previewImageEl.videoHeight || previewImageEl.height
        );
      }

      $wrapper.attr('data-inline-video-active', '1').addClass('inline_video_mode');
      if (isFullSingleLayout) {
        $wrapper.addClass('inline_video_full_mode');
      }
      $wrapper.attr('data-inline-video-source', context.videoSelector);
      $wrapper.removeAttr('data-html').removeAttr('data-poster').removeAttr('data-src');
      $wrapper.find('.playbutton, .i_p_image').remove();
      $wrapper.off('click.lgcustom click.lg');
      const $shell = $('<div class="i_inline_video_shell"></div>').append($inlineVideo);
      const $inlineAudioToggle = $('<button type="button" class="i_inline_audio_toggle"></button>');
      const $inlineExpandToggle = $('<button type="button" class="i_inline_expand_toggle"></button>');
      const $inlinePlayOverlay = $('<div class="i_inline_play_overlay is-visible" role="button" tabindex="0"></div>');
      const playLabels = getInlinePlayLabels();
      if (originalPlayButtonMarkup) {
        $inlinePlayOverlay.html(originalPlayButtonMarkup);
      } else {
        $inlinePlayOverlay.html('<span class="i_inline_play_fallback" aria-hidden="true">&#9658;</span>');
      }
      $shell.append($inlineAudioToggle);
      $shell.append($inlineExpandToggle);
      $shell.append($inlinePlayOverlay);
      $wrapper.append($shell);

      const videoEl = $inlineVideo.get(0);
      if (!videoEl || typeof videoEl.play !== 'function') {
        return true;
      }
      videoEl.__dizzyAutoResumeOnVisible = '0';
      applyInlineVideoMutedState(videoEl, getInlineGlobalMutedState());
      syncInlineAudioToggleButton($inlineAudioToggle.get(0), getInlineGlobalMutedState());
      syncInlineExpandToggleButton($inlineExpandToggle.get(0), false);
      const syncInlinePlayOverlay = function () {
        const overlayLabel = (videoEl.paused || videoEl.ended) ? playLabels.play : playLabels.pause;
        if (overlayLabel) {
          $inlinePlayOverlay.attr('aria-label', overlayLabel);
          $inlinePlayOverlay.attr('title', overlayLabel);
        } else {
          $inlinePlayOverlay.removeAttr('aria-label');
          $inlinePlayOverlay.removeAttr('title');
        }
        if (videoEl.paused || videoEl.ended) {
          $inlinePlayOverlay.addClass('is-visible');
        } else {
          $inlinePlayOverlay.removeClass('is-visible');
        }
      };
      const triggerInlineAudioToggle = function (event) {
        if (event) {
          event.preventDefault();
          event.stopPropagation();
        }
        const nextMuted = !getInlineGlobalMutedState();
        setInlineGlobalMutedState(nextMuted);
        applyInlineVideoMutedState(videoEl, nextMuted);
      };
      const triggerInlineExpandToggle = function (event) {
        if (event) {
          event.preventDefault();
          event.stopPropagation();
        }
        const shellEl = $shell.get(0);
        if (!shellEl) {
          return;
        }
        const nativeExpanded = isInlineShellNativeFullscreen(shellEl);
        if (nativeExpanded) {
          exitInlineNativeFullscreen();
          syncInlineExpandToggleButton($inlineExpandToggle.get(0), false);
          updateInlineStickyControls();
          return;
        }
        const cssExpanded = shellEl.classList.contains('is-inline-expanded');
        if (cssExpanded) {
          setInlineShellExpandedState(shellEl, false);
          return;
        }
        const requestedVideoFullscreen = requestInlineNativeFullscreen(videoEl);
        const requestedShellFullscreen = requestedVideoFullscreen
          ? false
          : requestInlineNativeFullscreen(shellEl);
        if (!requestedVideoFullscreen && !requestedShellFullscreen) {
          setInlineShellExpandedState(shellEl, true);
        } else {
          syncInlineExpandToggleButton($inlineExpandToggle.get(0), true);
          updateInlineStickyControls();
          window.setTimeout(function () {
            if (!isInlineShellNativeFullscreen(shellEl)) {
              setInlineShellExpandedState(shellEl, true);
            }
          }, 260);
        }
      };
      const triggerInlinePlay = function (event) {
        if (event) {
          event.preventDefault();
          event.stopPropagation();
        }
        updateInlineStickyControls();
        applyInlineVideoMutedState(videoEl, getInlineGlobalMutedState());
        pauseOtherInlineVideos(videoEl);
        autoPlayMediaElement(videoEl);
      };
      const handleInlineVideoPlay = function () {
        videoEl.__dizzyAutoResumeOnVisible = '1';
        syncInlineAudioToggleButton($inlineAudioToggle.get(0), getInlineGlobalMutedState());
        pauseOtherInlineVideos(videoEl);
        syncInlinePlayOverlay();
        updateInlineStickyControls();
      };
      const handleInlineVideoPause = function () {
        if (!videoEl.__dizzyPauseReason) {
          videoEl.__dizzyAutoResumeOnVisible = '0';
        }
        videoEl.__dizzyPauseReason = '';
        syncInlinePlayOverlay();
      };
      $inlineAudioToggle.on('click', triggerInlineAudioToggle);
      $inlineAudioToggle.on('keydown', function (event) {
        const key = event && (event.key || event.code || '');
        if (key === 'Enter' || key === ' ' || key === 'Spacebar' || key === 'Space') {
          triggerInlineAudioToggle(event);
        }
      });
      $inlineExpandToggle.on('click', triggerInlineExpandToggle);
      $inlineExpandToggle.on('keydown', function (event) {
        const key = event && (event.key || event.code || '');
        if (key === 'Enter' || key === ' ' || key === 'Spacebar' || key === 'Space') {
          triggerInlineExpandToggle(event);
        }
      });
      $inlinePlayOverlay.on('click', triggerInlinePlay);
      $inlinePlayOverlay.on('keydown', function (event) {
        const key = event && (event.key || event.code || '');
        if (key === 'Enter' || key === ' ' || key === 'Spacebar' || key === 'Space') {
          triggerInlinePlay(event);
        }
      });
      videoEl.__dizzyInlineSyncOverlay = syncInlinePlayOverlay;
      observeInlineVideoVisibility(videoEl);
      videoEl.addEventListener('play', handleInlineVideoPlay);
      videoEl.addEventListener('pause', handleInlineVideoPause);
      videoEl.addEventListener('ended', syncInlinePlayOverlay);
      videoEl.addEventListener('webkitbeginfullscreen', function () {
        syncInlineExpandToggleButton($inlineExpandToggle.get(0), true);
        updateInlineStickyControls();
      });
      videoEl.addEventListener('webkitendfullscreen', function () {
        syncInlineExpandToggleButton(
          $inlineExpandToggle.get(0),
          $shell.hasClass('is-inline-expanded')
        );
        updateInlineStickyControls();
      });
      videoEl.addEventListener('loadedmetadata', function () {
        const metadataRatio = resolveRatioPercent(videoEl.videoWidth, videoEl.videoHeight);
        if (!metadataRatio) {
          return;
        }
        if (lockedPreviewRatio > 0) {
          // Keep poster ratio for rotated portrait videos where metadata may report landscape dimensions.
          const ratioDelta = Math.abs(metadataRatio - lockedPreviewRatio);
          if (ratioDelta > (lockedPreviewRatio * 0.2)) {
            return;
          }
        }
        applyInlineRatio(videoEl.videoWidth, videoEl.videoHeight);
      }, { once: true });

      syncPlayerAccentToken();

      applyInlineVideoMutedState(videoEl, getInlineGlobalMutedState());
      pauseOtherInlineVideos(videoEl);
      autoPlayMediaElement(videoEl);
      syncInlinePlayOverlay();
      updateInlineStickyControls();
      if (
        window.dizzyVideoJsLoader &&
        typeof window.dizzyVideoJsLoader.ensure === 'function'
      ) {
        window.dizzyVideoJsLoader.ensure().then(function (vjs) {
          if (vjs) {
            initVideoJsInlinePlayer(videoEl);
          }
        }).catch(function () {});
      } else if (window.videojs) {
        initVideoJsInlinePlayer(videoEl);
      }
      return true;
    }

    function bindSingleVideoInlinePlayback() {
      if (window.__dizzySingleInlineVideoBound) {
        return;
      }
      window.__dizzySingleInlineVideoBound = true;

      const captureHandler = function (event) {
        const target = event && event.target;
        if (!target) {
          return;
        }
        const targetEl = target.nodeType === 1 ? target : target.parentElement;
        if (!targetEl || typeof targetEl.closest !== 'function') {
          return;
        }

        const playButton = targetEl.closest('.i_post_u_images .i_post_image_swip_wrapper .playbutton');
        if (playButton) {
          const context = getSingleInlineVideoContext($(playButton));
          if (context) {
            event.preventDefault();
            event.stopPropagation();
            if (typeof event.stopImmediatePropagation === 'function') {
              event.stopImmediatePropagation();
            }
            activateSingleInlineVideo(context);
          }
          return;
        }
      };

      document.addEventListener('click', captureHandler, true);
      document.addEventListener('touchend', captureHandler, true);
    }

    function initGalleriesInDOM(scope = $(document)) {
      const shouldSkipFeedVideoGallery = function ($gallery) {
        if (
          !$gallery ||
          !$gallery.length ||
          !$gallery.closest('.i_post_u_images').length
        ) {
          return false;
        }
        const $galleryItems = $gallery.children('.i_post_image_swip_wrapper');
        if (!$galleryItems.length) {
          return false;
        }
        const $realGalleryItems = $galleryItems.filter(function () {
          return !$(this).hasClass('swiper-slide-duplicate');
        });
        const $items = $realGalleryItems.length ? $realGalleryItems : $galleryItems;
        if ($items.length !== 1) {
          return false;
        }
        return $items.filter('[data-html]').length === 1;
      };
      const disableFeedVideoGalleryPopup = function ($gallery) {
        if (!$gallery || !$gallery.length) {
          return;
        }
        try {
          const galleryInstance = $gallery.data('lightGallery');
          if (galleryInstance && typeof galleryInstance.destroy === 'function') {
            galleryInstance.destroy(true);
          }
        } catch (e) {}
        $gallery.removeClass('lg-initialized');
        $gallery.removeData('lightGallery');
        $gallery.removeData('lgInitPending');
        $gallery.find('.i_post_image_swip_wrapper').off('click.lgcustom click.lg');
      };

      scope.find(".gallery_trigger").each(function () {
        const galleryID = $(this).data("gallery-id");
        if (galleryID && !initializedGalleries.has(galleryID)) {
          const $gallery = $("#" + galleryID);
          if ($gallery.length > 0) {
            if (shouldSkipFeedVideoGallery($gallery)) {
              disableFeedVideoGalleryPopup($gallery);
              initializedGalleries.add(galleryID);
              return;
            }
            initLightGallerySafe($gallery, lightGalleryOptions);
            initializedGalleries.add(galleryID);
          }
        }
      });
    }

    function reInitPostPlugins(scope) {
        if (!scope) return;
        initGalleriesInDOM(scope);

        scope.find('[id^="lightgallery"]').each(function () {
          const $this = $(this);
          if ((
            function ($gallery) {
              if (
                !$gallery ||
                !$gallery.length ||
                !$gallery.closest('.i_post_u_images').length
              ) {
                return false;
              }
              const $galleryItems = $gallery.children('.i_post_image_swip_wrapper');
              if (!$galleryItems.length) {
                return false;
              }
              const $realGalleryItems = $galleryItems.filter(function () {
                return !$(this).hasClass('swiper-slide-duplicate');
              });
              const $items = $realGalleryItems.length ? $realGalleryItems : $galleryItems;
              if ($items.length !== 1) {
                return false;
              }
              return $items.filter('[data-html]').length === 1;
            }
          )($this)) {
            try {
              const galleryInstance = $this.data('lightGallery');
              if (galleryInstance && typeof galleryInstance.destroy === 'function') {
                galleryInstance.destroy(true);
              }
            } catch (e) {}
            $this.removeClass('lg-initialized');
            $this.removeData('lightGallery');
            $this.removeData('lgInitPending');
            $this.find('.i_post_image_swip_wrapper').off('click.lgcustom click.lg');
            return;
          }
          initLightGallerySafe($this, lightGalleryOptions);
        });

        scope.find('[id^="play_po_"]').each(function () {
          const $this = $(this);
          if (!$this.hasClass('green-audio-player-loaded')) {
            new GreenAudioPlayer($this[0], {
              stopOthersOnPlay: true,
              showTooltips: true,
              showDownloadButton: false,
              enableKeystrokes: true
            });
            $this.addClass('green-audio-player-loaded');
          }
        });

        initExpandableText(scope);
    }

    function getReadMoreLabels() {
        var more = 'Read more';
        var less = 'Show less';
        if (window.readMoreLang) {
            if (window.readMoreLang.more) {
                more = window.readMoreLang.more;
            }
            if (window.readMoreLang.less) {
                less = window.readMoreLang.less;
            }
        }
        return { more: more, less: less };
    }

    function applyExpandableText($content) {
        if (!$content || !$content.length) {
            return;
        }
        var maxLines = parseInt($content.attr('data-max-lines') || '0', 10);
        if (!maxLines) {
            return;
        }

        var labels = getReadMoreLabels();
        var node = $content.get(0);
        var style = window.getComputedStyle(node);
        var lineHeight = parseFloat(style.lineHeight);
        if (isNaN(lineHeight)) {
            var fontSize = parseFloat(style.fontSize) || 14;
            lineHeight = fontSize * 1.4;
        }
        var maxHeight = Math.ceil(lineHeight * maxLines);

        $content.removeClass('is-expanded is-truncated');
        $content.css('max-height', '');
        var $toggle = $content.next('.i_text_toggle');
        if ($toggle.length) {
            $toggle.remove();
        }

        if (node.scrollHeight <= (maxHeight + 2)) {
            return;
        }

        $content.addClass('is-truncated').css('max-height', maxHeight + 'px');
        $toggle = $('<div class="i_text_toggle" role="button" tabindex="0" aria-expanded="false"></div>');
        $toggle.text(labels.more);
        $toggle.insertAfter($content);
        $toggle.on('click keydown', function (e) {
            if (e.type === 'keydown') {
                var key = e.key || '';
                var code = e.which || e.keyCode || 0;
                if (key !== 'Enter' && key !== ' ' && code !== 13 && code !== 32) {
                    return;
                }
            }
            e.preventDefault();
            var isExpanded = $content.hasClass('is-expanded');
            if (isExpanded) {
                $content.removeClass('is-expanded').addClass('is-truncated').css('max-height', maxHeight + 'px');
                $toggle.text(labels.more).attr('aria-expanded', 'false');
            } else {
                $content.addClass('is-expanded').removeClass('is-truncated').css('max-height', 'none');
                $toggle.text(labels.less).attr('aria-expanded', 'true');
            }
        });
    }

    function initExpandableText(scope) {
        var $scope = scope ? $(scope) : $(document);
        $scope.find('.js-text-truncate').each(function () {
            applyExpandableText($(this));
        });
    }

    window.initImageBackgrounds = function (targetSelector = '.i_post_image_swip_wrapper', scope = $(document)) {
      scope.find(targetSelector).each(function () {
        const bg = $(this).attr('data-bg');
        if (bg) {
          $(this).css('background-image', 'url(' + bg + ')');
        }
      });
    };

    window.initImageSuggestedBackgrounds = function (targetSelector = '.i_sub_u_cov', scope = $(document)) {
      scope.find(targetSelector).each(function () {
        const bg = $(this).attr('data-bg');
        if (bg) {
          $(this).css('background-image', 'url(' + bg + ')');
        }
      });
    };


 const initializedStandaloneSwiper = new Set();

window.initStandaloneSwiperLightGallery = function (scope = $(document)) {
  scope.find('.swiper-wrapper[data-standalone-gallery="true"]').each(function () {
    const $wrapper = $(this);
    const galleryID = $wrapper.attr('id');

    if (!galleryID || initializedStandaloneSwiper.has(galleryID)) {
      return;
    }

    initLightGallerySafe($wrapper, $.extend({}, lightGalleryOptions, {
      selector: '.swiper-slide a'
    }));

    initializedStandaloneSwiper.add(galleryID);
  });

  scope.find('.product_images_container .mySwiper').each(function () {
    new Swiper(this, {
      autoplay: {
        delay: 3000,
        disableOnInteraction: false,
      },
      pagination: {
        el: ".swiper-pagination",
        dynamicBullets: true,
      }
    });
  });
};

$(document).on('click', '.swiper-slide a', function (e) {
  const wrapper = $(this).closest('.swiper-wrapper');
  if (wrapper.hasClass('lg-initialized')) {
    e.preventDefault();
  }
});

    const initializedSuggestedSwipers = new Set();

  /**
   * Initialize Swiper in dynamically loaded suggested creators sections.
   * @param {jQuery|HTMLElement} scope - Optional. DOM scope to search for .mySwiper elements.
   */
  window.initSuggestedCreatorsSwiper = function (scope = $(document)) {
    scope.find('.i_postFormContainer_swiper .mySwiper').each(function () {
      const swiperElement = this;

      // Use a unique identifier to prevent double initialization
      const swiperID = $(swiperElement).data('swiper-id') || swiperElement.id || Math.random().toString(36).substr(2, 9);

      if (!initializedSuggestedSwipers.has(swiperID)) {
        new Swiper(swiperElement, {
          effect: "cards",
          grabCursor: true
        });
        initializedSuggestedSwipers.add(swiperID);
      }
    });
  };

$(document).ready(function () {
  syncPlayerAccentToken();
  bindSingleVideoInlinePlayback();
  if (
    window.dizzyVideoJsLoader &&
    typeof window.dizzyVideoJsLoader.pageNeedsVideoJs === 'function' &&
    typeof window.dizzyVideoJsLoader.ensure === 'function' &&
    window.dizzyVideoJsLoader.pageNeedsVideoJs()
  ) {
    window.dizzyVideoJsLoader.ensure().catch(function () {});
  }
  initGalleriesInDOM(); // mevcut sistemin
  reInitPostPlugins($(document));
  initImageBackgrounds();
  initStandaloneSwiperLightGallery();
  initSuggestedCreatorsSwiper();
  initImageSuggestedBackgrounds();
});

    window.reInitLightGallery = function (html) {
        initGalleriesInDOM(html);
    };

  /********* SCROLL TO LOAD MORE ***********/
  let scrollLoad = true;
  $(document).on('touchmove', showMoreData); /* For mobile */
  $(window).on('scroll', showMoreData);

  function showMoreData() {
    if (
      scrollLoad &&
      $(window).scrollTop() >= $(document).height() - $(window).height() - 500
    ) {
      const moreType = $("#moreType").attr("data-type");
      const moreCat = $("#moreType").attr("data-po");
      let profileUserID = '';
      let ID;

      if (moreType === 'notifications' || moreType === 'paid' || moreType === 'free' || moreType === 'creators') {
        ID = $('#moreType').children('.mor').last().attr('data-last');
        if (moreType === 'creators') {
          profileUserID = $("#moreType").attr("data-r");
        }
      }

      if (
        moreType === 'moreposts' || moreType === 'savedpost' || moreType === 'moreexplore' ||
        moreType === 'morepremium' || moreType === 'friends' || moreType === 'morepurchased' ||
        moreType === 'moreboostedposts' || moreType === 'moretrendposts' || moreType === 'hashtag'
      ) {
        ID = $('#moreType').children('.i_post_body').last().attr('data-last');
        if (moreType === 'hashtag') {
          profileUserID = $("#moreType").attr("data-hash");
        }
      }

      if (moreType === 'community') {
        ID = $('#moreType').children('.i_post_body').last().attr('data-last');
        profileUserID = $("#moreType").attr("data-community");
      }

      if (moreType === 'profile') {
        ID = $('#moreType').children('.i_post_body').last().attr('data-last');
        if (!ID) {
          ID = $('#moreType').children('.i_sub_box_container').last().attr('data-last');
        }
        profileUserID = $("#prw").attr("data-u");
      }
      const profileLastBefore = (moreType === 'profile')
        ? $('#moreType').children('.i_post_body, .i_sub_box_container').last().attr('data-last')
        : null;

      if (
        $('.i_loading , .nomore , .noPost , .no_creator_f_wrap').length === 0 &&
        !$(".i_loading , .nomore , .noPost , .no_creator_f_wrap")[0] &&
        moreType !== undefined
      ) {
        let data = `f=${moreType}&last=${ID}&p=${profileUserID}`;
        if (moreCat) {
          data += `&pcat=${moreCat}`;
        }
        if (moreType === 'community' && profileUserID) {
          data += `&community_id=${profileUserID}`;
        }
        const moreFilter = $("#moreType").attr("data-pf");
        if (moreFilter) {
          data += `&pf=${moreFilter}`;
        }

        $.ajax({
          type: "POST",
          url: siteurl + 'requests/request.php',
          data: data,
          cache: false,
          beforeSend: function () {
            $(".body_" + ID).after(preLoadingAnimation);
            scrollLoad = false;
          },
          success: function (response) {
            $(".i_loading").remove();
            if (response && !$(".nomore")[0]) {
                const $newContent = $(response);

                if (moreType === 'profile') {
                  const $incomingPosts = $newContent.filter('.i_post_body, .i_sub_box_container')
                    .add($newContent.find('.i_post_body, .i_sub_box_container'));
                  const incomingLastId = $incomingPosts.last().attr('data-last');
                  const hasNewProfilePosts = $incomingPosts.length > 0 &&
                    (!profileLastBefore || incomingLastId !== profileLastBefore);

                  if (!hasNewProfilePosts) {
                    const $stopper = $newContent.filter('.noPost, .nomore')
                      .add($newContent.find('.noPost, .nomore'));
                    $("#moreType").append($stopper.length ? $stopper : '<div class="nomore"></div>');
                    scrollLoad = false;
                    return;
                  }
                }

                $("#moreType").append($newContent);

                reInitLightGallery($newContent);
                reInitPostPlugins($newContent);
                initImageBackgrounds('.i_post_image_swip_wrapper', $newContent);
                initSuggestedCreatorsSwiper($newContent);
                initImageSuggestedBackgrounds();
              scrollLoad = true;
            }
          }
        });
      }
    }
  }

    /* Sync selected visibility chip */
    function syncWhoChip(scope) {
        var $root = scope && scope.length ? scope : $(document);
        $root.find(".i_form_buttons").each(function() {
            var $formButtons = $(this);
            var $chip = $formButtons.find(".ws_selected_chip");
            if (!$chip.length) {
                return;
            }
            var $selected = $formButtons.find(".i_choose_ws_wrapper .wsUpdate.wselected").first();
            var hasSelection = $selected.length > 0 && $.trim($selected.text()).length > 0;
            if (hasSelection) {
                $chip.html($selected.html()).addClass("ws_visible");
            } else {
                $chip.empty().removeClass("ws_visible");
            }
        });
    }

    /*Update Who Can See POST Before Share Post*/
    $(document).on("click", ".wsUpdate", function() {
        var $clicked = $(this);
        var type = 'whoSee';
        var ID = $(this).attr("data-id");

        /* If premium is already selected and clicked again, fallback to Everyone (id:1) */
        var isPremiumActive = $clicked.hasClass("wselected") || $(".point_input_wrapper").length > 0;
        if (ID === '4' && isPremiumActive) {
            var $defaultOption = $clicked.closest(".i_form_buttons").find(".i_choose_ws_wrapper .wsUpdate[data-id='1']").first();
            if ($defaultOption.length) {
                $defaultOption.trigger("click");
                return false;
            }
        }

        var data = 'f=' + type + '&who=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $(".i_whoseech_menu_item_out, .i_pb_premiumPostBox").removeClass("wselected");
                if (response) {
                    $("#wsUpdate" + ID).addClass("wselected");
                    $(".wBox").html('').append(response);
                    $(".i_choose_ws_wrapper").removeClass('dblock');
                    syncWhoChip($("#wsUpdate" + ID).closest(".i_form_buttons"));
                }
                if (ID == '4' && $("div[class='point_input_wrapper']").length === 0) {
                    whoSeePremium();
                } else {
                    $(".point_input_wrapper").remove();
                }
            }
        });
    });

    /* Profile product filter */
    $(document).on("change", "#profileProductFilter", function () {
        var val = $(this).val();
        var base = $(this).data("base");
        var user = $(this).data("user");
        if (!base || !user) { return; }
        var target = base + user + "?pcat=products";
        if (val && val !== "all") {
            target += "&ptype=" + encodeURIComponent(val);
        }
        window.location.href = target;
    });

    $(document).ready(function() {
        syncWhoChip($(document));
    });

    var qaCloseTimer = null;
    function setQuickActionsState(forceState) {
        var $qa = $("#quickActionsContainer");
        if (!$qa.length) {
            return;
        }
        var layout = $qa.data("qa-layout") || $qa.attr("data-qa-layout") || "popup";
        var isPopup = layout === "popup";
        var isOpen = $qa.hasClass("qaVisible");
        var shouldOpen = (typeof forceState === "boolean") ? forceState : !isOpen;
        if (qaCloseTimer) {
            window.clearTimeout(qaCloseTimer);
            qaCloseTimer = null;
        }
        if (shouldOpen) {
            $qa.removeClass("qaClosing").addClass("qaVisible").attr("aria-hidden", "false");
            if (isPopup) {
                $("body").addClass("qaOpen");
            } else {
                $("body").removeClass("qaOpen");
            }
        } else {
            if (isPopup && isOpen) {
                $qa.addClass("qaClosing");
                qaCloseTimer = window.setTimeout(function () {
                    $qa.removeClass("qaClosing qaVisible").attr("aria-hidden", "true");
                    $("body").removeClass("qaOpen");
                    qaCloseTimer = null;
                }, 260);
            } else {
                $qa.removeClass("qaClosing qaVisible").attr("aria-hidden", "true");
                $("body").removeClass("qaOpen");
            }
        }
    }

    /* Quick actions toggle */
    $(document).on("click", "#createNewToggle", function(e){
        e.preventDefault();
        setQuickActionsState();
    });
    $(document).on("click", "#qaClose, #qaOverlay", function(e){
        e.preventDefault();
        setQuickActionsState(false);
    });
    $(document).on("click", "#quickActionsContainer .qa_body .form_btn, #quickActionsContainer .qa_body .i_ai_generate", function(){
        setQuickActionsState(false);
    });
    $(document).on("keyup", function(e){
        if (e.key === "Escape") {
            setQuickActionsState(false);
        }
    });
    $(document).on("click", function(e){
        var $target = $(e.target);
        if(!$target.closest("#quickActionsContainer").length && !$target.closest("#createNewToggle").length){
            setQuickActionsState(false);
        }
    });

    function whoSeePremium() {
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: 'f=pw_premium',
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response) {
                    $(".aft").after(response);;
                }
            }
        });
    }
    /*Get PopUp for Post Updating WhoCanSee*/
    $(document).on("click", ".wcs", function() {
        var type = 'wcs';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response) {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                }
            }
        });
    });
    /*Trending Hashtags Top 100 Popup*/
    $(document).on("click", ".trendHashtagsTopBtn", function() {
        var data = 'f=trend_hashtags_top100';
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            success: function(response) {
                if (response) {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                } else if (typeof PopUPAlerts === "function") {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Update WhoCanSee Status for Shared Post*/
    $(document).on("click", ".who_can_see_pop_item", function() {
        var type = 'uwcs';
        var ID = $(this).attr("id");
        var wcs = $(this).attr("data-id");
        var data = 'f=' + type + '&id=' + ID + '&wci=' + wcs;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    $("#ipublic_" + ID).html('').append(response);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 100);
            }
        });
    });
    /*Call Edit Post PoUpbox*/
    $(document).on("click", ".edtp", function() {
        var type = 'c_editPost';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Call Delete Post PopUpBox*/
    $(document).on("click", ".delp", function() {
        var type = 'ddelPost';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Save Post Edit*/
    $(document).on("click", ".sedt", function() {
        var type = 'editS';
        var ID = $(this).attr('id');
        var editText = $("#ed_" + ID).val();
        var modal = $(this).closest(".i_modal_content");
        var pollBuilder = modal.find("#editPollBuilder");
        var pollWarning = modal.find(".i_warning_poll_edit");
        if (!pollWarning.length) {
            pollWarning = $(".i_warning_poll_edit");
        }
        var isPoll = pollBuilder.length > 0;
        var data = 'f=' + type + '&id=' + ID + '&text=' + encodeURIComponent(editText);
        if (isPoll) {
            var pollOptions = [];
            pollBuilder.find(".poll_option_input").each(function() {
                var val = $(this).find(".poll_option_field").val().trim();
                var optionId = $(this).data("option-id") || '';
                if (val) {
                    pollOptions.push({ id: optionId, text: val });
                }
            });
            var pollMax = parseInt(pollBuilder.data("max") || $("#poll_max_options").val() || 6, 10);
            var pollMin = parseInt(pollBuilder.data("min") || $("#poll_min_options").val() || 2, 10);
            if (pollOptions.length < pollMin) {
                if (pollWarning && pollWarning.length) {
                    pollWarning.text(pollBuilder.data("msg-min") || '').fadeIn();
                }
                return;
            }
            if (pollOptions.length > pollMax) {
                if (pollWarning && pollWarning.length) {
                    pollWarning.text(pollBuilder.data("msg-max") || '').fadeIn();
                }
                return;
            }
            if (pollWarning && pollWarning.length) {
                pollWarning.hide();
            }
            data += '&is_poll=1';
            var csrfToken = pollBuilder.data("csrf") || $("#poll_csrf_token").val() || $('meta[name=csrf-token]').attr('content');
            if (csrfToken) {
                data += '&csrf_token=' + encodeURIComponent(csrfToken);
            }
            $.each(pollOptions, function(index, opt) {
                data += '&poll_options[' + index + '][id]=' + encodeURIComponent(opt.id || '');
                data += '&poll_options[' + index + '][text]=' + encodeURIComponent(opt.text);
            });
        } else if (pollWarning && pollWarning.length) {
            pollWarning.hide();
        }
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var responseStatus = response.status;
                var editedText = response.text;
                if (responseStatus == 'no') {
                    PopUPAlerts('eCouldNotEmpty', 'ialert');
                } else if (responseStatus === 'poll_min_options') {
                    if (pollWarning && pollWarning.length && pollBuilder.length) {
                        pollWarning.text(pollBuilder.data("msg-min") || '').fadeIn();
                    } else {
                        PopUPAlerts('sWrong', 'ialert');
                    }
                } else if (responseStatus === 'poll_max_options') {
                    if (pollWarning && pollWarning.length && pollBuilder.length) {
                        pollWarning.text(pollBuilder.data("msg-max") || '').fadeIn();
                    } else {
                        PopUPAlerts('sWrong', 'ialert');
                    }
                } else if (responseStatus === 'poll_disabled') {
                    if (pollWarning && pollWarning.length && pollBuilder.length) {
                        pollWarning.text(pollBuilder.data("msg-disabled") || '').fadeIn();
                    } else {
                        PopUPAlerts('sWrong', 'ialert');
                    }
                } else if (responseStatus === 'poll_not_allowed' || responseStatus === 'poll_missing' || responseStatus === 'poll_update_failed' || responseStatus === 'poll_invalid_csrf') {
                    PopUPAlerts('sWrong', 'ialert');
                } else if (responseStatus == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                } else if (responseStatus == '200') {
                    $("#i_post_container_" + ID).show();
                    var $postText = $("#i_post_text_" + ID);
                    var $postContent = $postText.find(".i_post_text_content");
                    if ($postContent.length) {
                        $postContent.html(editedText);
                    } else {
                        $postText.html('<div class="i_post_text_content js-text-truncate" data-max-lines="6">' + editedText + '</div>');
                    }
                    initExpandableText($postText);
                    if (response.poll) {
                        refreshPollAfterEdit(ID, response.poll);
                    }
                    $(".i_modal_in_in").addClass("i_modal_in_in_out");
                    setTimeout(() => {
                        $(".i_modal_bg_in").remove();
                    }, 100);
                }
            }
        });
    });
    /*Uploading Music, Video and Image*/
    $(document).on("change", "#i_image_video", function (e) {
        e.preventDefault();
        var values = $("#uploadVal").val();
        var id = $("#i_image_video").attr("data-id");
        var data = { f: id };

        $('.i_uploaded_iv').append('<div class="i_upload_progress"></div>');

        $("#uploadform").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function () {
                $(".i_warning_unsupported").hide();
                $(".i_uploaded_iv").show();
                $(".i_upload_progress").width('0%');
                $(".publish").prop("disabled", true);
                $(".publish").css("pointer-events", "none");
            },
            uploadProgress: function (e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function (response) {
                if (response != '303') {
                    $(".i_uploaded_file_box").append(response);
                    var K = $('.i_uploaded_item').map(function () { return this.id }).toArray();
                    var T = K + "," + values;
                    if (T != "undefined,") {
                        $("#uploadVal").val(T);
                    }

                } else {
                    $(".i_uploaded_iv , .i_uploading_not").hide();
                    $(".i_warning_unsupported").show();
                }
                $(".i_upload_progress").width('0%');
                $(".i_uploading_not").hide();
                setTimeout(() => {
                    $('.publish').prop('disabled', false);
                    $(".publish").css("pointer-events", "auto");
                }, 3000);
            },
            error: function (xhr, status, error) { handleUploadError(xhr, status, error); }
        }).submit();
    });

    /*Delete Uploaded File Before Publish*/
    $(document).on("click", ".i_delete_item_button", function() {
        var type = 'delete_file';
        var ID = $(this).attr('id');
        var input = $('#uploadVal');
        var data = 'f=' + type + '&file=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    $(".iu_f_" + ID).remove();
                    input.val(function(_, value) {
                        return value.split(',').filter(function(val) {
                            return val !== ID;
                        }).join(',');
                    });
                } else {
                    PopUPAlerts('not_file', 'ialert')
                }
                if (!$(".i_uploaded_item")[0]) {
                    $(".i_uploaded_iv").hide();
                }
            }
        });
    });
    /*Poll Builder Helpers*/
    function resetPollBuilder() {
        var builder = $("#pollBuilder");
        if (builder.length) {
            builder.removeClass("active").addClass("nonePoint");
            builder.find(".poll_option_field").val('');
            builder.find(".poll_options_wrapper .poll_option_input").slice(2).remove();
            $(".i_warning_poll").hide();
        }
    }
    function getPollWarning(builder) {
        if (builder && builder.length && builder.attr("id") === "editPollBuilder") {
            return $(".i_warning_poll_edit");
        }
        var warningFromForm = builder ? builder.closest("form").find(".i_warning_poll") : null;
        if (warningFromForm && warningFromForm.length) {
            return warningFromForm;
        }
        return $(".i_warning_poll");
    }
    $(document).on("click", ".openPollBuilderBtn", function() {
        var builder = $("#pollBuilder");
        if (!builder.length) { return; }
        builder.removeClass("nonePoint").addClass("active");
    });
    $(document).on("click", ".close_poll_builder", function() {
        resetPollBuilder();
    });
    $(document).on("click", ".add_poll_option", function() {
        var builder = $(this).closest(".poll_builder");
        if (!builder.length) {
            builder = $("#pollBuilder");
        }
        if (!builder.length) { return; }
        var pollMax = parseInt(builder.data("max") || $("#poll_max_options").val() || 6, 10);
        var warning = getPollWarning(builder);
        var current = builder.find(".poll_option_input").length;
        if (current >= pollMax) {
            if (warning && warning.length) {
                warning.text(builder.data("msg-max") || '').fadeIn();
            }
            return;
        }
        var placeholder = builder.find(".poll_option_field").first().attr("placeholder") || '';
        var newOption = $('<div class="poll_option_input"><input type="text" class="poll_option_field i_input" placeholder="' + placeholder + '"><div class="remove_poll_option transition">&times;</div></div>');
        builder.find(".poll_options_wrapper").append(newOption);
        if (warning && warning.length) {
            warning.hide();
        }
    });
    $(document).on("click", ".remove_poll_option", function(e) {
        e.preventDefault();
        e.stopPropagation();
        var builder = $(this).closest(".poll_builder");
        if (!builder.length) {
            builder = $("#pollBuilder");
        }
        if (!builder.length) { return; }
        var pollMin = parseInt(builder.data("min") || $("#poll_min_options").val() || 2, 10);
        if (isNaN(pollMin) || pollMin < 0) {
            pollMin = 0;
        }
        var total = builder.find(".poll_option_input").length;
        if (total > pollMin) {
            $(this).closest(".poll_option_input").remove();
            var warning = getPollWarning(builder);
            if (warning && warning.length) {
                warning.hide();
            }
        } else {
            var warningMin = getPollWarning(builder);
            if (warningMin && warningMin.length) {
                warningMin.text(builder.data("msg-min") || '').fadeIn();
            }
        }
    });
    /*Schedule helpers*/
    var scheduleControls = $("#scheduleControls");
    var scheduleToggle = $("#schedulePostToggle");
    var scheduleRow = $("#schedulePopup");
    var scheduleInput = $("#scheduleAt");
    var scheduleDay = $("#scheduleDay");
    var scheduleMonth = $("#scheduleMonth");
    var scheduleYear = $("#scheduleYear");
    var scheduleHour = $("#scheduleHour");
    var scheduleMinute = $("#scheduleMinute");
    var scheduleSecond = $("#scheduleSecond");
    var scheduleWarning = $(".i_warning_schedule");
    var scheduleInlineWarning = $("#scheduleInlineWarning");
    var scheduleClose = $("#schedulePopupClose");
    var scheduleButton = $("#scheduleButton");
    var scheduleSelection = $("#scheduleSelection");
    var scheduleSelectedTime = $("#scheduleSelectedTime");
    var scheduleTz = $(".schedule_tz");
    var scheduleClear = $("#scheduleClear");
    var schedulePopupOk = $("#schedulePopupOk");
    var schedulePopupCancel = $("#schedulePopupCancel");
    var scheduledMaxDays = parseInt($("#scheduled_max_days").val() || scheduleControls.data("max-days") || 30, 10);
    if (isNaN(scheduledMaxDays) || scheduledMaxDays < 1) {
        scheduledMaxDays = 30;
    }
    if (scheduledMaxDays > 30) {
        scheduledMaxDays = 30;
    }
    var scheduleEnabled = ($("#scheduled_status_toggle").val() || '0') === '1';
    var publishButtonLabel = $(".publish .pbtn").text();
    var scheduleActive = false;
    // Try to display browser timezone to improve clarity
    try {
        if (scheduleTz && scheduleTz.length) {
            var browserTz = Intl.DateTimeFormat().resolvedOptions().timeZone;
            if (browserTz) {
                scheduleTz.text((scheduleTz.text().split(':')[0] || 'Timezone') + ': ' + browserTz);
            }
        }
    } catch (e) { /* ignore */ }

    function formatDateLocal(date) {
        const pad = (n) => (n < 10 ? '0' + n : n);
        return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate()) + 'T' + pad(date.getHours()) + ':' + pad(date.getMinutes());
    }
    function populateScheduleSelectors(baseDate) {
        if (!baseDate) { baseDate = new Date(); }
        var currentYear = baseDate.getFullYear();
        var maxYear = currentYear + 2;
        if (scheduleYear.length && scheduleYear.children().length === 0) {
            for (var y = currentYear; y <= maxYear; y++) {
                scheduleYear.append('<option value="' + y + '">' + y + '</option>');
            }
        }
        if (scheduleMonth.length && scheduleMonth.children().length === 0) {
            var months = ['01','02','03','04','05','06','07','08','09','10','11','12'];
            months.forEach(function(m, idx){
                scheduleMonth.append('<option value="' + (idx+1) + '">' + m + '</option>');
            });
        }
        if (scheduleHour.length && scheduleHour.children().length === 0) {
            for (var h = 0; h < 24; h++) {
                var hv = (h < 10 ? '0' + h : '' + h);
                scheduleHour.append('<option value="' + h + '">' + hv + '</option>');
            }
        }
        if (scheduleMinute.length && scheduleMinute.children().length === 0) {
            for (var m = 0; m < 60; m++) {
                var mv = (m < 10 ? '0' + m : '' + m);
                scheduleMinute.append('<option value="' + mv + '">' + mv + '</option>');
            }
        }
        if (scheduleSecond.length && scheduleSecond.children().length === 0) {
            var secs = [0,15,30,45];
            secs.forEach(function(s){
                var sv = (s < 10 ? '0' + s : '' + s);
                scheduleSecond.append('<option value="' + s + '">' + sv + '</option>');
            });
        }
        setScheduleFields(baseDate);
    }
    function getSelectedDateTime() {
        var dayVal = parseInt(scheduleDay.val() || 0, 10);
        var monthVal = parseInt(scheduleMonth.val() || 0, 10);
        var yearVal = parseInt(scheduleYear.val() || 0, 10);
        var hourVal = parseInt(scheduleHour.val() || 0, 10);
        var minuteVal = parseInt(scheduleMinute.val() || 0, 10);
        var secondVal = parseInt(scheduleSecond.val() || 0, 10);
        if (!dayVal || !monthVal || !yearVal) { return null; }
        var composed = new Date(yearVal, monthVal - 1, dayVal, hourVal || 0, minuteVal || 0, secondVal || 0);
        return isNaN(composed.getTime()) ? null : composed;
    }
    function renderSchedulePreview(dateObj) {
        if (!dateObj) { return; }
        var formatted = dateObj.toLocaleString(undefined, {
            month: 'short',
            day: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        if (scheduleSelectedTime.length) {
            var tpl = scheduleControls.data("created") || '';
            scheduleSelectedTime.text(tpl ? tpl.replace('{time}', formatted) : formatted);
        }
        if (scheduleSelection.length) {
            scheduleSelection.removeClass("nonePoint");
        }
        if (scheduleButton.length) {
            scheduleButton.addClass("active");
        }
    }
    function setScheduleFields(dt) {
        if (!dt) { return; }
        var day = dt.getDate();
        var month = dt.getMonth() + 1;
        var year = dt.getFullYear();
        var hour = dt.getHours();
        var minute = dt.getMinutes();
        if (scheduleDay.length) {
            scheduleDay.empty();
            var daysInMonth = new Date(year, month, 0).getDate();
            for (var d = 1; d <= daysInMonth; d++) {
                var dv = (d < 10 ? '0' + d : '' + d);
                scheduleDay.append('<option value="' + d + '">' + dv + '</option>');
            }
            scheduleDay.val(day);
        }
        if (scheduleMonth.length) { scheduleMonth.val(month); }
        if (scheduleYear.length) { scheduleYear.val(year); }
        if (scheduleHour.length) { scheduleHour.val(hour); }
        if (scheduleMinute.length) {
            var paddedMinute = (minute < 10 ? '0' + minute : '' + minute);
            scheduleMinute.val(paddedMinute);
        }
        if (scheduleSecond.length) {
            scheduleSecond.val(0);
        }
        if (scheduleInput.length) { scheduleInput.val(formatDateLocal(dt)); }
    }
    function refreshScheduleMin() {
        var now = new Date();
        var min = formatDateLocal(now);
        if (scheduleInput.length) {
            scheduleInput.attr('min', min);
        }
        var def = new Date();
        def.setMinutes(def.getMinutes() + 30);
        populateScheduleSelectors(def);
    }
    function resetScheduleUI() {
        scheduleActive = false;
        if (scheduleToggle.length) {
            scheduleToggle.prop('checked', false);
        }
        if (scheduleRow.length) {
            scheduleRow.removeClass("active");
        }
        if (scheduleWarning.length) {
            scheduleWarning.hide();
        }
        if (scheduleInlineWarning.length) {
            scheduleInlineWarning.addClass("nonePoint").text('');
        }
        if (scheduleInput.length) {
            scheduleInput.val('');
        }
        if (scheduleDay.length) { scheduleDay.empty(); }
        if (scheduleMonth.length) { scheduleMonth.empty(); }
        if (scheduleYear.length) { scheduleYear.empty(); }
        if (scheduleHour.length) { scheduleHour.empty(); }
        if (scheduleMinute.length) { scheduleMinute.empty(); }
        if (scheduleSecond.length) { scheduleSecond.empty(); }
        if (scheduleSelection.length) {
            scheduleSelection.addClass("nonePoint");
        }
        if (scheduleSelectedTime.length) {
            scheduleSelectedTime.text('');
        }
        if (scheduleButton.length) {
            scheduleButton.removeClass("active");
        }
        if (scheduleControls.length) {
            scheduleControls.addClass("nonePoint");
        }
        $(".publish .pbtn").text(publishButtonLabel);
    }
    function openSchedulePopup() {
        refreshScheduleMin();
        var preset = null;
        if (scheduleActive && scheduleInput.length && scheduleInput.val()) {
            var parsed = new Date(scheduleInput.val());
            if (!isNaN(parsed.getTime())) { preset = parsed; }
        }
        populateScheduleSelectors(preset || new Date());
        scheduleRow.addClass("active");
        $(".publish .pbtn").text($("#scheduleControls .irow_box_left").text() || publishButtonLabel);
    }
    function closeSchedulePopup(keepToggleState) {
        scheduleRow.removeClass("active");
        if (!keepToggleState) {
            resetScheduleUI();
        }
        if (scheduleInlineWarning.length) {
            scheduleInlineWarning.addClass("nonePoint").text('');
        }
    }
    function showScheduleError(message, type) {
        var msg = (message || '').toString();
        if (msg.trim() === '') {
            if (type === 'disabled') {
                msg = (scheduleControls && scheduleControls.length ? (scheduleControls.data("disabled") || '') : '');
                if (msg.trim() === '') { msg = 'Scheduling is disabled right now.'; }
            } else {
                msg = (scheduleControls && scheduleControls.length ? (scheduleControls.data("invalid") || '') : '');
                if (msg.trim() === '') { msg = 'Invalid time.'; }
            }
        }
        // Inline warning is inside the popup; if the popup isn't open yet, use the global warning so it's visible.
        if (scheduleRow && scheduleRow.length && scheduleRow.hasClass("active") && scheduleInlineWarning.length) {
            scheduleInlineWarning.text(msg).removeClass("nonePoint");
        } else if (scheduleWarning.length) {
            scheduleWarning.text(msg).fadeIn();
        } else if (scheduleInlineWarning.length) {
            scheduleInlineWarning.text(msg).removeClass("nonePoint");
        }
    }
    if (scheduleClose && scheduleClose.length) {
        scheduleClose.on("click", function() {
            closeSchedulePopup(true);
        });
    }
    if (scheduleButton && scheduleButton.length) {
        scheduleButton.on("click", function(e) {
            e.preventDefault();
            if (!scheduleEnabled) {
                showScheduleError(scheduleControls.data("disabled") || '', 'disabled');
                return;
            }
            if (scheduleControls.length) {
                scheduleControls.removeClass("nonePoint");
            }
            if (scheduleSelection.length) {
                scheduleSelection.addClass("nonePoint");
            }
            if (scheduleSelectedTime.length) {
                scheduleSelectedTime.text('');
            }
            openSchedulePopup();
        });
    }
    if (schedulePopupCancel && schedulePopupCancel.length) {
        schedulePopupCancel.on("click", function() {
            closeSchedulePopup(true);
        });
    }
    if (schedulePopupOk && schedulePopupOk.length) {
        schedulePopupOk.on("click", function() {
            if (!scheduleEnabled) {
                showScheduleError(scheduleControls.data("disabled") || '', 'disabled');
                return;
            }
            var nowTs = Math.floor(Date.now() / 1000);
            var composed = getSelectedDateTime();
            var scheduleTimestamp = composed ? Math.floor(composed.getTime() / 1000) : null;
            if (!scheduleTimestamp || scheduleTimestamp <= nowTs || (scheduledMaxDays && scheduleTimestamp > (nowTs + (scheduledMaxDays * 86400)))) {
                showScheduleError(scheduleControls.data("invalid") || '', 'invalid');
                return;
            }
            var formatted = composed.toLocaleString();
            scheduleActive = true;
            if (scheduleToggle.length) {
                scheduleToggle.prop('checked', true);
            }
            renderSchedulePreview(composed);
            if (scheduleInput.length) {
                scheduleInput.val(formatDateLocal(composed));
            }
            if (scheduleControls.length) {
                scheduleControls.removeClass("nonePoint");
            }
            closeSchedulePopup(true);
        });
    }
    if (scheduleClear && scheduleClear.length) {
        scheduleClear.on("click", function(e) {
            e.preventDefault();
            resetScheduleUI();
        });
    }
    var campaignActive = false;
    var campaignEnabled = $("#campaign_enabled").val() === '1';
    var campaignTitleRequired = $("#campaign_title_required_flag").val() === '1';
    var campaignGoalRequired = $("#campaign_goal_required_flag").val() === '1';
    var campaignDeadlineRequired = $("#campaign_deadline_required_flag").val() === '1';
    var campaignGoalMin = parseFloat($("#campaign_goal_min_flag").val()) || 0;
    if (campaignGoalMin < 0) { campaignGoalMin = 0; }
    var campaignGoalMax = parseFloat($("#campaign_goal_max_flag").val()) || 0;
    var campaignPopup = $("#campaignPopup");
    var campaignOpenBtn = $(".campaignOpenBtn");
    var campaignSaveBtn = $(".campaignSaveBtn");
    var campaignCancelBtn = $(".campaignCancelBtn");
    var campaignPopupOverlay = $("#campaignPopupClose");
    var campaignPopupCloseBtn = $("#campaignPopupCloseBtn");
    var campaignCoverButton = $("#campaignCoverButton");
    var campaignCoverDrop = $("#campaignCoverDrop");
    var campaignCoverInput = $("#campaignCoverHiddenInput");
    var campaignCoverIdInput = $("#campaignCoverId");
    var campaignCoverPreview = $("#campaignCoverPreview");
    var campaignCoverImg = $("#campaignCoverImg");
    var campaignCoverRemove = $("#campaignCoverRemove");
    var campaignCoverTemp = $("#campaignCoverTemp");
    var campaignCoverForm = $("#campaignCoverUploadForm");
    function getCampaignMessages() {
        var langMap = (typeof LANG !== "undefined" && LANG) ? LANG : {};
        var warning = $(".i_warning_campaign");
        var minInvalid = warning.data("minInvalid") || warning.data("min-invalid");
        var maxInvalid = warning.data("maxInvalid") || warning.data("max-invalid");
        var coverInvalid = warning.data("coverInvalid") || warning.data("cover-invalid");
        var goalMinMsg = warning.data("goalMinMsg") || warning.data("goal-min-msg");
        var goalMaxMsg = warning.data("goalMaxMsg") || warning.data("goal-max-msg");
        var disabledMsg = campaignOpenBtn.data("msgDisabled") || campaignOpenBtn.data("msg-disabled") || campaignOpenBtn.data("label") || 'campaign disabled';
        return {
            'campaign_disabled': disabledMsg,
            'campaign_permission': warning.data("permission") || 'permission denied',
            'campaign_title_required': $("#campaignTitle").data("msg") || 'Title required',
            'campaign_goal_required': $("#campaignGoal").data("msg") || 'Goal required',
            'campaign_deadline_required': $("#campaignDeadline").data("msg") || 'Deadline required',
            'campaign_goal_invalid': $("#campaignGoal").data("invalid") || warning.data("goal-invalid") || 'Invalid goal',
            'campaign_min_amount_invalid': minInvalid || 'Invalid minimum',
            'campaign_max_amount_invalid': maxInvalid || 'Invalid maximum',
            'campaign_max_exceeds_goal': langMap.campaign_max_exceeds_goal || 'Maximum contribution cannot be greater than the goal.',
            'campaign_goal_below_min': goalMinMsg || langMap.campaign_goal_below_min || 'Goal below minimum.',
            'campaign_goal_above_max': goalMaxMsg || langMap.campaign_goal_above_max || 'Goal above maximum.',
            'campaign_amount_range': warning.data("range") || 'Invalid amount range',
            'campaign_deadline_invalid': $("#campaignDeadline").data("invalid") || 'Invalid deadline',
            'campaign_cover_invalid': coverInvalid || 'Invalid cover',
            'campaign_create_failed': warning.data("create-failed") || 'Create failed'
        };
    }
    function showCampaignWarning(message) {
        if (!message) { return; }
        $(".i_warning_campaign").text(message).fadeIn();
    }
    function hideCampaignWarning() {
        $(".i_warning_campaign").fadeOut(100);
    }
    function buildCampaignDeadline() {
        var monthVal = parseInt($("#campaignDeadlineMonth").val(), 10);
        var dayVal = parseInt($("#campaignDeadlineDay").val(), 10);
        var yearVal = parseInt($("#campaignDeadlineYear").val(), 10);
        var hourVal = parseInt($("#campaignDeadlineHour").val(), 10);
        var minuteVal = parseInt($("#campaignDeadlineMinute").val(), 10);
        var secondVal = parseInt($("#campaignDeadlineSecond").val(), 10);

        if (isNaN(monthVal) || isNaN(dayVal) || isNaN(yearVal) || isNaN(hourVal) || isNaN(minuteVal) || isNaN(secondVal)) {
            return { raw: '', ts: null };
        }
        if (monthVal < 1 || monthVal > 12 || dayVal < 1 || dayVal > 31 || yearVal < 1970 || hourVal < 0 || hourVal > 23 || minuteVal < 0 || minuteVal > 59 || secondVal < 0 || secondVal > 59) {
            return { raw: '', ts: null };
        }
        var dt = new Date(yearVal, monthVal - 1, dayVal, hourVal, minuteVal, secondVal, 0);
        if (dt.getMonth() !== monthVal - 1 || dt.getDate() !== dayVal || dt.getFullYear() !== yearVal) {
            return { raw: '', ts: null };
        }
        var ts = dt.getTime();
        var pad = function(n) { return n < 10 ? '0' + n : '' + n; };
        var composed = yearVal + '-' + pad(monthVal) + '-' + pad(dayVal) + ' ' + pad(hourVal) + ':' + pad(minuteVal) + ':' + pad(secondVal);
        return { raw: composed, ts: ts };
    }
    function getCampaignFieldValues() {
        return {
            title: $("#campaignTitle").val() || '',
            summary: $("#campaignSummary").val() || '',
            goal: $("#campaignGoal").val() || '',
            min_amount: $("#campaignMinAmount").val() || '',
            max_amount: $("#campaignMaxAmount").val() || '',
            deadline: (function() {
                var composed = buildCampaignDeadline();
                return composed.raw || '';
            })(),
            cover_upload_id: campaignCoverIdInput.val() || ''
        };
    }
    function clearCampaignFields() {
        $("#campaignTitle, #campaignSummary, #campaignGoal, #campaignMinAmount, #campaignMaxAmount, #campaignCoverId").val('');
        $("#campaignDeadlineMonth, #campaignDeadlineDay, #campaignDeadlineYear, #campaignDeadlineHour, #campaignDeadlineMinute, #campaignDeadlineSecond").val('');
        clearCampaignCover();
    }
    function fillSelect($el, options, selectedVal) {
        if (!$el || !$el.length) { return; }
        $el.empty();
        options.forEach(function(opt) {
            var option = $('<option></option>').val(opt.value).text(opt.label);
            if (String(opt.value) === String(selectedVal)) {
                option.attr('selected', 'selected');
            }
            $el.append(option);
        });
    }
    function getDaysInMonth(year, monthIndex) {
        return new Date(year, monthIndex + 1, 0).getDate();
    }
    function buildNumberOptions(start, end) {
        var opts = [];
        for (var i = start; i <= end; i++) {
            var lbl = i < 10 ? '0' + i : '' + i;
            opts.push({ value: i, label: lbl });
        }
        return opts;
    }
    function prefillCampaignDeadlineNow() {
        var now = new Date();
        var target = new Date(now.getTime() + 3600000); // default +1 hour
        var currentYear = now.getFullYear();
        var currentMonth = now.getMonth();
        var currentDay = now.getDate();
        var maxYear = currentYear + 10;

        var selectedYear = target.getFullYear();
        if (selectedYear < currentYear) { selectedYear = currentYear; }

        fillSelect($("#campaignDeadlineYear"), buildNumberOptions(currentYear, maxYear), selectedYear);

        var monthStart = (selectedYear === currentYear) ? currentMonth + 1 : 1;
        var selectedMonth = target.getMonth() + 1;
        if (selectedMonth < monthStart) { selectedMonth = monthStart; }
        fillSelect($("#campaignDeadlineMonth"), buildNumberOptions(monthStart, 12), selectedMonth);

        var daysInSelectedMonth = getDaysInMonth(selectedYear, selectedMonth - 1);
        var dayStart = (selectedYear === currentYear && selectedMonth === (currentMonth + 1)) ? currentDay : 1;
        var selectedDay = target.getDate();
        if (selectedDay < dayStart) { selectedDay = dayStart; }
        if (selectedDay > daysInSelectedMonth) { selectedDay = daysInSelectedMonth; }
        fillSelect($("#campaignDeadlineDay"), buildNumberOptions(dayStart, daysInSelectedMonth), selectedDay);

        fillSelect($("#campaignDeadlineHour"), buildNumberOptions(0, 23), target.getHours());
        fillSelect($("#campaignDeadlineMinute"), buildNumberOptions(0, 59), target.getMinutes());
        fillSelect($("#campaignDeadlineSecond"), buildNumberOptions(0, 59), target.getSeconds());
    }
    function refreshCampaignDeadlineOnChange() {
        var now = new Date();
        var currentYear = now.getFullYear();
        var currentMonth = now.getMonth();
        var currentDay = now.getDate();

        var selectedYear = parseInt($("#campaignDeadlineYear").val(), 10);
        if (isNaN(selectedYear) || selectedYear < currentYear) { selectedYear = currentYear; }

        var monthStart = (selectedYear === currentYear) ? currentMonth + 1 : 1;
        var selectedMonth = parseInt($("#campaignDeadlineMonth").val(), 10);
        if (isNaN(selectedMonth) || selectedMonth < monthStart) { selectedMonth = monthStart; }
        fillSelect($("#campaignDeadlineMonth"), buildNumberOptions(monthStart, 12), selectedMonth);

        var daysInSelectedMonth = getDaysInMonth(selectedYear, selectedMonth - 1);
        var dayStart = (selectedYear === currentYear && selectedMonth === (currentMonth + 1)) ? currentDay : 1;
        var selectedDay = parseInt($("#campaignDeadlineDay").val(), 10);
        if (isNaN(selectedDay) || selectedDay < dayStart) { selectedDay = dayStart; }
        if (selectedDay > daysInSelectedMonth) { selectedDay = daysInSelectedMonth; }
        fillSelect($("#campaignDeadlineDay"), buildNumberOptions(dayStart, daysInSelectedMonth), selectedDay);
    }
    function clearCampaignCover() {
        campaignCoverIdInput.val('');
        if (campaignCoverPreview.length) {
            campaignCoverPreview.addClass("nonePoint");
        }
        if (campaignCoverImg.length) {
            campaignCoverImg.attr('src', '');
        }
    }
    function setCampaignCover(id, url) {
        if (!id) {
            clearCampaignCover();
            return;
        }
        campaignCoverIdInput.val(id);
        if (campaignCoverImg.length && url) {
            campaignCoverImg.attr('src', url);
        }
        if (campaignCoverPreview.length) {
            campaignCoverPreview.removeClass("nonePoint");
        }
    }
    function resetCampaignUI(clearFields) {
        campaignActive = false;
        if (campaignOpenBtn.length) {
            campaignOpenBtn.removeClass("campaignActive");
        }
        if (clearFields) {
            clearCampaignFields();
        }
    }
    function closeCampaignPopup() {
        if (campaignPopup.length) {
            campaignPopup.addClass("nonePoint");
        }
    }
    function openCampaignPopup() {
        if (campaignPopup.length) {
            campaignPopup.removeClass("nonePoint");
        }
    }
    function validateCampaignFields(showErrors) {
        var values = getCampaignFieldValues();
        var messages = getCampaignMessages();
        var warning = $(".i_warning_campaign");
        var requiredMsg = warning.data("required") || 'Required';
        var goalNum = null;
        var minNum = null;
        var maxNum = null;
        var deadlineData = buildCampaignDeadline();
        var goalMinLimit = isFinite(campaignGoalMin) ? campaignGoalMin : 0;
        var goalMaxLimit = isFinite(campaignGoalMax) ? campaignGoalMax : 0;
        hideCampaignWarning();
        if (campaignTitleRequired && !values.title.trim()) {
            if (showErrors) { showCampaignWarning(messages.campaign_title_required || requiredMsg); }
            return { valid: false };
        }
        if (campaignGoalRequired && !values.goal.trim()) {
            if (showErrors) { showCampaignWarning(messages.campaign_goal_required || requiredMsg); }
            return { valid: false };
        }
        if (campaignDeadlineRequired && !values.deadline.trim()) {
            if (showErrors) { showCampaignWarning(messages.campaign_deadline_required || requiredMsg); }
            return { valid: false };
        }
        if (values.goal.trim() !== '') {
            goalNum = parseFloat(values.goal);
            if (!isFinite(goalNum) || goalNum <= 0) {
                if (showErrors) { showCampaignWarning(messages.campaign_goal_invalid || requiredMsg); }
                return { valid: false };
            }
            if (goalMinLimit && goalNum < goalMinLimit) {
                if (showErrors) { showCampaignWarning(messages.campaign_goal_below_min || requiredMsg); }
                return { valid: false };
            }
            if (goalMaxLimit && goalNum > goalMaxLimit) {
                if (showErrors) { showCampaignWarning(messages.campaign_goal_above_max || requiredMsg); }
                return { valid: false };
            }
        }
        if (values.min_amount.trim() !== '') {
            minNum = parseFloat(values.min_amount);
            if (!isFinite(minNum) || minNum < 0) {
                if (showErrors) { showCampaignWarning(messages.campaign_min_amount_invalid || requiredMsg); }
                return { valid: false };
            }
        }
        if (values.max_amount.trim() !== '') {
            maxNum = parseFloat(values.max_amount);
            if (!isFinite(maxNum) || maxNum < 0) {
                if (showErrors) { showCampaignWarning(messages.campaign_max_amount_invalid || requiredMsg); }
                return { valid: false };
            }
        }
        if (goalNum !== null && maxNum !== null && maxNum > goalNum) {
            if (showErrors) { showCampaignWarning(messages.campaign_max_exceeds_goal || requiredMsg); }
            return { valid: false };
        }
        if (minNum !== null && maxNum !== null && maxNum < minNum) {
            if (showErrors) { showCampaignWarning(messages.campaign_amount_range || requiredMsg); }
            return { valid: false };
        }
        if (values.deadline.trim() !== '') {
            if (!deadlineData.ts || deadlineData.ts <= Date.now()) {
                if (showErrors) { showCampaignWarning(messages.campaign_deadline_invalid || requiredMsg); }
                return { valid: false };
            }
        }
        if (values.cover_upload_id.trim() !== '' && !/^[0-9]+$/.test(values.cover_upload_id.trim())) {
            if (showErrors) { showCampaignWarning(messages.campaign_cover_invalid || requiredMsg); }
            return { valid: false };
        }
        return { valid: true, values: values };
    }
    function applyCampaignFocus() {
        var builder = $("#pollBuilder");
        if (builder && builder.length) {
            builder.removeClass("active").addClass("nonePoint");
        }
        if (scheduleControls && scheduleControls.length) {
            resetScheduleUI();
            scheduleControls.addClass("nonePoint");
        }
    }
    if (campaignOpenBtn && campaignOpenBtn.length) {
        campaignOpenBtn.on("click", function(e) {
            e.preventDefault();
            if (!campaignEnabled) {
                showCampaignWarning(getCampaignMessages().campaign_disabled);
                return;
            }
            hideCampaignWarning();
            prefillCampaignDeadlineNow();
            openCampaignPopup();
        });
    }
    $(document).on("change", "#campaignDeadlineYear, #campaignDeadlineMonth", function() {
        refreshCampaignDeadlineOnChange();
    });
    if (campaignPopupOverlay && campaignPopupOverlay.length) {
        campaignPopupOverlay.on("click", function() {
            closeCampaignPopup();
        });
    }
    if (campaignPopupCloseBtn && campaignPopupCloseBtn.length) {
        campaignPopupCloseBtn.on("click", function() {
            closeCampaignPopup();
        });
    }
    if (campaignCancelBtn && campaignCancelBtn.length) {
        campaignCancelBtn.on("click", function() {
            resetCampaignUI(true);
            closeCampaignPopup();
        });
    }
    function extractCoverFromHtml(html) {
        if (!html) { return null; }
        var $wrap = $('<div>').html(html);
        var $item = $wrap.find(".i_uploaded_item").first();
        if (!$item.length) { return null; }
        var id = $item.attr("id") || '';
        var imgSrc = '';
        var $img = $item.find("img.i_file").first();
        if ($img.length) {
            imgSrc = $img.attr("src") || '';
        } else {
            var bg = $item.find(".i_uploaded_file").first().css("background-image") || '';
            if (bg) {
                imgSrc = bg.replace(/^url\(["']?/, '').replace(/["']?\)$/, '');
            }
        }
        return { id: id, url: imgSrc };
    }
    function handleCampaignCoverUpload() {
        if (!campaignCoverForm.length || !campaignCoverInput.length) { return; }
        var hasFile = campaignCoverInput[0].files && campaignCoverInput[0].files.length;
        if (!hasFile) { return; }
        campaignCoverForm.ajaxForm({
            data: { f: 'upload' },
            target: '#campaignCoverTemp',
            delegation: true,
            cache: false,
            beforeSubmit: function () {
                hideCampaignWarning();
                if (campaignCoverButton.length) {
                    campaignCoverButton.prop('disabled', true).addClass('loading');
                }
            },
            success: function (response) {
                var html = campaignCoverTemp.length ? campaignCoverTemp.html() : '';
                if (!html && response) { html = response; }
                var parsed = extractCoverFromHtml(html);
                if (parsed && parsed.id) {
                    setCampaignCover(parsed.id, parsed.url);
                } else {
                    showCampaignWarning(getCampaignMessages().campaign_cover_invalid || 'Invalid cover');
                    clearCampaignCover();
                }
                if (campaignCoverTemp.length) {
                    campaignCoverTemp.empty();
                }
            },
            error: function (xhr, status, error) {
                handleUploadError(xhr, status, error);
            },
            complete: function () {
                if (campaignCoverButton.length) {
                    campaignCoverButton.prop('disabled', false).removeClass('loading');
                }
                if (campaignCoverInput.length) {
                    campaignCoverInput.val('');
                }
            }
        }).submit();
    }
    if (campaignCoverButton.length) {
        campaignCoverButton.on("click", function (e) {
            e.preventDefault();
            if (campaignCoverInput.length) {
                campaignCoverInput.trigger("click");
            }
        });
    }
    if (campaignCoverDrop.length) {
        campaignCoverDrop.on("click", function () {
            if (campaignCoverInput.length) {
                campaignCoverInput.trigger("click");
            }
        });
    }
    if (campaignCoverInput.length) {
        campaignCoverInput.on("change", function () {
            handleCampaignCoverUpload();
        });
    }
    if (campaignCoverRemove.length) {
        campaignCoverRemove.on("click", function (e) {
            e.preventDefault();
            clearCampaignCover();
        });
    }
    $(document).on("input", ".campaignNumber", function() {
        var val = $(this).val() || '';
        val = val.replace(/,/g, '.').replace(/[^0-9.]/g, '');
        val = val.replace(/(\..*)\./g, '$1');
        $(this).val(val);
    });
    if (campaignSaveBtn && campaignSaveBtn.length) {
        campaignSaveBtn.on("click", function() {
            if (!campaignEnabled) {
                showCampaignWarning(getCampaignMessages().campaign_disabled);
                return;
            }
            var validation = validateCampaignFields(true);
            if (!validation.valid) {
                return;
            }
            campaignActive = true;
            if (campaignOpenBtn.length) {
                campaignOpenBtn.addClass("campaignActive");
            }
            applyCampaignFocus();
            closeCampaignPopup();
            setQuickActionsState(false);
            // Trigger publish with current campaign data
            var publishBtn = $(".publish").first();
            if (publishBtn && publishBtn.length) {
                publishBtn.trigger("click");
            }
        });
    }

    /*Save New Post*/
    $(document).on("click", ".publish", function() {
        var text = $("#newPostT").val();
        var files = $("#uploadVal").val();
        var point = $("#point").val();
        var type = 'newPost';
        var pollBuilder = $("#pollBuilder");
        var pollWarning = $(".i_warning_poll");
        var isPoll = pollBuilder.length && pollBuilder.hasClass("active");
        var isCampaign = campaignEnabled && campaignActive;
        var campaignValidation = null;
        var communityId = $("#moreType").attr("data-community");
        var isCommunity = ($("#moreType").attr("data-type") === "community");
        var pollOptions = [];
        var csrfToken = $("#poll_csrf_token").val() || $('meta[name=csrf-token]').attr('content');
        var isScheduled = (scheduleToggle.length && scheduleToggle.is(":checked") && scheduleActive);
        var scheduleTimestamp = null;
        if (isCampaign) {
            isPoll = false;
            isScheduled = false;
            campaignValidation = validateCampaignFields(true);
            if (!campaignValidation.valid) {
                return;
            }
            if (scheduleControls && scheduleControls.length) {
                resetScheduleUI();
                scheduleControls.addClass("nonePoint");
            }
        }
	        if (isScheduled) {
	            if (!scheduleEnabled) {
	                showScheduleError(scheduleControls.data("disabled") || '', 'disabled');
	                return;
	            }
            var scheduleVal = scheduleInput.val();
            if (scheduleVal) {
                var parsedDate = new Date(scheduleVal);
                if (!isNaN(parsedDate.getTime())) {
                    scheduleTimestamp = Math.floor(parsedDate.getTime() / 1000);
                }
            }
	            var nowTs = Math.floor(Date.now() / 1000);
	            if (!scheduleTimestamp || scheduleTimestamp <= nowTs || (scheduledMaxDays && scheduleTimestamp > (nowTs + (scheduledMaxDays * 86400)))) {
	                showScheduleError(scheduleControls.data("invalid") || '', 'invalid');
	                return;
	            }
	        }
        if (isPoll) {
            pollBuilder.find(".poll_option_field").each(function() {
                var val = $(this).val().trim();
                if (val) {
                    pollOptions.push(val);
                }
            });
            var pollMax = parseInt($("#poll_max_options").val() || pollBuilder.data("max") || 6);
            var pollMin = parseInt($("#poll_min_options").val() || pollBuilder.data("min") || 2);
            if (pollOptions.length < pollMin) {
                pollWarning.text(pollBuilder.data("msg-min")).fadeIn();
                return;
            }
            if (pollOptions.length > pollMax) {
                pollWarning.text(pollBuilder.data("msg-max")).fadeIn();
                return;
            }
            pollWarning.hide();
        } else {
            pollWarning.hide();
        }
        var data = 'f=' + type + '&txt=' + encodeURIComponent(text) + '&file=' + files + '&point=' + point;
        if (isCommunity && communityId) {
            data += '&community_id=' + encodeURIComponent(communityId);
        }
        if (isCampaign) {
            var campaignPayload = campaignValidation && campaignValidation.values ? campaignValidation.values : getCampaignFieldValues();
            data = 'f=newCampaign'
                + '&title=' + encodeURIComponent(campaignPayload.title)
                + '&summary=' + encodeURIComponent(campaignPayload.summary)
                + '&goal=' + encodeURIComponent(campaignPayload.goal)
                + '&min_amount=' + encodeURIComponent(campaignPayload.min_amount)
                + '&max_amount=' + encodeURIComponent(campaignPayload.max_amount)
                + '&deadline=' + encodeURIComponent(campaignPayload.deadline)
                + '&cover_upload_id=' + encodeURIComponent(campaignPayload.cover_upload_id);
            if (isCommunity && communityId) {
                data += '&community_id=' + encodeURIComponent(communityId);
            }
            if (csrfToken) {
                data += '&csrf_token=' + encodeURIComponent(csrfToken);
            }
        }
        if (isPoll) {
            data += '&is_poll=1';
            $.each(pollOptions, function(index, value) {
                data += '&poll_options[' + index + ']=' + encodeURIComponent(value);
            });
        }
        if (isScheduled) {
            data += '&is_scheduled=1&scheduled_at=' + scheduleTimestamp;
        }
        if (csrfToken && (isPoll || isScheduled)) {
            data += '&csrf_token=' + encodeURIComponent(csrfToken);
        }
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {
                $('.publish').prop('disabled', true);
                $(".publish").css("pointer-events", "none");
                $(".i_warning_point , .i_warning , .i_warning_prmfl, .i_warning_poll").fadeOut(100);
                $(".i_warning_schedule").fadeOut(100);
                $(".i_warning_campaign").fadeOut(100);
            },
            success: function(response) {
                $('.publish').prop('disabled', false);
                $(".publish").css("pointer-events", "auto");
                if ($("div").hasClass("noPost")) {
                    $(".noPost").remove();
                }
                var campaignMessages = getCampaignMessages();
                if (response === 'schedule_disabled' || response === 'invalid_time' || response === 'schedule_failed') {
                    var scheduleMsg = scheduleControls.data("invalid");
                    var scheduleErrType = 'invalid';
                    if (response === 'schedule_disabled') {
                        scheduleMsg = scheduleControls.data("disabled");
                        scheduleErrType = 'disabled';
                    } else if (response === 'schedule_failed') {
                        scheduleMsg = scheduleControls.data("invalid");
                    }
                    showScheduleError(scheduleMsg || '', scheduleErrType);
                    return;
                }
                var parsed = null;
                if (typeof response === "string") {
                    try { parsed = JSON.parse(response); } catch (e) {}
                }
                if (parsed && parsed.status === 'ok' && parsed.slug) {
                    window.location.href = siteurl + 'post/' + parsed.slug;
                    return;
                }
                if (parsed && parsed.status === 'scheduled') {
                    var createdTpl = scheduleControls.data("created") || parsed.message || '';
                    var finalMsg = createdTpl;
                    if (parsed.scheduled_for_text) {
                        finalMsg = createdTpl.replace('{time}', parsed.scheduled_for_text);
                    }
                    if (scheduleWarning.length) { scheduleWarning.hide(); }
                    if (scheduleInlineWarning.length) { scheduleInlineWarning.addClass("nonePoint").text(''); }
                    if (scheduleControls.length) {
                        scheduleControls.removeClass("nonePoint");
                    }
                    if (scheduleSelection.length) {
                        scheduleSelection.removeClass("nonePoint");
                    }
                    if (scheduleSelectedTime.length) {
                        scheduleSelectedTime.text(finalMsg || createdTpl);
                    }
                    var scheduledCard = function(data) {
                        var target = $("#moreType");
                        if (!target.length) { return; }
                        var postId = data.post_id || Date.now();
                        target.find('.scheduled_preview[data-post-id="' + postId + '"]').remove();
                        var badge = $('<div class="scheduled_preview" data-post-id="' + postId + '"></div>');
                        var timeLabel = data.scheduled_for_text || '';
                        var title = $('<div class="scheduled_preview_title"></div>').text(scheduleControls.data("created") ? (scheduleControls.data("created").replace('{time}', timeLabel)) : finalMsg);
                        var meta = $('<div class="scheduled_preview_meta"></div>').text(timeLabel);
                        badge.append(title).append(meta);
                        target.prepend(badge);
                    };
                    scheduledCard(parsed);
                    scheduleActive = false;
                    if (scheduleToggle.length) {
                        scheduleToggle.prop('checked', false);
                    }
                    $(".i_uploaded_file_box").html('');
                    $(".i_uploaded_iv").hide();
                    $(".newPostT").val('').trigger('change');
                    $("#uploadVal").val('');
                    $("#point").val('');
                    if (scheduleInlineWarning.length) { scheduleInlineWarning.addClass("nonePoint").text(''); }
                    if (isPoll) {
                        resetPollBuilder();
                    }
                    return;
                }
                if (response == '200') {
                    $(".i_warning").fadeIn();
                } else if (response == '201') {
                    $(".i_warning_point").fadeIn();
                } else if (response == '203') {
                    $(".i_warning_point_two").fadeIn();
                } else if (response == '202') {
                    $(".i_warning_prmfl").fadeIn();
                } else if (response == '204') {
                    PopUPAlerts('sWrong', 'ialert');
                } else if (response === 'poll_question_required' || response === 'poll_options_required' || response === 'poll_min_options') {
                    pollWarning.text(pollBuilder.data("msg-question") || pollBuilder.data("msg-min")).fadeIn();
                } else if (response === 'poll_max_options') {
                    pollWarning.text(pollBuilder.data("msg-max")).fadeIn();
                } else if (response === 'poll_poll_disabled') {
                    pollWarning.text(pollBuilder.data("msg-disabled")).fadeIn();
                } else if (response === 'poll_no_files') {
                    pollWarning.text(pollBuilder.data("msg-files")).fadeIn();
                } else if (response === 'poll_poll_create_failed') {
                    PopUPAlerts('sWrong', 'ialert');
                } else if (response === 'community_post_forbidden' || response === 'community_posting_disabled' || response === 'community_not_found' || response === 'community_post_failed') {
                    PopUPAlerts(response, 'ialert');
                } else if (response && typeof response === 'string' && response.indexOf('campaign_') === 0) {
                    var msg = campaignMessages[response] || response;
                    showCampaignWarning(msg);
                } else {
                    $(".i_uploaded_file_box").html('');
                    $(".i_uploaded_iv").hide();
                    const $newContent = $(response);
                    $("#moreType").prepend($newContent);

                    reInitLightGallery($newContent);
                    reInitPostPlugins($newContent);
                    initImageBackgrounds('.i_post_image_swip_wrapper', $newContent);
                    $(".newPostT").val('').trigger('change');
                    $("#uploadVal").val('');
                    $("#point").val('');
                    if (isPoll) {
                        resetPollBuilder();
                    }
                    if (isCampaign) {
                        resetCampaignUI(true);
                        closeCampaignPopup();
                    }
                }
            }
        });
    });
    function applyPollPayload($wrapper, poll) {
        if (!$wrapper || !$wrapper.length || !poll) { return; }
        var totalLabel = $wrapper.data('total-label') || '{count}';
        $wrapper.attr('data-enabled', poll.enabled ? '1' : '0');
        var optionsMap = {};
        $.each(poll.options || [], function(_, option) {
            optionsMap[parseInt(option.option_id, 10)] = option;
        });
        $wrapper.find('.poll_option_item').each(function() {
            var $item = $(this);
            var optionId = parseInt($item.data('option'), 10);
            var option = optionsMap[optionId];
            if (!option) { return; }
            var percent = option.percentage || 0;
            $item.toggleClass('poll_option_voted', poll.user_vote && parseInt(poll.user_vote, 10) === optionId);
            $item.find('.poll_option_percent').text(percent + '%');
            $item.find('.poll_option_bar_fill').css('width', percent + '%');
            var countLabel = option.votes_label || option.votes || 0;
            $item.find('.poll_option_count').text(countLabel);
            var $avatars = $item.find('.poll_option_avatars');
            if ($avatars && $avatars.length) {
                $avatars.empty();
                var voters = option.recent_voters || [];
                $.each(voters, function(_, v) {
                    if (v && v.avatar) {
                        var img = $('<span class="poll_avatar"><img alt=""></span>');
                        img.find('img').attr('src', v.avatar);
                        $avatars.append(img);
                    }
                });
            }
        });
        var votesText = totalLabel.replace('{count}', poll.total_votes || 0);
        $wrapper.find('.poll_votes').text(votesText);
        if (poll.user_vote) {
            $wrapper.find('.poll_voted_text').show();
        } else {
            $wrapper.find('.poll_voted_text').hide();
        }
        if (poll.has_removed_options) {
            $wrapper.find('.poll_removed_note').show();
        }
    }
    function escapeHtmlSafe(text) {
        return String(text === null ? '' : text).replace(/[&<>"'`]/g, function(chr) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
                '`': '&#96;'
            }[chr] || chr;
        });
    }
    function buildPollWrapperFromData(poll, $existingWrapper) {
        if (!poll) { return null; }
        var totalLabel = ($existingWrapper && $existingWrapper.data('total-label')) || '{count}';
        var disabledText = ($existingWrapper && $existingWrapper.find('.poll_disabled_note').text()) || '';
        var votedText = '';
        if ($existingWrapper && $existingWrapper.find('.poll_voted_text').length) {
            votedText = $existingWrapper.find('.poll_voted_text').first().text();
        }
        var removedText = ($existingWrapper && $existingWrapper.find('.poll_removed_note').length) ? $existingWrapper.find('.poll_removed_note').text() : '';
        var emptyText = ($existingWrapper && $existingWrapper.hasClass('poll_empty')) ? $existingWrapper.text() : '';
        if (!disabledText) {
            disabledText = $(".poll_disabled_note").first().text() || disabledText;
        }
        if (!votedText) {
            votedText = $(".poll_voted_text").not(".poll_removed_note").first().text() || votedText;
        }
        if (!removedText) {
            removedText = $(".poll_removed_note").first().text() || removedText;
        }
        if (!emptyText) {
            emptyText = $(".poll_wrapper.poll_empty").first().text() || emptyText;
        }
        var enabledAttr = poll.enabled ? '1' : '0';
        if (!poll.options || !poll.options.length) {
            var $empty = $('<div class="poll_wrapper poll_empty flex_ tabing_non_justify"></div>');
            $empty.attr({
                'data-enabled': enabledAttr,
                'data-poll': poll.poll_id || '',
                'data-post': poll.post_id || '',
                'data-total-label': totalLabel
            });
            $empty.text(emptyText || 'Poll options missing');
            return $empty;
        }
        var $wrapper = $('<div class="poll_wrapper"></div>');
        $wrapper.attr({
            'data-enabled': enabledAttr,
            'data-poll': poll.poll_id || '',
            'data-post': poll.post_id || '',
            'data-total-label': totalLabel
        });
        if (!poll.enabled && disabledText) {
            $wrapper.append('<div class="poll_disabled_note">' + escapeHtmlSafe(disabledText) + '</div>');
        }
        $.each(poll.options, function(_, option) {
            var percent = option.percentage || 0;
            var countLabel = option.votes_label || option.votes || 0;
            var $item = $('<div class="poll_option_item transition"></div>');
            $item.attr('data-option', option.option_id || '');
            if (poll.user_vote && parseInt(poll.user_vote, 10) === parseInt(option.option_id, 10)) {
                $item.addClass('poll_option_voted');
            }
            var $top = $('<div class="poll_option_top flex_ tabing_non_justify"></div>');
            $top.append('<div class="poll_option_text truncated_two">' + escapeHtmlSafe(option.option_text || '') + '</div>');
            var $stats = $('<div class="poll_option_stats flex_ tabing_non_justify"></div>');
            var $avatars = $('<div class="poll_option_avatars flex_"></div>');
            if (option.recent_voters && option.recent_voters.length) {
                $.each(option.recent_voters, function(_, voter) {
                    if (!voter || !voter.avatar) { return; }
                    var $avatar = $('<span class="poll_avatar"><img alt=""></span>');
                    $avatar.find('img').attr('src', voter.avatar);
                    $avatars.append($avatar);
                });
            }
            $stats.append($avatars);
            $stats.append('<div class="poll_option_count">' + escapeHtmlSafe(countLabel) + '</div>');
            $stats.append('<div class="poll_option_percent">' + escapeHtmlSafe(percent) + '%</div>');
            $top.append($stats);
            $item.append($top);
            var $bar = $('<div class="poll_option_bar"><div class="poll_option_bar_fill"></div></div>');
            $bar.find('.poll_option_bar_fill').css('width', percent + '%');
            $item.append($bar);
            $wrapper.append($item);
        });
        var votesText = totalLabel.replace('{count}', poll.total_votes || 0);
        var $meta = $('<div class="poll_meta flex_ tabing_non_justify"></div>');
        $meta.append('<div class="poll_votes">' + escapeHtmlSafe(votesText) + '</div>');
        if (poll.user_vote && votedText) {
            $meta.append('<div class="poll_voted_text">' + escapeHtmlSafe(votedText) + '</div>');
        }
        if (poll.has_removed_options && removedText) {
            $meta.append('<div class="poll_voted_text poll_removed_note">' + escapeHtmlSafe(removedText) + '</div>');
        }
        $wrapper.append($meta);
        return $wrapper;
    }
    function refreshPollAfterEdit(postId, poll) {
        if (!postId || !poll) { return; }
        var $postContainer = $("#i_post_container_" + postId);
        if (!$postContainer.length) { return; }
        var $currentWrapper = $postContainer.nextAll(".poll_wrapper").first();
        var $newWrapper = buildPollWrapperFromData(poll, $currentWrapper);
        if (!$newWrapper || !$newWrapper.length) { return; }
        if ($currentWrapper.length) {
            $currentWrapper.replaceWith($newWrapper);
        } else {
            $postContainer.after($newWrapper);
        }
    }
    $(document).on("click", ".poll_option_item", function() {
        var $pollWrapper = $(this).closest(".poll_wrapper");
        if (!$pollWrapper.length) { return; }
        if ($pollWrapper.attr("data-enabled") !== "1") { return; }
        if ($pollWrapper.hasClass("poll_submitting")) { return; }
        var optionId = $(this).data("option");
        var pollId = $pollWrapper.data("poll");
        if (!optionId || !pollId) { return; }
        var csrfToken = $("#poll_csrf_token").val() || $('meta[name=csrf-token]').attr('content');
        $pollWrapper.addClass("poll_submitting");
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            dataType: 'text',
            data: { f: 'poll_vote', poll_id: pollId, option_id: optionId, csrf_token: csrfToken },
            success: function(resp) {
                $pollWrapper.removeClass("poll_submitting");
                if (typeof resp === 'string' && resp.indexOf('poll_') === 0) {
                    if (resp === 'poll_already_voted') {
                        PopUPAlerts('poll_once', 'ialert');
                    } else if (resp === 'poll_poll_locked') {
                        PopUPAlerts('poll_locked', 'ialert');
                    } else if (resp === 'poll_poll_disabled') {
                        $pollWrapper.attr('data-enabled', '0');
                        PopUPAlerts('poll_disabled_now', 'ialert');
                    } else {
                        PopUPAlerts('sWrong', 'ialert');
                    }
                    return;
                }
                var parsed = null;
                try { parsed = JSON.parse(resp); } catch (e) {}
                if (!parsed || parsed.status !== 'success' || !parsed.poll) {
                    PopUPAlerts('sWrong', 'ialert');
                    return;
                }
                applyPollPayload($pollWrapper, parsed.poll);
            },
            error: function() {
                $pollWrapper.removeClass("poll_submitting");
                PopUPAlerts('sWrong', 'ialert');
            }
        });
    });
    $(document).on("click", ".publishReels", function() {
        var text = $("#newPostT").val();
        var files = $("#uploadVal").val();
        var point = $("#point").val();
        var type = 'insertNewReel';
        var data = 'f=' + type + '&txt=' + encodeURIComponent(text) + '&file=' + files + '&point=' + point;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {
                $('.publish').prop('disabled', true);
                $(".publish").css("pointer-events", "none");
                $(".i_warning_point , .i_warning , .i_warning_prmfl").fadeOut(100);
            },
            success: function(response) {
                $('.publish').prop('disabled', false);
                $(".publish").css("pointer-events", "auto");

                if (response === '200') {
                    $(".i_warning").fadeIn();
                } else if (response === '201') {
                    $(".i_warning_point").fadeIn();
                } else if (response === '203') {
                    $(".i_warning_point_two").fadeIn();
                } else if (response === '202') {
                    $(".i_warning_prmfl").fadeIn();
                } else if (response === '204') {
                    PopUPAlerts('sWrong', 'ialert');
                } else if (response.startsWith("REELS_ID:")) {
                    var newReelId = response.replace("REELS_ID:", "");
                    window.location.href = siteurl + 'reels/' + newReelId;
                } else {
                    $(".i_uploaded_file_box").html('');
                    $(".i_uploaded_iv").hide();
                    const $newContent = $(response);
                    $("#moreType").prepend($newContent);

                    reInitLightGallery($newContent);
                    reInitPostPlugins($newContent);
                    initImageBackgrounds('.i_post_image_swip_wrapper', $newContent);
                    $(".newPostT").val('').trigger('change');
                    $("#uploadVal").val('');
                    $("#point").val('');
                }
            }
        });
    });
    /*Like Post*/
    $(document).on("click", ".in_like , .in_unlike", function() {
        var type = 'p_like';
        var ID = $(this).attr('data-id');
        var data = 'f=' + type + '&post=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            dataType: "json",
            data: data,
            cache: false,
            beforeSend: function() {
                $('.in_like , .in_unlike').prop('disabled', true);
            },
            success: function(response) {
                var status = response.status;
                var statusIcon = response.like;
                var liksCount = response.likeCount;
                if (status == 'in_unlike') {
                    $("#p_l_" + ID).removeClass("in_like").addClass("in_unlike");
                    $("#lp_sum_" + ID).html(liksCount);

                    var $postID = $(".body_" + ID);
                    var $existingLikeHeart = $postID.find('.like_heart');

                    if ($existingLikeHeart.length > 0) {
                        $existingLikeHeart.fadeOut(300, function() {
                            $(this).remove();
                        });
                        clearTimeout($postID.data('likeTimer'));
                    } else {
                        $postID.append(likeBox);
                        var likeTimer = setTimeout(() => {
                            $postID.find(".like_heart").fadeOut(300, function() {
                                $(this).remove();
                            });
                        }, 450);
                        $postID.data('likeTimer', likeTimer);
                    }

                } else {
                    $("#p_l_" + ID).removeClass("in_unlike").addClass("in_like");
                    $("#lp_sum_" + ID).html(liksCount);

                    var $postID = $(".body_" + ID);
                    var $existingLikeHeart = $postID.find('.like_heart');

                    if ($existingLikeHeart.length > 0) {
                        $existingLikeHeart.fadeOut(300, function() {
                            $(this).remove();
                        });
                        clearTimeout($postID.data('likeTimer'));
                    } else {
                        $postID.append(UnlikeBox);
                        var likeTimer = setTimeout(() => {
                            $postID.find(".like_heart").fadeOut(300, function() {
                                $(this).remove();
                            });
                        }, 450);
                        $postID.data('likeTimer', likeTimer);
                    }

                }
                $("#p_l_" + ID).html(statusIcon);
                $('.in_like , .in_unlike').prop('disabled', false);
            }
        });
    });
    /*Call Post for Share*/
    $(document).on("click", ".in_share", function() {
        var ID = $(this).attr("data-id");
        var type = 'p_share';
        if (!$(".i_bottom_left_alert_container")[0]) {
            var data = 'f=' + type + '&sp=' + ID;
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                cache: false,
                beforeSend: function() {
                    $('.in_share').prop('disabled', true);
                },
                success: function(response) {
                    if (response != '404') {
                        $("body").append(response);

                        setTimeout(() => {
                            $(".i_modal_bg_in").addClass('i_modal_display_in');
                            $(".more_textarea").focus();
                            const $newContent = $(".i_modal_bg_in").last();

                            initImageBackgrounds('.i_post_image_swip_wrapper', $newContent);
                            reInitPostPlugins($newContent);
                            initGalleriesInDOM($newContent);
                        }, 200);
                    } else if (response == '404') {
                        PopUPAlerts('not_Shared', 'ialert');
                    }
                    $('.in_share').prop('disabled', false);
                }
            });
        }
    });

    function PopUPAlerts(ialert, type) {
        var data = 'f=' + type + '&al=' + ialert;
        if (!$(".i_bottom_left_alert_container")[0]) {
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                cache: false,
                beforeSend: function() {

                },
                success: function(response) {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_bottom_left_alert_container").addClass('fadeOutDown');
                    }, 5000);
                    setTimeout(() => {
                        $(".i_bottom_left_alert_container").remove();
                    }, 5000);
                }
            });
        }
    }
    /*Save Re-Share Post*/
    $(document).on("click", ".re-share", function() {
        var ID = $(this).attr("id");
        var type = 'p_rshare';
        var postText = $(".more_textarea").val();
        var data = 'f=' + type + '&sp=' + ID + '&pt=' + encodeURIComponent(postText);
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response == '200') {
                    $(".i_modal_in_in").addClass("i_modal_in_in_out");
                    setTimeout(() => {
                        $(".i_modal_bg_in").remove();
                    }, 100);
                } else {
                    PopUPAlerts('not_Shared', 'ialert');
                }
            }
        });
    });
    /*Delete Post From Database*/
    $(document).on("click", ".yes-del", function() {
        var type = 'deletePost';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 100);
                if (response == '200') {
                    $(".body_" + ID).fadeOut();
                    PopUPAlerts('delete_success', 'ialert');
                } else {
                    PopUPAlerts('delete_not_success', 'ialert');
                }
            }
        });
    });
    $(document).on("click", ".shareClose , .no-del , .popClose , .svAC", function() {
        $(".i_modal_in_in").addClass("i_modal_in_in_out");
        setTimeout(() => {
            $(".i_modal_bg_in").remove();
        }, 200);
    });
    /*Update Comment Status*/
    $(document).on("click", ".pcl", function() {
        var type = 'updateComentStatus';
        var ID = $(this).attr('data-id');
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            dataType: "json",
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var status = response.status;
                var statusIcon = response.text;
                if (status) {
                    if (status == '200') {
                        PopUPAlerts('commentClosed', 'ialert');
                    } else {
                        PopUPAlerts('commentOpened', 'ialert');
                    }
                    $("#dc_" + ID).html(statusIcon);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Pin Post*/
    $(document).on("click", ".i_pnp", function() {
        var type = 'pinpost';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            dataType: "json",
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var status = response.status;
                var statusIcon = response.text;
                var btns = response.btn;
                if (status) {
                    if (status == '200') {
                        PopUPAlerts('pined', 'ialert');
                        $(".body_" + ID).append(statusIcon);
                    } else {
                        PopUPAlerts('pinClosed', 'ialert');
                        $("#i_pined_post_" + ID).remove();
                    }
                    $(".pbtn_" + ID).html(btns);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Report Post*/
    $(document).on("click", ".rpp", function() {
        var type = 'reportPost';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            dataType: "json",
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var status = response.status;
                var statusIcon = response.text;
                if (status) {
                    if (status == '200') {
                        $(".rpp" + ID).html(statusIcon);
                    } else if (status == '404') {
                        $(".rpp" + ID).html(statusIcon);
                    }
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Report Post*/
    $(document).on("click", ".svp", function() {
        var type = 'savePost';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            dataType: "json",
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var status = response.status;
                var statusIcon = response.text;
                if (status) {
                    if (status == '200') {
                        $(".in_save_" + ID).html(statusIcon);
                        PopUPAlerts('sAdded', 'ialert');
                    } else if (status == '404') {
                        $(".in_save_" + ID).html(statusIcon);
                        PopUPAlerts('sRemoved', 'ialert');
                    }
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Click To Send Comment*/
    $(document).on("click", ".sndcom", function() {
        var type = 'comment';
        var ID = $(this).attr('id');
        var value = '';
        var $input = getCommentInput(ID);
        if ($input.length) {
            value = $input.val();
        }
        var stickerID = $("#stic_" + ID).val();
        var gif = $("#cgif_" + ID).val();
        Comment(ID, value, type, stickerID, gif);
    });
    $(document).on('keydown', ".nwComment", function(e) {
        var key = e.which || e.keyCode || 0;
        if (key == 13) {
            var type = 'comment';
            var ID = $(this).attr('data-id');
            var value = '';
            var $input = getCommentInput(ID);
            if ($input.length) {
                value = $input.val();
            }
            var stickerID = $("#stic_" + ID).val();
            var gif = $("#cgif_" + ID).val();
            Comment(ID, value, type, stickerID, gif);
        }
    });
    /*Send Gif Comment*/
    $(document).on("click", ".rGif", function() {
        var src = $(this).attr("src");
        var ID = $(this).attr("data-id");
        var $input = getCommentInput(ID);
        if (!$input.length || $input.val() === '') {
            Comment(ID, '', 'comment', '', src);
        } else {
            $(".emptyGifArea" + ID).show();
            $(".srcGif" + ID).attr('src', src);
            $("#cgif_" + ID).val(src);
            $(".stickersContainer").remove();
        }
    });
    /*Add Sticker*/
    $(document).on("click", ".addSticker", function() {
        var type = 'addSticker';
        var ID = $(this).attr("id");
        var dataID = $(this).attr("data-id");
        var data = 'f=' + type + '&id=' + ID + '&pi=' + dataID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {
                $(".emptyStickerArea" + dataID).html('');
            },
            success: function(response) {
                var sticker_url = response.stickerUrl;
                var stickerID = response.st_id;
                if (sticker_url) {
                    $(".stickersContainer").remove();
                    var $input = getCommentInput(dataID);
                    if (!$input.length || $input.val() === '') {
                        Comment(dataID, '', 'comment', stickerID);
                    } else {
                        $(".emptyStickerArea" + dataID).append(sticker_url);
                        $("#stic_" + dataID).val(stickerID);
                    }
                }
            }
        });
    });

    function getCommentInput(ID) {
        var $input = $("#comment" + ID);
        if ($input.length === 0) {
            $input = $(".comment_reel_item_" + ID);
        }
        return $input;
    }

    function clearReplyContext(ID) {
        var $context = $(".reply_context_" + ID);
        if ($context.length) {
            $context.hide();
            $context.find(".reply_context_text").text('');
        }
        $("#reply_" + ID).val('0');
    }

    function setReplyContext(postID, commentID, displayName) {
        var $context = $(".reply_context_" + postID);
        if (!$context.length) {
            return;
        }
        var template = $context.attr('data-template') || 'Replying to {user}';
        var label = template.replace('{user}', displayName || '');
        $context.find(".reply_context_text").text(label);
        $context.show();
        $("#reply_" + postID).val(commentID);
    }

    /*Comment*/
    function Comment(ID, value, type, sticker, gif) {
        var replyId = parseInt($("#reply_" + ID).val() || '0', 10);
        var data = 'f=' + type + '&id=' + ID + '&val=' + encodeURIComponent(value) + '&sticker=' + sticker + '&gf=' + gif + '&reply_to=' + replyId;
        var csrfToken = $('meta[name=csrf-token]').attr('content') || $("#poll_csrf_token").val();
        if (csrfToken) {
            data += '&csrf_token=' + encodeURIComponent(csrfToken);
        }
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {},
            success: function(response) {
                if ((response || '').toString().toLowerCase().indexOf('csrf') !== -1) {
                    window.location.reload();
                    return;
                }
                if (response === 'comment_restricted') {
                    PopUPAlerts('comments_restricted', 'ialert');
                } else if (response == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                } else if (response) {
                    var $response = $(response);
                    var $commentEl = $response.filter(".i_u_comment_body");
                    if (!$commentEl.length) {
                        $commentEl = $response.find(".i_u_comment_body");
                    }
                    var parentId = parseInt($commentEl.attr('data-parent-id') || $commentEl.data('parent-id') || 0, 10);
                    if (parentId > 0) {
                        var $replies = $("#comment_replies_" + parentId);
                        var $toggle = $(".toggleReplies[data-id='" + parentId + "']");
                        if ($replies.length) {
                            $replies.append($commentEl);
                        }
                        if ($toggle.length) {
                            var count = parseInt($toggle.attr('data-count') || '0', 10) + 1;
                            $toggle.attr('data-count', count);
                            var openTemplate = $toggle.attr('data-open-template') || $toggle.attr('data-open-text') || 'View replies ({count})';
                            var openText = openTemplate.replace('{count}', count);
                            $toggle.attr('data-open-text', openText);
                            var closeText = $toggle.attr('data-close-text') || 'Hide replies';
                            $toggle.text(closeText).show();
                            $replies.show();
                        }
                    } else {
                        $("#i_user_comments_" + ID).append($commentEl);
                    }
                    if ($commentEl && $commentEl.length) {
                        initExpandableText($commentEl);
                    }
                }
                getCommentInput(ID).val('');
                $(".stickersContainer").remove();
                $(".emptyStickerArea" + ID).empty();
                $("#stic_" + ID).val('');
                $(".emptyGifArea" + ID).hide();
                $(".srcGif" + ID).attr('src', '');
                $("#cgif_" + ID).val('');
                clearReplyContext(ID);
            }
        });
    }
    $(document).on("click", ".cancel_reply", function() {
        var ID = $(this).attr('data-id');
        clearReplyContext(ID);
    });
    $(document).on("click", ".toggleReplies", function() {
        var $toggle = $(this);
        var parentId = $toggle.attr('data-id');
        var $replies = $("#comment_replies_" + parentId);
        if (!$replies.length) {
            return;
        }
        if ($replies.is(":visible")) {
            $replies.hide();
            $toggle.text($toggle.attr('data-open-text') || $toggle.text());
        } else {
            $replies.show();
            $toggle.text($toggle.attr('data-close-text') || $toggle.text());
        }
    });
    $(document).on("click", ".removeSticker", function() {
        var ID = $(this).attr('id');
        $(".emptyStickerArea" + ID).empty();
        $("#stic_" + ID).val('');
    });
    $(document).on("click", ".removeGif", function() {
        var ID = $(this).attr('id');
        $(".emptyGifArea" + ID).hide();
        $(".srcGif" + ID).attr('src', '');
        $("#cgif_" + ID).val('');
    });
    /*Like Post*/
    $(document).on("click", ".c_in_like , .c_in_unlike", function() {
        var type = 'pc_like';
        var ID = $(this).attr('data-id');
        var pID = $(this).attr("data-p");
        var data = 'f=' + type + '&post=' + pID + '&com=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            dataType: "json",
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var status = response.status;
                var statusIcon = response.like;
                var statusTotalLike = response.totalLike;
                if (status == 'c_in_unlike') {
                    $(".c_in_l_" + ID).removeClass("c_in_like").addClass("c_in_unlike");
                } else {
                    $(".c_in_l_" + ID).removeClass("c_in_unlike").addClass("c_in_like");
                }
                $("#t_c_" + ID).html(statusTotalLike);
                $(".c_in_l_" + ID).html(statusIcon);
            }
        });
    });
    /*Call Delete Post PopUpBox*/
    $(document).on("click", ".delCm", function() {
        var type = 'ddelComment';
        var ID = $(this).attr("id");
        var postID = $(this).attr("data-id");
        var data = 'f=' + type + '&id=' + ID + '&pid=' + postID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Delete Comment*/
    $(document).on("click", ".dlCm", function() {
        var type = 'deletecomment';
        var ID = $(this).attr("id");
        var postID = $(this).attr("data-id");
        var data = 'f=' + type + '&cid=' + ID + '&pid=' + postID;
        var csrfToken = $('meta[name=csrf-token]').attr('content') || $("#poll_csrf_token").val();
        if (csrfToken) {
            data += '&csrf_token=' + encodeURIComponent(csrfToken);
        }
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {},
            success: function(response) {
                if ((response || '').toString().toLowerCase().indexOf('csrf') !== -1) {
                    window.location.reload();
                    return;
                }
                if (response == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                } else {
                    $(".i_modal_in_in").addClass("i_modal_in_in_out");
                    setTimeout(() => {
                        $(".i_modal_bg_in").remove();
                    }, 100);
                    if (response == '200') {
                        $(".dlCm" + ID).fadeOut();
                        PopUPAlerts('delete_comment_success', 'ialert');
                    } else {
                        PopUPAlerts('delete_comment_not_success', 'ialert');
                    }
                }
            }
        });
    });
    /*Report Comment*/
    $(document).on("click", ".ccp", function() {
        var type = 'reportComment';
        var commentID = $(this).attr("id");
        var postID = $(this).attr("data-id");
        var data = 'f=' + type + '&id=' + commentID + '&pid=' + postID;
        var csrfToken = $('meta[name=csrf-token]').attr('content') || $("#poll_csrf_token").val();
        if (csrfToken) {
            data += '&csrf_token=' + encodeURIComponent(csrfToken);
        }
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            dataType: "json",
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response && response.message && response.message.toString().toLowerCase().indexOf('csrf') !== -1) {
                    window.location.reload();
                    return;
                }
                var status = response.status;
                var statusIcon = response.text;
                if (status) {
                    if (status == '200') {
                        $(".ccp" + commentID).html(statusIcon);
                    } else if (status == '404') {
                        $(".ccp" + commentID).html(statusIcon);
                    }
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Call Edit Comment PoUpbox*/
    $(document).on("click", ".cced", function() {
        var type = 'c_editComment';
        var commentID = $(this).attr("id");
        var postID = $(this).attr("data-id");
        var data = 'f=' + type + '&cid=' + commentID + '&pid=' + postID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Save Comment Edit*/
    $(document).on("click", ".secdt", function() {
        var type = 'editSC';
        var commentID = $(this).attr('id');
        var postID = $(this).attr('data-id');
        var editText = $("#ed_" + commentID).val();
        var data = 'f=' + type + '&cid=' + commentID + '&pid=' + postID + '&text=' + encodeURIComponent(editText);
        var csrfToken = $('meta[name=csrf-token]').attr('content') || $("#poll_csrf_token").val();
        if (csrfToken) {
            data += '&csrf_token=' + encodeURIComponent(csrfToken);
        }
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response && response.message && response.message.toString().toLowerCase().indexOf('csrf') !== -1) {
                    window.location.reload();
                    return;
                }
                var responseStatus = response.status;
                var editedText = response.text;
                if (responseStatus == 'no') {
                    PopUPAlerts('eCouldNotEmpty', 'ialert');
                } else if (responseStatus == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                } else if (responseStatus == '200') {
                    var $commentBox = $("#i_u_c_" + commentID);
                    var $commentContent = $commentBox.find(".i_comment_text_content");
                    if ($commentContent.length) {
                        $commentContent.html(editedText);
                    } else {
                        $commentBox.html('<div class="i_comment_text_content js-text-truncate" data-max-lines="4">' + editedText + '</div>');
                    }
                    initExpandableText($commentBox);
                    $(".i_modal_in_in").addClass("i_modal_in_in_out");
                    setTimeout(() => {
                        $(".i_modal_bg_in").remove();
                    }, 100);
                }
            }
        });
    });
    /*Follow Profile PopUp Call*/
    $(document).on("click", ".free_follow", function() {
        var type = 'follow_free_not';
        var ID = $(this).attr("data-u");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });

    /*Follow Profile Free*/
    $(document).on("click", ".f_p_follow", function() {
        var type = 'freeFollow';
        var ID = $(this).attr("data-u");
        var data = 'f=' + type + '&follow=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {
                $(".i_modal_content").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                var responseStatus = response.status;
                var responseNot = response.text;
                var responseBtn = response.btn;
                if (responseStatus == '200') {
                    $(".i_fw" + ID).html(responseBtn);
                    if (responseNot == 'flw') {
                        $(".i_fw" + ID).removeClass("i_btn_like_item free_follow").addClass("i_btn_like_item_flw f_p_follow");
                        PopUPAlerts('youFollowing', 'ialert');
                    } else if (responseNot == 'unflw') {
                        $(".i_fw" + ID).removeClass("i_btn_like_item_flw f_p_follow").addClass("i_btn_like_item free_follow");
                        PopUPAlerts('youUnfollowing', 'ialert');
                    }
                }
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 100);
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Block Not PopUp Call*/
    $(document).on("click", ".ublknot", function() {
        var type = 'uBlockNotice';
        var ID = $(this).attr("data-u");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
    /*Choose Block TYPE*/
    $(document).on("click", ".i_redtrict_u", function() {
        var ID = $(this).attr("data-s");
        $(".block_a_status").removeClass("blockboxActive").addClass("blockboxPassive");
        $("#bl_s_" + ID).addClass("blockboxActive");
        $(".ublk").attr('data-bt', ID);
    });
    /*Block User*/
    $(document).on("click", ".ublk", function() {
        var type = 'ublock';
        var ID = $(this).attr("id");
        var blockType = $(this).attr("data-bt");
        var data = 'f=' + type + '&id=' + ID + '&blckt=' + blockType;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var responseStatus = response.status;
                var responseRedirect = response.redirect;
                if (responseStatus == '200') {
                    window.location.href = responseRedirect;
                } else if (responseStatus == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                }

            }
        });
    });
    /*Block Not PopUp Call*/
    $(document).on("click", ".uSubsModal", function() {
        var type = 'subsModal';
        var ID = $(this).attr("data-u");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

                $(".uSubsModal").prop("disabled", true);
                $(".uSubsModal").css("pointer-events", "none");
            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                    $(".uSubsModal").prop("disabled", false);
                    $(".uSubsModal").css("pointer-events", "auto");
                }, 200);
            }
        });
    });
    /*Credit Card Form Call*/
    $(document).on("click", ".bcmSubs", function() {
        var type = 'creditCard';
        var ID = $(this).attr("data-u");
        var subscribing = $(this).attr("id");
        var data = 'f=' + type + '&plan=' + subscribing + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
    /*Upload Verification Files*/
    $(document).on("change", "#id_card_two, #id_card", function(e) {
        e.preventDefault();
        var values = $("#uploadVal").val();
        var id = $(this).attr("data-id");
        var type = $(this).attr("data-type");
        var data = { f: id, c: type };
        $("#vUploadForm").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function() {
                $(".f_" + type).html('');
                $("#uploadVal_" + type).val('');
                $("#" + type).append('<div class="i_upload_progress"></div>');
            },
            uploadProgress: function(e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function(response) {
                if (response) {
                    $(".f_" + type).append(response);
                    var K = $(".f_" + type + " > div:last").attr("id");
                    var T = K;
                    if (T != "undefined,") {
                        $("#uploadVal_" + type).val(T);
                    }
                    $("#id_card , #id_card_two").val('');
                }
                $(".i_upload_progress").remove();
            },
            error: function(xhr, status, error) { handleUploadError(xhr, status, error); }
        }).submit();
    });
    /*Send Verification Certificate Request*/
    $(document).on("click", ".v_Next", function() {
        var type = 'verificationRequest';
        var IDCard = $("#uploadVal_sec_one").val();
        var IDPhoto = $("#uploadVal_sec_two").val();
        var data = 'f=' + type + '&cID=' + IDCard + '&cP=' + IDPhoto;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".i_nex_btn").css("pointer-events", "none");
                $(".card , .both , .photo").hide();
            },
            success: function(response) {
                if (response == '200') {
                    location.reload();
                } else if (response == 'card') {
                    $("#id_card , #id_card_two").val('');
                    $(".card").show();
                } else if (response == 'photo') {
                    $("#id_card , #id_card_two").val('');
                    $(".photo").show();
                } else if (response == 'both') {
                    $("#id_card , #id_card_two").val('');
                    $(".both").show();
                }
                $(".i_nex_btn").css("pointer-events", "auto");
            }
        });
    });
    /*Call Avatar And Cover PopUP*/
    $(document).on("click", ".editAvatarCover", function() {
        var type = 'updateAvatarCover';
        var data = 'f=' + type;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });


    $(document).on('submit', "#myEmailForm", function(e) {
        e.preventDefault();
        var myEmailForm = $(this);
        if ($("#cPass").val().length == 0) {
            $(".warning_required").show();
            return false;
        }
        jQuery.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: myEmailForm.serialize(),
            beforeSend: function() {
                $(".warning_inuse , .warning_invalid , .warning_wrong_password , .warning_required , .warning_same_email").hide();
                $(".i_become_creator_box_footer").append(plreLoadingAnimationPlus);
                myEmailForm.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    myEmailForm.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '404') {
                    $(".warning_inuse").show();
                } else if (data == 'no') {
                    $(".warning_invalid").show();
                } else if (data == 'same') {
                    $(".warning_same_email").show();
                } else if (data == 'password') {
                    $(".warning_wrong_password").show();
                } else if (data == '200') {
                    $(".successNot").show();
                }
                $(".loaderWrapper").remove();
            }
        });
    });

    $(document).on('submit', "#myProfileForm", function(e) {
        e.preventDefault();
        var myProfileForm = $(this);
        jQuery.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: myProfileForm.serialize(),
            beforeSend: function() {
                $(".invalid_username , .character_warning , .warning_username").hide();
                $(".i_become_creator_box_footer").append(plreLoadingAnimationPlus);
                myProfileForm.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    myProfileForm.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '1') {
                    $(".successNot").show();
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    function payoutValidateEmail(email) {
        var re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    function hidePayoutWarnings() {
        $("#setWarning, #notMatch, #notValidE, #setBankWarning").hide();
    }

    function togglePayoutMethodFields() {
        var selectedMethod = $('input[name=default]:checked', '#bankForm').val() || '';
        $(".payout_method_fields").hide();
        $('.payout_method_fields[data-method="' + selectedMethod + '"]').show();
    }

    function buildPayoutUpdatePayload() {
        var defaultMethod = $('input[name=default]:checked', '#bankForm').val() || '';
        var paypalEmail = $.trim($("#paypale").val() || '');
        var repaypalEmail = $.trim($("#paypalere").val() || '');
        var bankAccount = $.trim($("#bank_transfer").val() || '');
        var payoneerEmail = $.trim($("#payoneer_email").val() || '');
        var payoneerReEmail = $.trim($("#payoneer_email_re").val() || '');
        var zelleEmail = $.trim($("#zelle_email").val() || '');
        var zelleReEmail = $.trim($("#zelle_email_re").val() || '');
        var westernUnionFullName = $.trim($("#western_union_full_name").val() || '');
        var westernUnionDocumentId = $.trim($("#western_union_document_id").val() || '');
        var bitcoinWallet = $.trim($("#bitcoin_wallet").val() || '');
        var mercadoPagoAlias = $.trim($("#mercadopago_alias").val() || '');
        var mercadoPagoCvu = $.trim($("#mercadopago_cvu").val() || '');

        hidePayoutWarnings();

        if (defaultMethod == 'paypal') {
            if (paypalEmail.length == 0 || repaypalEmail.length == 0) {
                $("#setWarning").show();
                return null;
            }
            if (!payoutValidateEmail(paypalEmail) || !payoutValidateEmail(repaypalEmail)) {
                $("#notValidE").show();
                return null;
            }
            if (paypalEmail != repaypalEmail) {
                $("#notMatch").show();
                return null;
            }
        } else if (defaultMethod == 'payoneer') {
            if (payoneerEmail.length == 0 || payoneerReEmail.length == 0) {
                $("#setWarning").show();
                return null;
            }
            if (!payoutValidateEmail(payoneerEmail) || !payoutValidateEmail(payoneerReEmail)) {
                $("#notValidE").show();
                return null;
            }
            if (payoneerEmail != payoneerReEmail) {
                $("#notMatch").show();
                return null;
            }
        } else if (defaultMethod == 'zelle') {
            if (zelleEmail.length == 0 || zelleReEmail.length == 0) {
                $("#setWarning").show();
                return null;
            }
            if (!payoutValidateEmail(zelleEmail) || !payoutValidateEmail(zelleReEmail)) {
                $("#notValidE").show();
                return null;
            }
            if (zelleEmail != zelleReEmail) {
                $("#notMatch").show();
                return null;
            }
        } else if (defaultMethod == 'western-union') {
            if (westernUnionFullName.length == 0 || westernUnionDocumentId.length == 0) {
                $("#setBankWarning").show();
                return null;
            }
        } else if (defaultMethod == 'bitcoin') {
            if (bitcoinWallet.length == 0) {
                $("#setBankWarning").show();
                return null;
            }
        } else if (defaultMethod == 'mercadopago') {
            if (mercadoPagoAlias.length == 0 || mercadoPagoCvu.length == 0) {
                $("#setBankWarning").show();
                return null;
            }
        } else if (bankAccount.length == 0) {
            $("#setBankWarning").show();
            return null;
        }

        return {
            f: 'updatePayoutSet',
            paypalEmail: encodeURIComponent(paypalEmail),
            paypalReEmail: encodeURIComponent(repaypalEmail),
            bank: bankAccount,
            method: defaultMethod,
            payoneerEmail: encodeURIComponent(payoneerEmail),
            payoneerReEmail: encodeURIComponent(payoneerReEmail),
            zelleEmail: encodeURIComponent(zelleEmail),
            zelleReEmail: encodeURIComponent(zelleReEmail),
            westernUnionFullName: westernUnionFullName,
            westernUnionDocumentId: westernUnionDocumentId,
            bitcoinWallet: bitcoinWallet,
            mercadoPagoAlias: mercadoPagoAlias,
            mercadoPagoCvu: mercadoPagoCvu
        };
    }

    togglePayoutMethodFields();
    $(document).on("change", '#bankForm input[name="default"]', function() {
        hidePayoutWarnings();
        togglePayoutMethodFields();
    });

    $(document).on("click", ".pyot_sNext", function() {
        var data = buildPayoutUpdatePayload();
        if (!data) {
            return false;
        }
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".i_nex_btn").css("pointer-events", "none");
                $(".i_become_creator_box_footer").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response != '200') {
                    if (response == 'email_warning') {
                        $("#notMatch").show();
                    } else if (response == 'paypal_warning') {
                        $("#setWarning").show();
                    } else if (response == 'bank_warning') {
                        $("#setBankWarning").show();
                    } else if (response == 'not_valid_email') {
                        $("#notValidE").show();
                    }
                }
                setTimeout(() => {
                    if (response == '200') {
                        $(".successNot").show();
                    }
                    $(".loaderWrapper").remove();
                    $(".i_nex_btn").css("pointer-events", "auto");
                }, 3000);
            }
        });
    });

    $(document).on("click", ".mwithdraw", function() {
        var type = 'makewithDraw';
        var amount = $("#wamount").val();
        if (amount.length == 0) {
            $("#mwithdrawal").show();
            return false;
        } else {
            $("#mwithdrawal").hide();
        }
        var data = 'f=' + type + '&amount=' + amount;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".i_nex_btn_btn").css("pointer-events", "none");
                $(".i_t_warning").hide();
                $(".i_become_creator_box_footer").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '1') {
                    $(".successNot").show();
                } else if (response == '2') {
                    $("#mwithdrawal").show();
                } else if (response == '3') {
                    $("#nbudget").show();
                } else if (response == '4') {
                    $("#nnoway").show();
                } else if (response == '5') {
                    $("#nwaitpending").show();
                }
                $(".loaderWrapper").remove();
                $(".i_nex_btn_btn").css("pointer-events", "auto");
            }
        });
    });
    /*Credit Card Form Call*/
    $(document).on("click", ".prcsPost", function() {
        var type = 'pPurchase';
        var post = $(this).attr("id");
        var data = 'f=' + type + '&purchase=' + post;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
    $(document).on("click", ".prchase_go_wallet", function() {
        var type = 'goWallet';
        var post = $(this).attr("id");
        var data = 'f=' + type + '&p=' + post;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var url = $.trim(response);
                if(url){
                    window.location.href = url;
                }
            }
        });
    });
    $(document).on("click", ".buyPoint", function() {
        var type = 'choosePaymentMethod';
        var pointID = $(this).attr("id");
        var data = 'f=' + type + '&type=' + pointID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".credit_plan_box").css("pointer-events", "none");
                $("#p_i_" + pointID).append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
                $(".loaderWrapper").remove();
                $(".credit_plan_box").css("pointer-events", "auto");
            }
        });
    });
    $(document).on("click", ".mcSt", function() {
        if ($(".cSetc")[0]) {
            $(".cSetc").removeClass("dblock");
        }
        $(".cSetc").addClass("dblock");
    });
    /*Get Gifs*/
    $(document).on("click", ".getmGifs", function() {
        var type = 'chat_gifs';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        if (!$("div").hasClass("Message_stickersContainer")) {
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                beforeSend: function() {
                    $(".getmGifs").css("pointer-events", "none");
                    $(".nanos").append('<div class="preLoadC">' + plreLoadingAnimationPlus + '</div>');
                },
                success: function(response) {
                    $(".nanos").append(response);
                    $(".preLoadC").remove();
                    $(".getmGifs").css("pointer-events", "auto");
                }
            });
        } else {
            $(".Message_stickersContainer").remove();
        }
    });
    /*Get Gifs*/
    $(document).on("click", ".getmStickers", function() {
        var type = 'chat_stickers';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        if (!$("div").hasClass("Message_stickersContainer")) {
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                beforeSend: function() {
                    $(".getmGifs").css("pointer-events", "none");
                    $(".nanos").append('<div class="preLoadC">' + plreLoadingAnimationPlus + '</div>');
                },
                success: function(response) {
                    $(".nanos").append(response);
                    $(".preLoadC").remove();
                    $(".getmGifs").css("pointer-events", "auto");
                }
            });
        } else {
            $(".Message_stickersContainer").remove();
        }
    });
    $(document).on("click", ".getMEmojis", function() {
        var type = 'memoji';
        var ID = $(this).attr("data-type");
        var data = 'f=' + type + '&id=' + ID;
        if (!$("div").hasClass("Message_stickersContainer")) {
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                beforeSend: function() {
                    $(".getMEmojis").css("pointer-events", "none");
                    $(".nanos").append('<div class="preLoadC">' + plreLoadingAnimationPlus + '</div>');
                },
                success: function(response) {
                    $(".nanos").append(response);
                    $(".preLoadC").remove();
                    $(".getMEmojis").css("pointer-events", "auto");
                }
            });
        } else {
            $(".Message_stickersContainer").remove();
        }
    });
    /*Get Gifs*/
    $(document).on("click", ".chtBtns", function() {
        var type = 'chat_btns';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        if (!$("div").hasClass("ch_fl_btns_container")) {
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                beforeSend: function() {
                    $(".chtBtns").css("pointer-events", "none");
                },
                success: function(response) {
                    $(".chtBtns").css("pointer-events", "auto");
                    $(".fl_btns").append(response);
                }
            });
        }
    });

    function ScrollBottomChat() {
        if ($("div").hasClass("all_messages")) {
            $(".all_messages").stop().animate({ scrollTop: $(".all_messages")[0].scrollHeight }, 100);
        }
    }
    ScrollBottomChat();
    function updateChatComposerState() {
        var textValue = $.trim($(".mSize").val() || '');
        if (textValue.length === 0) {
            $(".sendmes").addClass("send-disabled");
        } else {
            $(".sendmes").removeClass("send-disabled");
        }
    }
    function autoSizeChatTextarea(el) {
        if (!el) {
            return;
        }
        el.style.height = '42px';
        var nextHeight = el.scrollHeight;
        if (nextHeight < 42) {
            nextHeight = 42;
        }
        if (nextHeight > 124) {
            nextHeight = 124;
        }
        el.style.height = nextHeight + 'px';
    }
    $(document).on("input", ".mSize", function() {
        autoSizeChatTextarea(this);
        updateChatComposerState();
    });
    $(document).on("focus", ".mSize", function() {
        autoSizeChatTextarea(this);
        updateChatComposerState();
    });
    $(document).on("click", ".sendmes.send-disabled", function(e) {
        e.preventDefault();
        return false;
    });
    updateChatComposerState();
    $(document).on('keydown', ".mSize", function(e) {
        var key = e.which || e.keyCode || 0;
        if (key == 13) {
            var type = 'nmessage';
            var ID = $(".message_send_form_wrapper").attr("id");
            var value = $.trim($(this).val());
            if (!value) {
                e.preventDefault();
                return false;
            }
            var gMoney = $("#sicVal").val();
            var gf = '';
            var st = '';
            e.preventDefault();
            Message(ID, value, type, gf, st, '', gMoney);
        }
    });
    /*Add Sticker*/
    $(document).on("click", ".MaddSticker", function() {
        var type = 'message_sticker';
        var ID = $(this).attr("id");
        var dataID = $(this).attr("data-id");
        var data = 'f=' + type + '&id=' + ID + '&pi=' + dataID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {
                $(".Message_stickersContainer").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                var stickerID = response.st_id;
                if (stickerID) {
                    $(".Message_stickersContainer").remove();
                    var gMoney = $("#sicVal").val();
                    Message(dataID, '', 'nmessage', stickerID, '', '',gMoney);
                    $(".loaderWrapper").remove();
                }
            }
        });
    });
    /*Send Gif Message*/
    $(document).on("click", ".mrGif", function() {
        var src = $(this).attr("src");
        var ID = $(this).attr("data-id");
        var gMoney = $("#sicVal").val();
        Message(ID, '', 'nmessage', '', src, '',gMoney);
        $(".Message_stickersContainer").remove();
    });

    $(document).on("click", ".emoji_item_m", function() {
        var copyEmoji = $(this).attr("data-emoji");
        var getValue = $(".mSize").val();
        $(".mSize").val(getValue + ' ' + copyEmoji + ' ');
        $(".mSize").each(function() {
            autoSizeChatTextarea(this);
        });
        updateChatComposerState();
    });
    /*Comment*/
    function Message(ID, value, type, stickerID, gfSrc, file, gMoney) {
        var data = 'f=' + type + '&id=' + ID + '&val=' + encodeURIComponent(value) + '&sticker=' + stickerID + '&gif=' + gfSrc + '&fl=' + file + '&mo='+gMoney;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".Message_stickersContainer").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                } if(response == '403'){
                    PopUPAlerts('cNotSend', 'ialert');
                }else {
                    $(".all_messages_container").append(response);
                    ScrollBottomChat();
                }
                $(".mSize").val('');
                $(".mSize").each(function() {
                    autoSizeChatTextarea(this);
                });
                updateChatComposerState();
                $(".Message_stickersContainer").remove();
                $(".loaderWrapper").remove();
                $(".i_write_secret_post_price").addClass("boxD");
                $("#sicVal").val('');
            }
        });
    }
    $(document).on("click", ".sendmes", function() {
        var value = $.trim($(".mSize").val());
        if (!value) {
            return false;
        }
        var ID = $(".message_send_form_wrapper").attr("id");
        var gMoney = $("#sicVal").val();
        Message(ID, value, 'nmessage', '', '', '',gMoney);
    });
    /*Uploading Message Image*/
    $(document).on("change", "#ci_image", function(e) {
        e.preventDefault();
        var values = $("#uploadVal").val();
        var id = $("#ci_image").attr("data-id");
        var ID = $(".message_send_form_wrapper").attr("id");
        var data = { f: id, c: ID };
        $("#uploadform").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function() {
                $(".ch_fl_btns_container").remove();
                $('.message_send_form_wrapper').append('<div class="i_upload_progress"></div>');
            },
            uploadProgress: function(e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function(response) {
                if (response) {
                    $(".i_uploaded_iv").show();
                    if (response) {
                        var gMoney = $("#sicVal").val();
                        if(gMoney.length != 0){
                            Message(ID, '', 'nmessage','', '', response, gMoney);
                        }else{
                            Message(ID, '', 'nmessage', '', '', response, '');
                        }
                    }
                }
                $(".i_upload_progress").remove();
            },
            error: function() {}
        }).submit();
    });
    /*Get More Comment*/
    $(document).on("click", ".more_comment", function() {
        var type = 'moreComment';
        var ID = $(this).attr("data-id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $("#pf_l_" + ID).append(preLoadingAnimation);
                $(".comnts").css("pointer-events", "none");
            },
            success: function(response) {
                if (response == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                } else {
                    $("#i_user_comments_" + ID).html(response);
                    $(".lc_sum_container_" + ID).remove();
                    initExpandableText($("#i_user_comments_" + ID));
                }
                $(".comnts").css("pointer-events", "auto");
                $(".i_loading").remove();
            }
        });
    });
    $(document).on("click", ".chooseLanguage", function() {
        var type = 'chooseLanguage';
        var data = 'f=' + type;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".chooseLanguage").css("pointer-events", "none");
            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
                $(".chooseLanguage").css("pointer-events", "auto");
            }
        });
    });
    /*Change Language*/
    $(document).on("click", ".chLang", function() {
        var type = 'changeMyLang';
        var id = $(this).attr("id");
        var data = 'f=' + type + '&id=' + id;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".chLang").css("pointer-events", "none");
                $(".purchase_post_details").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
                $(".chLang").css("pointer-events", "auto");
                if (response == '200') {
                    location.reload();
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                    $(".i_modal_in_in").addClass("i_modal_in_in_out");
                    setTimeout(() => {
                        $(".i_modal_bg_in").remove();
                    }, 200);
                }

            }
        });
    });

	    /*Search Creator*/
	    let creatorSearchTimer = null;
	    let creatorSearchXhr = null;
	    $(document).delegate('#search_creator', 'keyup', function () {
	        const $input = $(this);
	        const searchValue = $input.val() || '';
	        const type = 'searchCreator';
	        const sum = searchValue.replace(/\s+/g, '').length;
	        const $searchRoot = $input.closest('.i_search');
	        const $container = $searchRoot.find('.i_general_box_search_container').first();
	        const $items = $searchRoot.find('.sb_items').first();
	
	        if (creatorSearchTimer) {
	            clearTimeout(creatorSearchTimer);
	            creatorSearchTimer = null;
	        }
	        if (creatorSearchXhr && typeof creatorSearchXhr.abort === 'function') {
	            creatorSearchXhr.abort();
	            creatorSearchXhr = null;
	        }
	
	        if (sum >= 1) {
	            $container.css('display', 'flex');
	            const requestedQuery = searchValue;
	            creatorSearchTimer = setTimeout(() => {
	                $items.html(plreLoadingAnimationPlus);
	                creatorSearchXhr = $.ajax({
	                    type: "POST",
	                    url: siteurl + 'requests/request.php',
	                    data: 'f=' + type + '&s=' + encodeURIComponent(requestedQuery),
	                    cache: false,
	                    success: function (response) {
	                        // Prevent stale responses from overwriting newer queries
	                        if (($input.val() || '') !== requestedQuery) {
	                            return;
	                        }
	                        $items.html(response);
	                    },
	                    error: function () {
	                        if (($input.val() || '') !== requestedQuery) {
	                            return;
	                        }
	                        $items.html('');
	                    }
	                });
	            }, 450);
	        } else {
	            $container.css('display', 'none');
	            $items.html('');
	        }
	    });
    $(document).on("mouseup", function(e) {
        var container = $(".i_general_box_search_container");
        if (!container.is(e.target) && container.has(e.target).length === 0) {
            container.hide();
            $(".sb_items").html('');
        }
    });
    $(document).on("click", ".newMessageMe", function() {
        var type = 'newMessageMe';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&user=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".newMessageMe").css("pointer-events", "none");
            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
                $(".newMessageMe").css("pointer-events", "auto");
            }
        });
    });
    /*Send New Message*/
    $(document).on("click", ".sndNewMessage", function() {
        var type = 'newfirstMessage';
        var value = $("#sendNewM").val();
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&fm=' + encodeURIComponent(value) + '&u=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".sndNewMessage").css("pointer-events", "none");
            },
            success: function(response) {
                if (response != '404') {
                    window.location.href = response;
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 200);
            }
        });
    });
    /*Update Theme*/
    $(document).on("click", ".updateTheme", function() {
        var type = 'updateTheme';
        var theme = $(this).attr("data-id");
        var data = 'f=' + type + '&theme=' + theme;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".sndNewMessage").css("pointer-events", "none");
            },
            success: function(response) {
                if (response != '404') {
                    location.reload();
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    $(document).on("click", ".mobile_hamburger", function() {
        if (!$(".leftStickyActive")[0]) {
            $(".mobile_left").addClass("leftStickyActive");
            $(".is_mobile").addClass("svg_active_icon");
        } else {
            $(".mobile_left").removeClass("leftStickyActive");
            $(".is_mobile").removeClass("svg_active_icon");
        }
    });
    $(document).on("click", ".mobile_srcbtn", function() {
        if (!$(".i_search_active")[0]) {
            $(".i_search").addClass("i_search_active");
        } else {
            $(".i_search").removeClass("i_search_active");
        }
    });
    $(window).on("resize", function() {
        checkWidth();
    });
    checkWidth();

    function checkWidth() {
        var vWidth = $(window).width();
        if (vWidth < 700) {
            if (!$(".mobile_footer_fixed_menu_container")[0]) {
                $.ajax({
                    type: "POST",
                    url: siteurl + 'requests/request.php',
                    data: 'f=fixedMenu',
                    cache: false,
                    beforeSend: function() {
                        $(".sndNewMessage").css("pointer-events", "none");
                    },
                    success: function(response) {
                        if (!$(".mobile_footer_fixed_menu_container")[0] && !$(".live_video_header")[0]) {
                            $("body").append(response);
                        }
                    }
                });
            }
        } else {
            if ($(".mobile_footer_fixed_menu_container")[0]) {
                $(".mobile_footer_fixed_menu_container").remove();
            }
        }
    }
    $(document).on("keyup keypress keydown", ".nwComment", function() {
        var ID = $(this).attr("data-id");
        var check = $(this).val().length;
        var $vWidth = $(window).width();
        if (check > 20) {
            $(".i_comment_footer" + ID).addClass("commentFooterResize");
        } else {
            $(".i_comment_footer" + ID).removeClass("commentFooterResize");
        }
    });
    $(document).on("click", ".settings_mobile_menu_container", function() {
        if (!$(".settingsMenuDisplay")[0]) {
            $(".i_settings_menu_wrapper").addClass("settingsMenuDisplay");
        } else {
            $(".i_settings_menu_wrapper").removeClass("settingsMenuDisplay");
        }
    });
    $(document).on("click", ".cList", function() {
        if (!$(".chatDisplay")[0]) {
            $(".chat_left_container").addClass("chatDisplay");
        } else {
            $(".chat_left_container").removeClass("chatDisplay");
        }
    });
    /*Delete Message*/
    $(document).on("click", ".dlMesv", function() {
        var type = 'deleteMessage';
        var ID = $(this).attr("id");
        var cID = $(".cList").attr("id");
        var data = 'f=' + type + '&id=' + ID + '&cid=' + cID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    $("#msg_" + ID).remove();
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 200);
            }
        });
    });
    $(document).on("click", ".delmes", function() {
        var type = 'ddelMesage';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $(".me_msg_plus").removeClass("dblock");
        $(".msg").removeClass("msg-menu-open");
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    $(document).on("click", ".repmes", function() {
        var type = 'reportMessage';
        var ID = $(this).attr("id");
        var cID = $(".cList").attr("id");
        var data = 'f=' + type + '&id=' + ID + '&cid=' + cID;
        var csrfToken = $('meta[name=csrf-token]').attr('content') || $("#poll_csrf_token").val();
        if (csrfToken) {
            data += '&csrf_token=' + encodeURIComponent(csrfToken);
        }
        $(".me_msg_plus").removeClass("dblock");
        $(".msg").removeClass("msg-menu-open");
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            dataType: "json",
            data: data,
            cache: false,
            success: function(response) {
                if (response && response.message && response.message.toString().toLowerCase().indexOf('csrf') !== -1) {
                    window.location.reload();
                    return;
                }
                var status = response.status;
                var statusIcon = response.text;
                if (status) {
                    if (status == '200' || status == '404') {
                        $(".repmes_" + ID).html(statusIcon);
                        if (status == '200') {
                            PopUPAlerts('message_reported_success', 'ialert');
                        } else {
                            PopUPAlerts('message_report_removed', 'ialert');
                        }
                    }
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    $(document).on("click", ".d_conversation", function() {
        var type = 'ddelConv';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Delete Message*/
    $(document).on("click", ".dlConv", function() {
        var type = 'deleteConversation';
        var ID = $(this).attr("id");
        var cID = $(".cList").attr("id");
        var data = 'f=' + type + '&id=' + ID + '&cid=' + cID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response != '404') {
                    location.reload();
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*Search Creator*/
    let chatSearchTimer = null;
    let chatSearchRequest = null;
    $(document).on('input', '#c_search', function() {
        const searchValue = $(this).val();
        const type = 'searchUser';
        const normalizedLength = searchValue.replace(/\s+/g, '').length;

        if (chatSearchTimer) {
            clearTimeout(chatSearchTimer);
            chatSearchTimer = null;
        }
        if (chatSearchRequest && chatSearchRequest.readyState !== 4) {
            chatSearchRequest.abort();
        }

        if (normalizedLength < 3) {
            $(".chat_users_wrapper_results").addClass("nonePoint").removeClass("search-open").hide().html('');
            $(".chat_users_wrapper").show();
            return;
        }

        $(".chat_users_wrapper_results").removeClass("nonePoint").addClass("search-open").show().html(plreLoadingAnimationPlus);
        $(".chat_users_wrapper").hide();

        chatSearchTimer = setTimeout(function() {
            chatSearchRequest = $.ajax({
                type: "POST",
                url: siteurl + 'requests/request.php',
                data: 'f=' + type + '&key=' + encodeURIComponent(searchValue),
                cache: false,
                success: function(response) {
                    if (response && response.trim() !== '') {
                        $(".chat_users_wrapper_results").removeClass("nonePoint").addClass("search-open").show().html(response);
                    } else {
                        $(".chat_users_wrapper_results").addClass("nonePoint").removeClass("search-open").hide().html('');
                        $(".chat_users_wrapper").show();
                    }
                },
                error: function(xhr, status) {
                    if (status !== 'abort') {
                        $(".chat_users_wrapper_results").addClass("nonePoint").removeClass("search-open").hide().html('');
                        $(".chat_users_wrapper").show();
                    }
                }
            });
        }, 350);
    });
    /*Show full report-note notification in popup*/
    $(document).on("click", ".js_report_note_notification", function(e) {
        var $trigger = $(this);
        var popupText = $.trim($trigger.attr("data-popup-text") || "");
        if (popupText === "") {
            return;
        }
        e.preventDefault();
        var popupTitle = $.trim($trigger.attr("data-popup-title") || "");
        var popupUrl = $.trim($trigger.attr("data-popup-url") || "");
        var popupActionText = $.trim($trigger.attr("data-popup-action") || "");
        var popupCloseText = $.trim($trigger.attr("data-popup-close") || "No");

        $(".i_modal_bg_in.js_report_note_modal").remove();

        var $modal = $("<div>", {
            "class": "i_modal_bg_in js_report_note_modal",
            "role": "dialog",
            "aria-modal": "true"
        });
        var $inner = $("<div>", { "class": "i_modal_in_in" });
        var $content = $("<div>", { "class": "i_modal_content" });
        var $header = $("<div>", { "class": "i_modal_g_header" }).text(popupTitle);
        var closeSvgIcon = "";
        var $existingCloseIcon = $(".shareClose svg, .popClose svg").first();
        if ($existingCloseIcon.length) {
            closeSvgIcon = $existingCloseIcon.prop("outerHTML") || "";
        }
        if (closeSvgIcon === "") {
            closeSvgIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18.3 5.71 12 12l6.3 6.29-1.42 1.42L10.59 13.4 4.29 19.7 2.88 18.3 9.17 12 2.88 5.71 4.29 4.29l6.3 6.3 6.29-6.3z"></path></svg>';
        }
        var $close = $("<div>", { "class": "shareClose transition", "role": "button" }).html(closeSvgIcon);
        var $description = $("<div>", { "class": "i_delete_post_description i_report_notification_detail" }).text(popupText);
        var $footer = $("<div>", { "class": "i_modal_g_footer" });

        if (popupUrl !== "" && popupUrl !== "#") {
            $("<a>", {
                "class": "alertBtnRight transition i_report_notification_go",
                "href": popupUrl,
                "text": popupActionText
            }).appendTo($footer);
        }

        $("<div>", {
            "class": "alertBtnLeft no-del transition",
            "role": "button",
            "text": popupCloseText
        }).appendTo($footer);

        $header.append($close);
        $content.append($header).append($description).append($footer);
        $inner.append($content);
        $modal.append($inner);
        $("body").append($modal);
        setTimeout(function() {
            $modal.addClass("i_modal_display_in");
        }, 25);
    });
    /*Hide Notification*/
    $(document).on("click", ".hidNot", function() {
        var type = 'hideNotification';
        var hideID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + hideID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response == '200') {
                    $(".hidNot_" + hideID).fadeOut();
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    /*UnBlock User*/
    $(document).on("click", ".unblock", function() {
        var type = 'unblock';
        var ID = $(this).attr("id");
        var blockedUserID = $(this).attr("data-u");
        var data = 'f=' + type + '&id=' + ID + '&u=' + blockedUserID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".block_id_" + ID).append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    $(".block_id_" + ID).fadeOut();
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });

    $(document).on('submit', '#myPasswordChange', function(e) {
        e.preventDefault();
        var passChange = $(this);
        jQuery.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: passChange.serialize(),
            beforeSend: function() {
                $(".successNot , .warning_not_mach , .warning_not_correct , .warning_write_current_password , .no_new_password_wrote , .minimum_character_not , .not_contain_whitespace").hide();
                $(".i_become_creator_box_footer").append(plreLoadingAnimationPlus);
                passChange.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    passChange.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '1') {
                    $(".warning_not_correct").show();
                } else if (data == '2') {
                    $(".warning_not_mach").show();
                } else if (data == '3') {
                    $(".warning_write_current_password").show();
                } else if (data == '4') {
                    $(".no_new_password_wrote").show();
                } else if (data == '5') {
                    $(".minimum_character_not").show();
                } else if (data == '6') {
                    $(".not_contain_whitespace").show();
                } else if (data == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                } else {
                    window.location.href = data;
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    function inoraFormatBytes(bytes) {
        var size = parseInt(bytes, 10) || 0;
        if (size <= 0) {
            return "0 B";
        }
        var units = ["B", "KB", "MB", "GB", "TB"];
        var pow = Math.floor(Math.log(size) / Math.log(1024));
        pow = Math.min(Math.max(pow, 0), units.length - 1);
        var value = size / Math.pow(1024, pow);
        var precision = pow === 0 ? 0 : 2;
        return value.toFixed(precision) + " " + units[pow];
    }

    function inoraApplyExportStats(response) {
        if (!response || response.status !== "ok") {
            return;
        }
        var createdAt = parseInt(response.created_at, 10) || 0;
        var expiresAt = parseInt(response.expires_at, 10) || 0;
        var fileSize = parseInt(response.file_size, 10) || 0;
        var generationSeconds = parseInt(response.generation_seconds, 10) || 0;
        var createdText = createdAt > 0 ? new Date(createdAt * 1000).toLocaleString() : "-";
        var expiresText = expiresAt > 0 ? new Date(expiresAt * 1000).toLocaleString() : "-";
        $(".account-export-created-value").text(createdText).attr("data-created-ts", createdAt);
        $(".account-export-size-value").text(inoraFormatBytes(fileSize)).attr("data-file-bytes", fileSize);
        $(".account-export-duration-value").text(generationSeconds > 0 ? generationSeconds + "s" : "-").attr("data-generation-seconds", generationSeconds);
        $(".account-export-expires-value").text(expiresText);
    }

    function inoraRunExportCooldown($btn, finishAt, persistToStorage) {
        var defaultLabel = $btn.data("default-label") || $btn.text();
        var template = $btn.data("cooldown-template") || "";
        if (persistToStorage && window.sessionStorage) {
            window.sessionStorage.setItem("accountExportCooldownUntil", String(finishAt));
        }
        $btn.data("cooldown-until", finishAt);
        function tick() {
            var remaining = Math.ceil((finishAt - Date.now()) / 1000);
            if (remaining <= 0) {
                $btn.prop("disabled", false).text(defaultLabel).removeData("cooldown-until");
                if (window.sessionStorage) {
                    window.sessionStorage.removeItem("accountExportCooldownUntil");
                }
                return;
            }
            var label = template ? template.replace("{seconds}", remaining) : (defaultLabel + " (" + remaining + "s)");
            $btn.prop("disabled", true).text(label);
            setTimeout(tick, 1000);
        }
        tick();
    }

    function inoraStartExportCooldown($btn) {
        var cooldownSeconds = parseInt($btn.data("cooldown-seconds"), 10) || 45;
        var finishAt = Date.now() + (cooldownSeconds * 1000);
        inoraRunExportCooldown($btn, finishAt, true);
    }

    function inoraRestoreExportCooldown() {
        var $btn = $(".createAccountExport").first();
        if ($btn.length === 0 || !window.sessionStorage) {
            return;
        }
        var storedUntil = parseInt(window.sessionStorage.getItem("accountExportCooldownUntil"), 10) || 0;
        if (storedUntil <= Date.now()) {
            window.sessionStorage.removeItem("accountExportCooldownUntil");
            return;
        }
        inoraRunExportCooldown($btn, storedUntil, false);
    }

    if (String(window.accountDeleteAutoCancelNotice || "0") === "1") {
        setTimeout(function() {
            if (typeof PopUPAlerts === "function") {
                PopUPAlerts("account_delete_auto_cancelled", "ialert");
            }
        }, 350);
    }
    inoraRestoreExportCooldown();

    $(document).on("click", ".createAccountExport", function() {
        var $btn = $(this);
        var cooldownUntil = parseInt($btn.data("cooldown-until"), 10) || 0;
        if (cooldownUntil > Date.now()) {
            return;
        }
        var defaultLabel = $btn.data("default-label") || $btn.text();
        var generatingLabel = $btn.data("generating-label") || defaultLabel;
        var csrfToken = $btn.data("csrf") || $('meta[name=csrf-token]').attr('content') || '';
        var $box = $(".account-export-feedback");
        var $downloadBtn = $(".account-export-download");
        if (!csrfToken) {
            return;
        }
        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            dataType: "json",
            data: {
                f: "createAccountExport",
                csrf_token: csrfToken
            },
            beforeSend: function() {
                $btn.prop("disabled", true).text(generatingLabel);
                $box.attr("hidden", "hidden").removeClass("successNot warning_not_correct");
                $btn.closest(".i_become_creator_box_footer").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                var isOk = response && response.status === "ok";
                var message = response && response.message ? response.message : "";
                if (isOk && response.download_url) {
                    $downloadBtn.attr("href", response.download_url).removeAttr("hidden");
                    inoraApplyExportStats(response);
                    if ($(".account-export-stat-item").length === 0) {
                        setTimeout(function() {
                            window.location.reload();
                        }, 500);
                    }
                }
                if (message) {
                    $box.text(message).removeAttr("hidden");
                    if (isOk) {
                        $box.addClass("successNot");
                    } else {
                        $box.addClass("warning_not_correct");
                    }
                }
                if (isOk) {
                    inoraStartExportCooldown($btn);
                } else {
                    $btn.prop("disabled", false).text(defaultLabel);
                }
            },
            error: function() {
                $btn.prop("disabled", false).text(defaultLabel);
                PopUPAlerts('sWrong', 'ialert');
            },
            complete: function() {
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on("click", ".openDeleteAccountModal", function() {
        var $modal = $(".account-delete-password-modal");
        if ($modal.length === 0) {
            return;
        }
        $modal.addClass("i_modal_display_in").attr("aria-hidden", "false");
        var $password = $modal.find("#delete_password");
        $password.attr("type", "password").val("").trigger("focus");
        $modal.find("#delete_confirm_text").val("");
        var $toggle = $modal.find(".account-delete-password-toggle");
        if ($toggle.length) {
            $toggle.text($toggle.data("show-text") || $toggle.text());
        }
        $modal.find(".account-delete-modal-error").attr("hidden", "hidden").text("");
    });
    $(document).on("click", ".accountDeleteModalClose", function() {
        var $modal = $(this).closest(".account-delete-password-modal");
        if ($modal.length === 0) {
            $modal = $(".account-delete-password-modal");
        }
        $modal.removeClass("i_modal_display_in").attr("aria-hidden", "true");
    });
    $(document).on("click", ".account-delete-password-toggle", function() {
        var $btn = $(this);
        var $wrap = $btn.closest(".account-delete-password-input-wrap");
        var $input = $wrap.find("input[name='delete_password']");
        if ($input.length === 0) {
            return;
        }
        var isPassword = $input.attr("type") === "password";
        $input.attr("type", isPassword ? "text" : "password");
        $btn.text(isPassword ? ($btn.data("hide-text") || "Hide") : ($btn.data("show-text") || "Show"));
    });
    $(document).on("click", ".account-delete-password-modal", function(e) {
        if (!$(e.target).closest(".account-delete-modal-card").length) {
            $(this).removeClass("i_modal_display_in").attr("aria-hidden", "true");
        }
    });
    $(document).on("submit", "#accountDeleteConfirmForm", function(e) {
        e.preventDefault();
        var $form = $(this);
        var $modal = $form.closest(".account-delete-password-modal");
        var $submit = $form.find("button[type='submit']");
        var $error = $form.find(".account-delete-modal-error");
        var $box = $(".account-delete-feedback");
        var csrfToken = $form.find("input[name='csrf_token']").val() || $('meta[name=csrf-token]').attr('content') || '';
        var password = $form.find("input[name='delete_password']").val() || '';
        var confirmText = $form.find("input[name='delete_confirm_text']").val() || '';
        var confirmKeyword = ($form.find("input[name='delete_confirm_keyword']").val() || "DELETE").toString().toUpperCase();
        var confirmInvalidMessage = $form.find(".delete-confirm-invalid-text").val() || "Please type DELETE to confirm.";
        if (!csrfToken) {
            return;
        }
        if ($.trim(confirmText).toUpperCase() !== confirmKeyword) {
            $error.text(confirmInvalidMessage).removeAttr("hidden");
            return;
        }
        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            dataType: "json",
            data: {
                f: "requestAccountDeletion",
                csrf_token: csrfToken,
                delete_password: password,
                delete_confirm_text: confirmText
            },
            beforeSend: function() {
                $submit.prop("disabled", true);
                $error.attr("hidden", "hidden").text("");
                $box.attr("hidden", "hidden").removeClass("successNot warning_not_correct");
                $form.append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                var isOk = response && response.status === "ok";
                var message = response && response.message ? response.message : "";
                if (!isOk && message) {
                    $error.text(message).removeAttr("hidden");
                }
                if (message) {
                    $box.text(message).removeAttr("hidden");
                    if (isOk) {
                        $box.addClass("successNot");
                    } else {
                        $box.addClass("warning_not_correct");
                    }
                }
                if (isOk && response.logout_url) {
                    $modal.removeClass("i_modal_display_in").attr("aria-hidden", "true");
                    window.location.href = response.logout_url;
                }
            },
            error: function() {
                PopUPAlerts('sWrong', 'ialert');
            },
            complete: function() {
                $submit.prop("disabled", false);
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on("click", ".cancelAccountDeletion", function() {
        var $btn = $(this);
        var csrfToken = $btn.data("csrf") || $('meta[name=csrf-token]').attr('content') || '';
        var $box = $(".account-delete-feedback");
        if (!csrfToken) {
            return;
        }
        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            dataType: "json",
            data: {
                f: "cancelAccountDeletion",
                csrf_token: csrfToken
            },
            beforeSend: function() {
                $btn.prop("disabled", true);
                $box.attr("hidden", "hidden").removeClass("successNot warning_not_correct");
                $btn.closest(".i_become_creator_box_footer").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                var isOk = response && response.status === "ok";
                var message = response && response.message ? response.message : "";
                if (message) {
                    $box.text(message).removeAttr("hidden");
                    if (isOk) {
                        $box.addClass("successNot");
                    } else {
                        $box.addClass("warning_not_correct");
                    }
                }
                if (isOk) {
                    setTimeout(function() {
                        window.location.reload();
                    }, 600);
                }
            },
            error: function() {
                PopUPAlerts('sWrong', 'ialert');
            },
            complete: function() {
                $btn.prop("disabled", false);
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Change Notifications*/
    $(document).on("click", ".setChange", function() {
        var type = 'p_preferences';
        var setChange = $(this).val();
        var setType = $(this).attr("id");
        var dataNot = 'f=' + type + '&notit=' + encodeURIComponent(setChange) + '&sType=' + setType;
        $.ajax({
            type: 'POST',
            url: siteurl + "requests/request.php",
            data: dataNot,
            cache: false,
            beforeSend: function() {
                $("." + setType).append(plreLoadingAnimationPlus);
                $('.setChange').attr('disabled', true);
            },
            success: function(response) {
                setTimeout(function() {
                    $('.setChange').attr('disabled', false);
                }, 500);
                if (response == '200') {
                    if (setChange == '0') {
                        $("#" + setType).val('1');
                    }
                    if (setChange == '1') {
                        $("#" + setType).val('0');
                    }
                }
                setTimeout(function() {
                    $(".loaderWrapper").remove();
                }, 1500);
            }
        });
    });
    /*Start Age Verification*/
    $(document).on("click", ".ageVerifStart", function() {
        var $btn = $(this);
        var $container = $btn.closest(".age_verification_block");
        if ($container.length === 0) {
            $container = $btn.closest(".age-verif-container");
        }
        var errorGeneric = $btn.data("error") || "";
        var errorCsrf = $btn.data("errorCsrf") || "";
        var csrf = $btn.data("csrf") || "";
        var $message = $container.find(".age-verif-inline-message");
        if ($message.length) {
            $message.text("").removeClass("age-verif-message--error age-verif-message--success").hide();
        }
        if (!csrf) {
            if ($message.length) {
                if (errorCsrf !== "") {
                    $message.text(errorCsrf).addClass("age-verif-message--error").show();
                }
            }
            return;
        }
        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            dataType: "json",
            data: {
                f: "ageVerifStart",
                csrf_token: csrf
            },
            beforeSend: function() {
                $btn.prop("disabled", true);
                if ($container.length) {
                    $container.append(plreLoadingAnimationPlus);
                }
            },
            success: function(response) {
                if (response && response.status === "success") {
                    var redirectUrl = response.redirect_url || response.url || "";
                    if (redirectUrl) {
                        window.location.href = redirectUrl;
                        return;
                    }
                }
                var message = (response && response.message) ? response.message : errorGeneric;
                if ($message.length) {
                    if (message) {
                        $message.text(message).addClass("age-verif-message--error").show();
                    }
                }
            },
            error: function() {
                if ($message.length) {
                    if (errorGeneric !== "") {
                        $message.text(errorGeneric).addClass("age-verif-message--error").show();
                    }
                }
            },
            complete: function() {
                $btn.prop("disabled", false);
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Close Alert*/
    $(document).on("click", ".i_alert_close", function() {
        $(".i_bottom_left_alert_container").remove();
    });
    /*Create a Live Streaming PopUp Call*/
    $(document).on("click", ".cNLive", function() {
        var type = $(this).attr("data-type");
        var autoSchedule = $(this).attr("data-schedule") === "1";
        var data = 'f=' + type;
        $.ajax({
            type: 'POST',
            url: siteurl + "requests/request.php",
            data: data,
            cache: false,
            beforeSend: function() {
                $("." + type).append(plreLoadingAnimationPlus);
                $("." + type).attr('disabled', true);
            },
            success: function(response) {
                setTimeout(function() {
                    $("." + type).attr('disabled', false);
                }, 500);
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        var $modal = $(".i_modal_bg_in").last();
                        $modal.addClass('i_modal_display_in');
                        if (autoSchedule) {
                            var $toggle = $modal.find(".live_schedule_toggle");
                            if ($toggle.length) {
                                $toggle.prop("checked", true).trigger("change");
                            }
                        }
                    }, 200);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
                setTimeout(function() {
                    $(".loaderWrapper").remove();
                }, 1500);
            }
        });
    });
    function getLiveNotifySelectedIds($modal) {
        var raw = ($modal.find(".live_notify_selected_ids").val() || "").toString();
        if (raw === "") {
            return [];
        }
        return raw.split(",").map(function(item) {
            return parseInt(item, 10);
        }).filter(function(item) {
            return !isNaN(item) && item > 0;
        });
    }

    function renderLiveNotifySelectedHint($modal, selectedIds) {
        var $hint = $modal.find(".live_notify_selected_hint");
        if (!$hint.length) {
            return;
        }
        var emptyText = $hint.data("empty-text") || "";
        var countText = $hint.data("count-text") || "";
        var count = selectedIds.length;
        if (count > 0 && countText) {
            $hint.text(countText.replace("{count}", count));
        } else {
            $hint.text(emptyText);
        }
    }

    function setLiveNotifySelectedIds($modal, selectedIds) {
        var uniqueIds = selectedIds.filter(function(item, index, self) {
            return self.indexOf(item) === index;
        });
        $modal.find(".live_notify_selected_ids").val(uniqueIds.join(","));
        renderLiveNotifySelectedHint($modal, uniqueIds);
    }

    function updateLiveNotifyAudienceUI($modal) {
        var notifyEnabled = $modal.find(".live_notify_toggle").is(":checked");
        var $audience = $modal.find(".live_notify_audience");
        var $selectBtn = $modal.find(".live_notify_select_btn");
        var $warning = $modal.find(".live_notify_selected_warning");
        var selectedIds = getLiveNotifySelectedIds($modal);
        if (!notifyEnabled) {
            $selectBtn.prop("disabled", true);
            renderLiveNotifySelectedHint($modal, []);
            if ($warning.length) {
                $warning.hide();
            }
            return;
        }
        $selectBtn.prop("disabled", false);
        var audience = $audience.val();
        if (audience === "selected") {
            renderLiveNotifySelectedHint($modal, selectedIds);
            if ($warning.length && selectedIds.length > 0) {
                $warning.hide();
            }
        } else {
            renderLiveNotifySelectedHint($modal, []);
            if ($warning.length) {
                $warning.hide();
            }
        }
    }

    function updateLiveScheduleUI($modal) {
        var scheduleEnabled = $modal.find(".live_schedule_toggle").is(":checked");
        $modal.find(".live_schedule_fields").toggle(scheduleEnabled);
        if (scheduleEnabled) {
            setLiveScheduleBounds($modal);
            populateLiveScheduleSelectors($modal);
        }
    }
    function padLiveScheduleNumber(val) {
        return val < 10 ? '0' + val : '' + val;
    }
    function getLiveScheduleMaxDays($modal) {
        var raw = $modal.find(".live_schedule_fields").data("max-days");
        var maxDays = parseInt(raw, 10);
        if (isNaN(maxDays) || maxDays <= 0) {
            maxDays = 30;
        }
        if (maxDays > 30) {
            maxDays = 30;
        }
        return maxDays;
    }
    function populateLiveScheduleSelectors($modal) {
        var $date = $modal.find(".live_schedule_date");
        var $hour = $modal.find(".live_schedule_hour");
        var $minute = $modal.find(".live_schedule_minute");
        if (!$date.length || !$hour.length || !$minute.length) {
            return;
        }
        var now = new Date();
        var minDate = new Date(now.getTime() + (5 * 60 * 1000));
        var maxDays = getLiveScheduleMaxDays($modal);
        var startDate = new Date(minDate.getFullYear(), minDate.getMonth(), minDate.getDate());
        if ($date.children().length === 0) {
            var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            for (var i = 0; i < maxDays; i++) {
                var d = new Date(startDate.getFullYear(), startDate.getMonth(), startDate.getDate() + i);
                var label = months[d.getMonth()] + ' ' + padLiveScheduleNumber(d.getDate());
                if (d.getFullYear() !== now.getFullYear()) {
                    label += ' ' + d.getFullYear();
                }
                var value = d.getFullYear() + '-' + padLiveScheduleNumber(d.getMonth() + 1) + '-' + padLiveScheduleNumber(d.getDate());
                $date.append('<option value="' + value + '">' + label + '</option>');
            }
        }
        if ($hour.children().length === 0) {
            for (var h = 0; h <= 23; h++) {
                var hv = padLiveScheduleNumber(h);
                $hour.append('<option value="' + hv + '">' + hv + '</option>');
            }
        }
        if ($minute.children().length === 0) {
            for (var mi = 0; mi <= 59; mi++) {
                var mv = padLiveScheduleNumber(mi);
                $minute.append('<option value="' + mv + '">' + mv + '</option>');
            }
        }
        var minDateValue = minDate.getFullYear() + '-' + padLiveScheduleNumber(minDate.getMonth() + 1) + '-' + padLiveScheduleNumber(minDate.getDate());
        if (!$date.val()) { $date.val(minDateValue); }
        if (!$hour.val()) { $hour.val(padLiveScheduleNumber(minDate.getHours())); }
        if (!$minute.val()) { $minute.val(padLiveScheduleNumber(minDate.getMinutes())); }
    }
    function getLiveScheduleDate($modal) {
        var dateVal = ($modal.find(".live_schedule_date").val() || '').toString();
        if (dateVal === '') {
            return null;
        }
        var parts = dateVal.split('-');
        if (parts.length !== 3) {
            return null;
        }
        var year = parseInt(parts[0], 10);
        var month = parseInt(parts[1], 10);
        var day = parseInt(parts[2], 10);
        var hour = parseInt($modal.find(".live_schedule_hour").val(), 10);
        var minute = parseInt($modal.find(".live_schedule_minute").val(), 10);
        if (!year || !month || !day || isNaN(hour) || isNaN(minute)) {
            return null;
        }
        var date = new Date(year, month - 1, day, hour, minute, 0);
        if (isNaN(date.getTime())) {
            return null;
        }
        return date;
    }
    function setLiveScheduleBounds($modal) {
        var $input = $modal.find(".live_schedule_datetime");
        if (!$input.length) {
            return;
        }
        var now = new Date();
        var minDate = new Date(now.getTime() + (5 * 60 * 1000));
        $input.attr("min", formatDateLocal(minDate));
        var maxDays = getLiveScheduleMaxDays($modal);
        if (maxDays > 0) {
            var maxDate = new Date(now.getTime() + (maxDays * 86400 * 1000));
            $input.attr("max", formatDateLocal(maxDate));
        } else {
            $input.removeAttr("max");
        }
    }

    $(document).on("change", ".live_notify_toggle", function() {
        var $modal = $(this).closest(".i_modal_bg_in");
        var $audience = $modal.find(".live_notify_audience");
        var isEnabled = $(this).is(":checked");
        $audience.prop("disabled", !isEnabled);
        $audience.attr("aria-disabled", isEnabled ? "false" : "true");
        updateLiveNotifyAudienceUI($modal);
    });

    $(document).on("change", ".live_schedule_toggle", function() {
        var $modal = $(this).closest(".i_modal_bg_in");
        updateLiveScheduleUI($modal);
    });

    $(document).on("change", ".live_notify_audience", function() {
        var $modal = $(this).closest(".i_modal_bg_in");
        updateLiveNotifyAudienceUI($modal);
    });

    $(document).on("click", ".live_notify_select_btn", function() {
        var $modal = $(this).closest(".i_modal_bg_in");
        if (!$modal.find(".live_notify_toggle").is(":checked")) {
            return;
        }
        var $audience = $modal.find(".live_notify_audience");
        if ($audience.val() !== "selected") {
            $audience.val("selected").trigger("change");
        }
        $("body").data("liveNotifyTarget", $modal);
        var selectedIds = $modal.find(".live_notify_selected_ids").val() || "";
        var data = "f=liveNotifyAudience&selected=" + encodeURIComponent(selectedIds);
        var csrfToken = $('meta[name=csrf-token]').attr('content') || $("#poll_csrf_token").val();
        if (csrfToken) {
            data += "&csrf_token=" + encodeURIComponent(csrfToken);
        }
        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: data,
            cache: false,
            success: function(response) {
                if (response !== "404") {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").last().addClass("i_modal_display_in");
                    }, 200);
                } else {
                    PopUPAlerts("sWrong", "ialert");
                }
            }
        });
    });

    $(document).on("click", ".live_notify_close, .live_notify_cancel", function() {
        var $popup = $(this).closest(".i_modal_bg_in");
        $popup.find(".i_modal_in_in").addClass("i_modal_in_in_out");
        setTimeout(function() {
            $popup.remove();
        }, 200);
    });

    $(document).on("click", ".live_notify_apply", function() {
        var $popup = $(this).closest(".i_modal_bg_in");
        var selectedIds = [];
        $popup.find(".live_notify_user_checkbox:checked").each(function() {
            var id = parseInt($(this).val(), 10);
            if (!isNaN(id) && id > 0) {
                selectedIds.push(id);
            }
        });
        var $target = $("body").data("liveNotifyTarget");
        if ($target && $target.length) {
            setLiveNotifySelectedIds($target, selectedIds);
            $target.find(".live_notify_selected_warning").hide();
        }
        $popup.find(".i_modal_in_in").addClass("i_modal_in_in_out");
        setTimeout(function() {
            $popup.remove();
        }, 200);
    });

    $(document).on("input", ".live_notify_search_input", function() {
        var $popup = $(this).closest(".i_modal_bg_in");
        var query = ($(this).val() || "").toString().toLowerCase().trim();
        var $cards = $popup.find(".live_notify_user_card");
        var $empty = $popup.find(".live_notify_search_empty");
        if (!$cards.length) {
            if ($empty.length) {
                $empty.removeClass("is-visible");
            }
            return;
        }
        if (query === "") {
            $cards.show();
            if ($empty.length) {
                $empty.removeClass("is-visible");
            }
            return;
        }
        var visibleCount = 0;
        $cards.each(function() {
            var $card = $(this);
            var name = ($card.data("name") || "").toString().toLowerCase();
            var username = ($card.data("username") || "").toString().toLowerCase();
            if (name.indexOf(query) !== -1 || username.indexOf(query) !== -1) {
                $card.show();
                visibleCount++;
            } else {
                $card.hide();
            }
        });
        if ($empty.length) {
            if (visibleCount === 0) {
                $empty.addClass("is-visible");
            } else {
                $empty.removeClass("is-visible");
            }
        }
    });
    /*Save Live Streaming*/
    $(document).on("click", ".createLiveStream", function() {
        var type = 'createFreeLiveStream';
        var $trigger = $(this);
        var $modal = $trigger.closest(".i_modal_bg_in");
        var liveStreamingTitle = $modal.find("#liveName").val();
        var notifyLive = $modal.find(".live_notify_toggle").prop("checked") ? 1 : 0;
        var notifyAudience = $modal.find(".live_notify_audience").val() || 'followers';
        var selectedIds = getLiveNotifySelectedIds($modal);
        if (notifyLive === 1 && notifyAudience === 'selected' && selectedIds.length === 0) {
            $modal.find(".live_notify_selected_warning").show();
            return;
        }
        var scheduleEnabled = $modal.find(".live_schedule_toggle").is(":checked");
        var scheduledAt = 0;
        if (scheduleEnabled) {
            populateLiveScheduleSelectors($modal);
            var dateVal = ($modal.find(".live_schedule_date").val() || '').toString();
            if (dateVal === '') {
                $modal.find(".live_schedule_required").show();
                return;
            }
            var scheduleDate = getLiveScheduleDate($modal);
            if (!scheduleDate) {
                $modal.find(".live_schedule_invalid").show();
                return;
            }
            scheduledAt = Math.floor(scheduleDate.getTime() / 1000);
            var maxDays = getLiveScheduleMaxDays($modal);
            var nowTs = Math.floor(Date.now() / 1000);
            if (scheduledAt < (nowTs + (5 * 60))) {
                $modal.find(".live_schedule_invalid").show();
                return;
            }
            if (maxDays > 0 && scheduledAt > (nowTs + (maxDays * 86400))) {
                $modal.find(".live_schedule_limit").show();
                return;
            }
        }
        var data = 'f=' + type + '&lTitle=' + encodeURIComponent(liveStreamingTitle) + '&notify_live=' + notifyLive + '&notify_audience=' + encodeURIComponent(notifyAudience);
        if (notifyAudience === 'selected' && selectedIds.length) {
            data += '&notify_selected_ids=' + encodeURIComponent(selectedIds.join(","));
        }
        if (scheduledAt > 0) {
            data += '&scheduled_at=' + scheduledAt;
        }
        var csrfToken = $('meta[name=csrf-token]').attr('content') || $("#poll_csrf_token").val();
        if (csrfToken) {
            data += '&csrf_token=' + encodeURIComponent(csrfToken);
        }
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() { $modal.find(".warning_required").hide(); },
            success: function(response) {
                var status = response.status;
                var start = response.start;
                if (status == '200' || status == 'scheduled') {
                    window.location.href = start;
                } else if (status == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                } else if (status == 'require') {
                    $modal.find(".require").show();
                } else if (status == '4') {
                    $modal.find(".name_short").show();
                } else if (status == 'schedule_invalid') {
                    $modal.find(".live_schedule_invalid").show();
                } else if (status == 'schedule_limit') {
                    $modal.find(".live_schedule_limit").show();
                } else if (status == 'schedule_disabled') {
                    $modal.find(".live_schedule_disabled").show();
                } else if (status == 'live_exists') {
                    $modal.find(".live_exists_warning").show();
                } else if (status == 'csrf' || status == 'error') {
                    var message = response.message || '';
                    var $csrfWarning = $modal.find(".live_csrf_warning");
                    if (message !== '' && $csrfWarning.length) {
                        $csrfWarning.text(message).show();
                    } else if ($csrfWarning.length) {
                        $csrfWarning.show();
                    } else {
                        PopUPAlerts('sWrong', 'ialert');
                    }
                }
            }
        });
    });
    /*Like Post*/
    $(document).on("click", ".lin_like , .lin_unlike", function() {
        var type = 'l_like';
        var ID = $(this).attr('data-id');
        var data = 'f=' + type + '&post=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            dataType: "json",
            data: data,
            cache: false,
            beforeSend: function() {
                $('.lin_like , .lin_unlike').prop('disabled', true);
            },
            success: function(response) {
                var status = response.status;
                var statusIcon = response.like;
                var liksCount = response.likeCount;
                if (status == 'lin_unlike') {
                    $("#p_l_l_" + ID).removeClass("lin_like").addClass("lin_unlike");
                    $("#lp_sum_l_" + ID).html(liksCount);
                } else {
                    $("#p_l_l_" + ID).removeClass("lin_unlike").addClass("lin_like");
                    $("#lp_sum_l_" + ID).html(liksCount);
                }
                $("#p_l_l_" + ID).html(statusIcon);
                $('.lin_like , .lin_unlike').prop('disabled', false);
            }
        });
    });
    /*Save Live Streaming*/
    $(document).on("click", ".createLiveStreamPaid", function() {
        var type = 'createPaidLiveStream';
        var $trigger = $(this);
        var $modal = $trigger.closest(".i_modal_bg_in");
        var liveStreamingTitle = $modal.find("#liveName").val();
        var liveFee = $modal.find("#lsFee").val();
        var notifyLive = $modal.find(".live_notify_toggle").prop("checked") ? 1 : 0;
        var notifyAudience = $modal.find(".live_notify_audience").val() || 'followers';
        var selectedIds = getLiveNotifySelectedIds($modal);
        if (notifyLive === 1 && notifyAudience === 'selected' && selectedIds.length === 0) {
            $modal.find(".live_notify_selected_warning").show();
            return;
        }
        var scheduleEnabled = $modal.find(".live_schedule_toggle").is(":checked");
        var scheduledAt = 0;
        if (scheduleEnabled) {
            populateLiveScheduleSelectors($modal);
            var dateVal = ($modal.find(".live_schedule_date").val() || '').toString();
            if (dateVal === '') {
                $modal.find(".live_schedule_required").show();
                return;
            }
            var scheduleDate = getLiveScheduleDate($modal);
            if (!scheduleDate) {
                $modal.find(".live_schedule_invalid").show();
                return;
            }
            scheduledAt = Math.floor(scheduleDate.getTime() / 1000);
            var maxDays = getLiveScheduleMaxDays($modal);
            var nowTs = Math.floor(Date.now() / 1000);
            if (scheduledAt < (nowTs + (5 * 60))) {
                $modal.find(".live_schedule_invalid").show();
                return;
            }
            if (maxDays > 0 && scheduledAt > (nowTs + (maxDays * 86400))) {
                $modal.find(".live_schedule_limit").show();
                return;
            }
        }
        var data = 'f=' + type + '&lTitle=' + encodeURIComponent(liveStreamingTitle) + '&pointfee=' + liveFee + '&notify_live=' + notifyLive + '&notify_audience=' + encodeURIComponent(notifyAudience);
        if (notifyAudience === 'selected' && selectedIds.length) {
            data += '&notify_selected_ids=' + encodeURIComponent(selectedIds.join(","));
        }
        if (scheduledAt > 0) {
            data += '&scheduled_at=' + scheduledAt;
        }
        var csrfToken = $('meta[name=csrf-token]').attr('content') || $("#poll_csrf_token").val();
        if (csrfToken) {
            data += '&csrf_token=' + encodeURIComponent(csrfToken);
        }
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {
                $modal.find(".warning_required").hide();
            },
            success: function(response) {
                var status = response.status;
                var start = response.start;
                if (status == '200' || status == 'scheduled') {
                    window.location.href = start;
                } else if (status == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                } else if (status == 'point') {
                    $modal.find(".point_warning").show();
                } else if (status == 'title') {
                    $modal.find(".title_warning").show();
                } else if (status == 'require') {
                    $modal.find(".require").show();
                } else if (status == 'schedule_invalid') {
                    $modal.find(".live_schedule_invalid").show();
                } else if (status == 'schedule_limit') {
                    $modal.find(".live_schedule_limit").show();
                } else if (status == 'schedule_disabled') {
                    $modal.find(".live_schedule_disabled").show();
                } else if (status == 'live_exists') {
                    $modal.find(".live_exists_warning").show();
                } else if (status == 'csrf' || status == 'error') {
                    var message = response.message || '';
                    var $csrfWarning = $modal.find(".live_csrf_warning");
                    if (message !== '' && $csrfWarning.length) {
                        $csrfWarning.text(message).show();
                    } else if ($csrfWarning.length) {
                        $csrfWarning.show();
                    } else {
                        PopUPAlerts('sWrong', 'ialert');
                    }
                }
            }
        });
    });
    /*Credit Card Form Call*/
    $(document).on("click", ".purchaseLiveButton", function() {
        var type = 'pLivePurchase';
        var post = $(this).attr("id");
        var data = 'f=' + type + '&purchase=' + post;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
    $(document).on("click", ".joinLiveStream", function() {
        var type = 'goWalletLive';
        var post = $(this).attr("id");
        var data = 'f=' + type + '&p=' + post;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                window.location.href = response;
            }
        });
    });
    /*Credit Card Form Call*/
    $(document).on("click", ".unSubU", function() {
        var type = 'unSub';
        var post = $(this).attr("data-u");
        var data = 'f=' + type + '&u=' + post;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
    /*Credit Card Form Call*/
    $(document).on("click", ".unSubUP", function() {
        var type = 'unSubP';
        var post = $(this).attr("data-u");
        var data = 'f=' + type + '&u=' + post;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
    /*UnSubscribe PointUser*/
    $(document).on("click", ".unSubPNow", function() {
        var type = 'unSubscribePoint';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var responseStatus = response.status;
                var responseRedirect = response.redirect;
                if (responseStatus == '200') {
                    location.reload();
                } else {
                    if (response.message) {
                        alert(response.message);
                    } else {
                        PopUPAlerts('sWrong', 'ialert');
                    }
                }

            }
        });
    });
    /*UnSubscribe User*/
    $(document).on("click", ".unSubNow", function() {
        var type = 'unSubscribe';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                var responseStatus = response.status;
                var responseRedirect = response.redirect;
                if (responseStatus == '200') {
                    location.reload();
                } else if (responseStatus == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                }

            }
        });
    });
    /*Upload Verification Files*/
    $(document).on("change", ".cTumb", function(e) {
        e.preventDefault();
        var f = 'vTumbnail';
        var id = $(this).attr("data-id");
        var data = { f: f, id: id };
        $("#tuploadform").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function() {
                $(".iu_f_" + id).append('<div class="i_upload_progress"></div>');
            },
            uploadProgress: function(e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function(response) {
                $("#viTumb" + id).css('background-image', 'url(' + response + ')');
                $("#viTumbi" + id).attr('src', response);
                $(".i_upload_progress").remove();
            },
            error: function() {}
        }).submit();
    });
    /*Credit Card Form Call*/
    $(document).on("click", ".bcmSubsPoint", function() {
        var type = 'creditPoint';
        var ID = $(this).attr("data-u");
        var subscribing = $(this).attr("id");
        var data = 'f=' + type + '&plan=' + subscribing + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
    /*Subscribe With Point*/
    $(document).on("click", ".subMyPoint", function() {
        var type = 'subWithPoints';
        var subscribingType = $(this).attr("id");
        var ID = $(this).attr("data-u");
        var data = 'f=' + type + '&pl=' + subscribingType + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".bySub").append(plreLoadingAnimationPlus);
                $(".cntsub , .insfsub").hide();
                $(".pay_subscription_point").css("pointer-events", "none");
            },
            success: function(response) {
                if (response == '404') {
                    $(".cntsub").show();
                } else if (response == '302') {
                    $(".insfsub").show();
                } else if (response == '200') {
                    location.reload();
                }
                $(".pay_subscription_point").css("pointer-events", "auto");
                setTimeout(function() {
                    $(".loaderWrapper").remove();
                }, 1500);
            }
        });
    });
    /*Call Post for Share*/
    $(document).on("click", ".in_tips", function() {
        var $btn = $(this);
        var ID = $btn.attr("data-id");
        var tipPostID = $btn.attr("data-ppid");
        var isCampaignDonate = $btn.data('mode') === 'campaign' || $btn.hasClass('campaign_primary_btn');
        if (isCampaignDonate && ($btn.data('expired') === 1 || $btn.data('expired') === '1')) {
            PopUPAlerts('campaign_deadline_expired', 'ialert');
            return;
        }
        var type = isCampaignDonate ? 'p_campaign_donate' : 'p_tips';
        if (!$(".i_bottom_left_alert_container")[0]) {
            var data = 'f=' + type + '&tip_u=' + ID + '&tpid=' + tipPostID;
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                cache: false,
                beforeSend: function() {
                    $('.in_tips').prop('disabled', true);
                },
                success: function(response) {
                    if (response != '404') {
                        $("body").append(response);
                        setTimeout(() => {
                            $(".i_modal_bg_in").addClass('i_modal_display_in');
                            $(".more_textarea").focus();
                            if (isCampaignDonate && typeof customizeCampaignDonateModal === 'function') {
                                customizeCampaignDonateModal($btn);
                            }
                        }, 200);
                    } else if (response == '404') {
                        PopUPAlerts('not_Shared', 'ialert');
                    }
                    $('.in_tips').prop('disabled', false);
                }
            });
        }
    });

    /* Show full campaign donors list */
    $(document).on("click", ".campaign_donor_trigger", function(e) {
        e.preventDefault();
        var postId = $(this).data('ppid');
        if (!postId) { return; }
        $(".campaign_donors_modal").remove();
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: { f: 'campaign_donors', post: postId },
            cache: false,
            success: function(response) {
                if (response && response !== '404') {
                    $("body").append(response);
                    setTimeout(function() {
                        $(".i_modal_bg_in").last().addClass('i_modal_display_in');
                        var $filter = $(".campaign_donor_filter").last();
                        if ($filter.length) {
                            $filter.trigger('focus');
                        }
                    }, 50);
                }
            }
        });
    });

    /* Filter donors client-side */
    $(document).on('input', '.campaign_donor_filter', function() {
        var term = $(this).val().toLowerCase().trim();
        var $tiles = $(this).closest('.campaign_donors_modal').find('.campaign_donor_tile');
        if (!term) {
            $tiles.show();
            return;
        }
        $tiles.each(function() {
            var name = ($(this).data('name') || '').toString();
            if (name.indexOf(term) !== -1) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    });

    function persistTipPaymentContext(userDetails) {
        if (!userDetails) { return; }
        var paymentType = (userDetails.payment_type || '').toString();
        if (paymentType !== 'tips' && paymentType !== 'campaign_donate') { return; }
        var payload = {
            orderId: userDetails.order_id || null,
            payedUserId: userDetails.payed_user_id || null,
            payedPostId: userDetails.payed_post_id || null,
            paymentType: paymentType,
            origin: window.location.href
        };
        try {
            localStorage.setItem('tipPaymentContext', JSON.stringify(payload));
        } catch (e) { /* ignore storage errors */ }
    }

    function captureTipResultFromPage() {
        var $result = $(".tip_payment_result").first();
        if (!$result.length) { return; }
        var context = null;
        try {
            var ctxRaw = localStorage.getItem('tipPaymentContext');
            context = ctxRaw ? JSON.parse(ctxRaw) : null;
        } catch (e) { context = null; }
        var data = {
            orderId: $result.data("order-id") || (context ? context.orderId : null),
            payedUserId: $result.data("payed-user-id") || (context ? context.payedUserId : null),
            payedPostId: $result.data("payed-post-id") || (context ? context.payedPostId : null),
            paymentType: $result.data("payment-type") || (context ? context.paymentType : null),
            origin: context ? context.origin : null
        };
        try {
            localStorage.removeItem('tipPaymentContext');
            localStorage.setItem('tipPaymentCompleted', JSON.stringify(data));
        } catch (e) { /* ignore */ }
        if (data.origin && window.location.href.indexOf(data.origin) === -1) {
            window.location.href = data.origin;
        }
    }

    function processTipPaymentResult() {
        var raw = null;
        try {
            raw = localStorage.getItem('tipPaymentCompleted');
        } catch (e) {
            raw = null;
        }
        if (!raw) { return; }
        var data;
        try {
            data = JSON.parse(raw);
        } catch (e) {
            localStorage.removeItem('tipPaymentCompleted');
            return;
        }
        var postId = data.payedPostId || data.payed_post_id || null;
        var userId = data.payedUserId || data.payed_user_id || null;
        var paymentType = (data.paymentType || data.payment_type || '').toString();
        var isCampaignDonation = paymentType === 'campaign_donate';
        if (userId || postId) {
            showBubble(userId, postId, isCampaignDonation);
        }
        localStorage.removeItem('tipPaymentCompleted');
    }
    captureTipResultFromPage();
    processTipPaymentResult();
    function sanitizeTipInput(value) {
        if (typeof value !== "string") { value = (value || "").toString(); }
        var normalized = value.replace(/,/g, ".").replace(/[^0-9.]/g, "");
        var parts = normalized.split(".");
        if (parts.length > 2) {
            normalized = parts[0] + "." + parts.slice(1).join("");
        }
        return normalized;
    }
    function updateTipConversion($context) {
        var note = $context.find(".i_tip_not").first();
        var input = $context.find("#tipVal").first();
        if (!note.length || !input.length) { return; }
        var baseNote = note.data("base-note") || note.text();
        var onePointEqual = parseFloat($context.find(".i_set_subscription_fee").data("one-point-equal")) || 0;
        var currency = $context.find(".i_set_subscription_fee").data("currency") || "";
        var amount = parseFloat(input.val());
        if (!amount || isNaN(amount) || onePointEqual <= 0) {
            note.text(baseNote);
            note.css("color", "");
            return;
        }
        var converted = (amount * onePointEqual).toFixed(2);
        var currencyText = currency ? currency + converted : converted;
        note.text(baseNote + " • " + currencyText);
        note.css("color", "");
    }
    $(document).on("input", "#tipVal", function() {
        var cleaned = sanitizeTipInput($(this).val());
        $(this).val(cleaned);
        updateTipConversion($(this).closest(".i_modal_in_in"));
    });
    function markTipValidationState($modal, hasError) {
        if (!$modal || !$modal.length) { return; }
        var $note = $modal.find(".i_tip_not, .donate_min_hint").first();
        var $input = $modal.find("#tipVal").first();
        if ($note.length) {
            $note.css("color", hasError ? "red" : "");
        }
        if ($input.length) {
            $input.css("border-color", hasError ? "#ef4444" : "");
        }
    }
    function getValidatedTipValue($modal) {
        if (!$modal || !$modal.length) { return ""; }
        var $input = $modal.find("#tipVal").first();
        if (!$input.length) { return ""; }
        var cleaned = sanitizeTipInput($input.val());
        $input.val(cleaned);
        var amount = parseFloat(cleaned);
        if (!cleaned || isNaN(amount) || amount <= 0) {
            markTipValidationState($modal, true);
            return "";
        }
        markTipValidationState($modal, false);
        return cleaned;
    }
    /*SEND TIPS*/
    $(document).on("click", ".send_tip_btn", function() {
        var $modal = $(this).closest(".i_modal_bg_in");
        var ID = $modal.find(".i_set_subscription_fee").attr("id");
        var tipValue = getValidatedTipValue($modal);
        var tipPostID = $modal.find(".i_set_subscription_fee").attr("data-pid");
        var isCampaignDonate = $(this).hasClass('donate_send_btn');
        var type = isCampaignDonate ? 'p_campaign_donate_send' : 'p_sendTip';
        var donateAnonymous = $modal.find("#donate_anonymous").length && $modal.find("#donate_anonymous").is(":checked") ? 1 : 0;
        if (!ID || !tipValue) {
            return;
        }
        if (!$(".i_bottom_left_alert_container")[0]) {
            var data = 'f=' + type + '&tip_u=' + ID + '&tipVal=' + tipValue + '&tpid=' + tipPostID + '&donate_anonymous=' + donateAnonymous;
            $.ajax({
                type: "POST",
                url: siteurl + 'requests/request.php',
                data: data,
                dataType: "json",
                cache: false,
                beforeSend: function() {
                    $('.send_tip_btn').prop('disabled', true);
                },
                success: function(response) {

                var responseStatus = response.status;
                var responseRedirect = response.redirect;
                var senamount = response.enamount;
                

                if (responseStatus == 'ok') {
                        showBubble(ID, tipPostID, isCampaignDonate);

                        if (isCampaignDonate) {
                            var raisedVal = response.raised;
                            var progressVal = response.progress;
                            var currency = response.currency || '';
                            updateCampaignCardUI(tipPostID, raisedVal, progressVal, currency);
                        }

                        $(".i_modal_in_in").addClass("i_modal_in_in_out");
                        setTimeout(() => {
                            $(".i_modal_bg_in").remove();
                        }, 200);
                    }

                    if (senamount == 'notEnough') {
                        markTipValidationState($modal, true);
                        if (responseRedirect) {
                            window.location.href = responseRedirect;
                            return;
                        }
                    }
                    if (senamount == 'expired') {
                        PopUPAlerts('campaign_deadline_expired', 'ialert');
                    }
                    if (senamount == 'notEnouhCredit') {
                        window.location.href = responseRedirect;
                    }
                    $('.send_tip_btn').prop('disabled', false);
                },
                error: function() {
                    markTipValidationState($modal, true);
                    $('.send_tip_btn').prop('disabled', false);
                }
            });
        }
    });
    $(document).on("click", ".send_tip_methods_btn", function() {
        var $modal = $(this).closest(".i_modal_bg_in");
        var isCampaignDonate = $(this).hasClass('donate_methods_btn') || $modal.find(".donate_send_btn").length > 0;
        var userId = $modal.find(".i_set_subscription_fee").attr("id");
        var tipPostID = $modal.find(".i_set_subscription_fee").attr("data-pid");
        var tipValue = getValidatedTipValue($modal);
        var donateAnonymous = $modal.find("#donate_anonymous").length && $modal.find("#donate_anonymous").is(":checked") ? 1 : 0;
        if (!userId || !tipValue) {
            return;
        }
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: {
                f: isCampaignDonate ? 'campaign_payment_methods' : 'tip_payment_methods',
                tip_u: userId,
                tpid: tipPostID,
                tipVal: tipValue,
                donate_anonymous: donateAnonymous
            },
            cache: false,
            beforeSend: function() {
                $('.send_tip_methods_btn').prop('disabled', true);
            },
            success: function(response) {
                $('.send_tip_methods_btn').prop('disabled', false);
                if (response && response !== '404') {
                    $(".i_modal_bg_in").removeClass('i_modal_display_in');
                    $("body").append(response);
                    setTimeout(function() {
                        $(".i_modal_bg_in").last().addClass('i_modal_display_in');
                        persistTipPaymentContext(window.userData || {});
                    }, 60);
                } else {
                    markTipValidationState($modal, true);
                }
            },
            error: function() {
                $('.send_tip_methods_btn').prop('disabled', false);
                markTipValidationState($modal, true);
            }
        });
    });

    function updateCampaignCardUI(postId, raisedVal, progressVal, currency) {
        if (!postId) { return; }
        var $card = $(".campaign_cta_row a[data-ppid='" + postId + "']").closest(".campaign_card");
        if (!$card.length) {
            $card = $(".campaign_card[data-postid='" + postId + "']");
        }
        if ($card.length) {
            var newRaisedNumeric = null;
            if (raisedVal !== null && raisedVal !== undefined) {
                var $raised = $card.find(".campaign_raised_value");
                if ($raised.length) {
                    var currencyText = currency ? " " + currency : "";
                    var currentText = $raised.text().trim();
                    var numericCurrent = parseFloat(currentText.replace(/[^\d.-]/g, ''));
                    var newVal = raisedVal;
                    if (!newVal || isNaN(parseFloat(newVal))) {
                        newVal = numericCurrent || 0;
                    }
                    newRaisedNumeric = parseFloat(newVal);
                    var formatted = newVal;
                    $raised.text(formatted + currencyText);
                }
            }
            var progressNumeric = null;
            if (progressVal !== null && progressVal !== undefined && !isNaN(parseFloat(progressVal))) {
                progressNumeric = parseFloat(progressVal);
            } else {
                // try to compute from raised/goal
                var goalText = $card.find(".campaign_figure.align_end .figure_value").text().trim();
                var goalNumeric = parseFloat(goalText.replace(/[^\d.-]/g, ''));
                var raisedTextDom = $card.find(".campaign_raised_value").text().trim();
                var raisedNumeric = newRaisedNumeric !== null ? newRaisedNumeric : parseFloat(raisedTextDom.replace(/[^\d.-]/g, ''));
                if (goalNumeric && !isNaN(goalNumeric) && raisedNumeric && !isNaN(raisedNumeric) && goalNumeric > 0) {
                    progressNumeric = Math.min(100, (raisedNumeric / goalNumeric) * 100);
                }
            }
            if (progressNumeric !== null && !isNaN(progressNumeric)) {
                var displayVal;
                if (progressNumeric >= 10) {
                    displayVal = progressNumeric.toFixed(0);
                } else if (progressNumeric >= 1) {
                    displayVal = progressNumeric.toFixed(1);
                } else {
                    displayVal = progressNumeric.toFixed(2);
                }
                var progressText = displayVal + "%";
                $card.find(".campaign_progress_value").text(progressText);
                $card.find(".campaign_progress_bar_fill").css("width", progressNumeric + "%");
            }
        }
    }

    function customizeCampaignDonateModal(btn) {
        var lang = {};
        if (btn && btn.length) {
            lang.title = btn.data('lang-title');
            lang.send = btn.data('lang-send');
            lang.amount = btn.data('lang-amount');
            lang.min = btn.data('lang-min');
        }
        var modal = $(".i_modal_bg_in").last();
        if (!modal.length) { return; }
        var header = modal.find("#sendTipModalTitle");
        if (header.length && lang.title) {
            var textNode = header.contents().filter(function() { return this.nodeType === 3; }).first();
            if (textNode.length) { textNode[0].nodeValue = lang.title; }
        }
        var amountInput = modal.find("#tipVal");
        if (amountInput.length && lang.amount) {
            amountInput.attr("placeholder", lang.amount);
            amountInput.attr("aria-label", lang.amount);
        }
        var minNote = modal.find(".i_tip_not");
        if (minNote.length && lang.min) {
            minNote.text(lang.min);
            minNote.data("base-note", lang.min);
        }
        var sendBtn = modal.find(".send_tip_btn");
        if (sendBtn.length && lang.send) {
            var iconHtml = "";
            var iconEl = sendBtn.find("svg").first();
            if (iconEl.length) { iconHtml = iconEl.prop("outerHTML"); }
            sendBtn.html(iconHtml + " " + lang.send);
        }
    }
    function showBubble(userid, postid, isCampaign) {
        var $bubble = $(".tip_" + postid);
        if ($bubble.length) {
            var $text = $bubble.find(".i_bubble");
            var donationText = typeof LANG !== "undefined" && LANG.thanks_for_donation ? LANG.thanks_for_donation : "Thanks for the donation.";
            var tipText = typeof LANG !== "undefined" && LANG.thanks_for_tip ? LANG.thanks_for_tip : "Thanks for the tip.";
            if ($text.length) {
                $text.text(isCampaign ? donationText : tipText);
            }
            $bubble.show();
            document.getElementById('notification-sound-coin').play();
            setTimeout(() => {
                $bubble.fadeOut();
            }, 5000);
        }
    }
    $(document).on("click", ".live_coin_send", function() {
        if (String(window.liveGiftEnabled || "1") !== "1") {
            return;
        }
        var ID = $(this).attr("data-u");
        var tipValue = $(this).attr("data-tip");
        var liveID = $(".live_wrapper_tik").attr("id");
        var type = 'p_sendGift';
        var data = 'f=' + type + '&tip_u=' + ID + '&tipTyp=' + tipValue + '&lid=' + liveID;

        function getLiveGiftAnimationTarget() {
            var $target = $(".live_vide__holder:visible").first();
            if (!$target.length) {
                $target = $(".live_vide__holder").first();
            }
            if (!$target.length) {
                $target = $(".filtvid:visible").first();
            }
            if (!$target.length) {
                $target = $(".filtvid").first();
            }
            return $target;
        }

        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            dataType: "json",
            cache: false,
            beforeSend: function() {
                $('.co_' + tipValue).append(plreLoadingAnimationPlus);
                $(".live_animation_wrapper").remove();
            },
            success: function(response) {
                setTimeout(function() {
                    $(".loaderWrapper").remove();
                }, 1000);
                var responseStatus = response.status;
                var responseRedirect = response.redirect;
                var senamount = response.enamount;
                var sendSuccessAnimation = response.giftAnimation;
                var currentBalance = response.current_balance;
                if (responseStatus == 'disabled') {
                    return;
                }
                if (responseStatus == 'ok') {
                    var $animationTarget = getLiveGiftAnimationTarget();
                    var $animation = $(sendSuccessAnimation);
                    if ($animationTarget.length && $animation.length) {
                        $animationTarget.append($animation);
                    }
                    setTimeout(() => {
                        $(".live_animation_wrapper").remove();
                    }, 2800);
                    $(".crnblnc").html(currentBalance);
                }
                if (senamount == 'notEnough') {
                    $(".i_tip_not").css("color", "red");
                }
                if (senamount == 'notEnouhCredit') {
                    window.location.href = responseRedirect;
                }
            }
        });
    });
    /*QR Code Generator*/
    $(document).on("click", ".qrCodeGenerator", function() {
        var type = 'generateQRCode';
        var data = 'f=' + type;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {
                $('.createQrBox').append(plreLoadingAnimationPlus);
                $('.qrCodeGenerator').prop('disabled', true);
            },
            success: function(response) {
                $(".loaderWrapper").remove();
                if (response) {
                    $(".qrCodeImage").html('<img src="' + response + '">');
                }
                $('.qrCodeGenerator').prop('disabled', false);
            }
        });
    });
    /*Delete Post From Database*/
    $(document).on("click", ".yes-del-story", function() {
        var type = 'deleteStorie';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 100);
                if (response == '200') {
                    $(".body_" + ID).fadeOut();
                    PopUPAlerts('delete_success', 'ialert');
                } else {
                    PopUPAlerts('delete_not_success', 'ialert');
                }
            }
        });
    });
    /*Delete Post From Database*/
    $(document).on("click", ".yes-del-product", function() {
        var type = 'deleteProduct';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 100);
                if (response == '200') {
                    $(".body_" + ID).fadeOut();
                    PopUPAlerts('delete_success', 'ialert');
                } else {
                    PopUPAlerts('delete_not_success', 'ialert');
                }
            }
        });
    });
    /*Share This Story*/
    $(document).on("click", ".share_this_story", function() {
        var type = 'shareMyStorie';
        var ID = $(this).attr("id");
        var $container = $(".body_" + ID);
        var text = $(".st_txt_" + ID).val();
        var privacy = $container.find(".story_privacy").val() || 'followers';
        var overlayLink = $container.find(".story_overlay_link").val() || '';
        var overlayMention = $container.find(".story_overlay_mention").val() || '';
        var overlaySticker = $container.find(".story_overlay_sticker").val() || '';
        var overlayAudio = $container.find(".story_overlay_audio").val() || '';
        var quickReplies = [];
        var $quickBlock = $container.find(".story_quick_replies_block");
        if ($quickBlock.length && String($quickBlock.data("enabled")) === "1") {
            $quickBlock.find(".story_quick_reply_input").each(function() {
                var value = $(this).val() || '';
                value = value.replace(/\s+/g, ' ').trim();
                if (value) {
                    quickReplies.push(value);
                }
            });
        }
        if (quickReplies.length > 5) {
            quickReplies = quickReplies.slice(0, 5);
        }
        var data = 'f=' + type + '&id=' + ID + '&txt=' + encodeURIComponent(text) +
            '&privacy=' + encodeURIComponent(privacy) +
            '&overlay_link=' + encodeURIComponent(overlayLink) +
            '&overlay_mention=' + encodeURIComponent(overlayMention) +
            '&overlay_sticker=' + encodeURIComponent(overlaySticker) +
            '&overlay_audio=' + encodeURIComponent(overlayAudio);
        if (quickReplies.length) {
            data += '&quick_replies=' + encodeURIComponent(JSON.stringify(quickReplies));
        }
        var csrfToken = $('meta[name="csrf-token"]').attr('content') || '';
        if (csrfToken) {
            data += '&csrf_token=' + encodeURIComponent(csrfToken);
        }
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response == '200') {
                    $(".body_" + ID).fadeOut();
                    PopUPAlerts('shared_storie_success', 'ialert');
                    window.location = siteurl;
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    $(document).on("click", ".story_quick_replies_trigger", function(e) {
        e.preventDefault();
        var $button = $(this);
        var $options = $button.closest(".story_options");
        var $block = $options.find(".story_quick_replies_block").first();
        if (!$block.length) {
            return;
        }
        var isEnabled = String($block.data("enabled")) === "1";
        if (isEnabled) {
            $block.addClass("is-hidden").data("enabled", "0");
            $button.attr("aria-expanded", "false");
            $button.text($button.data("add-label") || $button.text());
        } else {
            $block.removeClass("is-hidden").data("enabled", "1");
            $button.attr("aria-expanded", "true");
            $button.text($button.data("remove-label") || $button.text());
            $block.find(".story_quick_reply_input").first().focus();
        }
    });
    /*Mention Autocomplete*/
    var timer = null;
    var tagword = /@(\w+)(?!.*@\w)/;

    $(document).delegate(".newPostT", "keyup", function(e) {
        var value = e.target.value;
        var names = $(this).val().split(' ');
        var firstLetterOfSurname = names[names.length - 1].charAt(0);
        clearTimeout(timer);
        if (firstLetterOfSurname == '@') {
            timer = setTimeout(function() {
                var contents = value;
                var goname = contents.match(tagword);
                var type = 'mfriends';
                if (goname !== null) {
                    if (goname.length > 0) {
                        var data = 'f=' + type + '&menFriend=' + goname[1];
                        $.ajax({
                            type: "POST",
                            url: siteurl + "requests/request.php",
                            data: data,
                            cache: false,
                            beforeSend: function() {
                                // Do Something
                            },
                            success: function(response) {
                                if (response) {
                                    $(".mentions_list").show().html(response);
                                } else {
                                    $(".mentions_list").hide().html('');
                                }
                            }
                        });
                    }
                } else {
                    $(".mentions_list").hide().html('');
                }
            }, 500);
        }
    });
    (function($) {
        $.fn.focusTextToEnd = function() {
            this.focus();
            var $thisVal = this.val();
            this.val('').val($thisVal);
            return this;
        }
    }(jQuery));


    /*Click To Add Mentioned User*/
    $(document).on("click", ".mres_u", function() {
        var words = $(".newPostT").val().split(' ');
        if (words.length > 0) {
            var username = $(this).attr("data-user");
            if (words[words.length - 1].startsWith('@')) {
                words[words.length - 1] = "@" + username + ' ';
                $(".newPostT").val(words.join(' ')).focus();
                $(".mentions_list").hide().html('');
                $('.newPostT').focusTextToEnd();
            }
        }
    });
    var timeoutId;
    // Append User Profile Card
    $(document).on("mouseenter", ".ownTooltip", function() {
        if ($(window).width() > 800) {
            var tooltipText = $(this).attr("data-label");
            $(this).append("<div class='ownTooltipWrapper'>" + tooltipText + "</div>");
        }
    });
    $(document).on('mouseleave', '.ownTooltip', function() {
        $(".ownTooltipWrapper").fadeOut("normal", function() {
            window.clearTimeout(timeoutId);
            timeoutId = null;
            $(this).remove();
        });
    });
    /*Get PopUp for Which Story*/
    $(document).on("click", ".chsStoryw", function() {
        var type = 'whcStory';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response) {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                }
            }
        });
    });
    $(document).on("click", ".share_text_story", function() {
        var type = 'shareMyTextStory';
        var ID = $(".choosed_bg").attr("data-iid");
        var textStory = $("#strt_text").val();
        var $options = $(".story_text_options");
        var privacy = $options.find(".story_privacy").val() || 'followers';
        var overlayLink = $options.find(".story_overlay_link").val() || '';
        var overlayMention = $options.find(".story_overlay_mention").val() || '';
        var overlaySticker = $options.find(".story_overlay_sticker").val() || '';
        var overlayAudio = $options.find(".story_overlay_audio").val() || '';
        var quickReplies = [];
        var $quickBlock = $options.find(".story_quick_replies_block");
        if ($quickBlock.length && String($quickBlock.data("enabled")) === "1") {
            $quickBlock.find(".story_quick_reply_input").each(function() {
                var value = $(this).val() || '';
                value = value.replace(/\s+/g, ' ').trim();
                if (value) {
                    quickReplies.push(value);
                }
            });
        }
        if (quickReplies.length > 5) {
            quickReplies = quickReplies.slice(0, 5);
        }
        var data = 'f=' + type + '&id=' + ID + '&stext=' + encodeURIComponent(textStory) +
            '&privacy=' + encodeURIComponent(privacy) +
            '&overlay_link=' + encodeURIComponent(overlayLink) +
            '&overlay_mention=' + encodeURIComponent(overlayMention) +
            '&overlay_sticker=' + encodeURIComponent(overlaySticker) +
            '&overlay_audio=' + encodeURIComponent(overlayAudio);
        if (quickReplies.length) {
            data += '&quick_replies=' + encodeURIComponent(JSON.stringify(quickReplies));
        }
        var csrfToken = $('meta[name="csrf-token"]').attr('content') || '';
        if (csrfToken) {
            data += '&csrf_token=' + encodeURIComponent(csrfToken);
        }
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                if (response == '200') {
                    PopUPAlerts('shared_storie_success', 'ialert');
                    setTimeout(() => {
                        window.location = siteurl;
                    }, 1000);
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            }
        });
    });
    $(document).on("click", ".buy__myproduct", function() {
        var type = 'buyProduct';
        var pointID = $(this).attr("data-id");
        var data = 'f=' + type + '&type=' + pointID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".credit_plan_box").css("pointer-events", "none");
                $("#p_i_" + pointID).append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == 'me') {
                    PopUPAlerts('cnbowni', 'ialert');
                } else {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                    $(".loaderWrapper").remove();
                    $(".credit_plan_box").css("pointer-events", "auto");
                }
            }
        });
    });
    $(document).on("click", ".s_p_p_p_download", function() {
        var type = 'downloadMyProduct';
        var pointID = $(this).attr("data-id");
        var data = 'f=' + type + '&myp=' + pointID;
        $.ajax({
            type: "POST",
            url: siteurl + 'dfile.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".s_p_p_p_download").css("pointer-events", "none");
            },
            success: function(response) {
                if (response == 'me') {
                    PopUPAlerts('sRong', 'ialert');
                }
                setTimeout(() => {
                    $(".s_p_p_p_download").css("pointer-events", "auto");
                }, 2000);

            }
        });
    });
    /*Call Post for Share*/
    $(document).on("click", ".sendPoint", function() {
        var ID = $(this).attr("data-u");
        var type = 'p_p_tips';
        if (!$(".i_bottom_left_alert_container")[0]) {
            var data = 'f=' + type + '&tp_u=' + ID;
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                cache: false,
                beforeSend: function() {
                    $('.sendPoint').prop('disabled', true);
                },
                success: function(response) {
                    if (response != '404') {
                        $("body").append(response);
                        setTimeout(() => {
                            $(".i_modal_bg_in").addClass('i_modal_display_in');
                            $(".more_textarea").focus();
                        }, 200);
                    } else if (response == '404') {
                        PopUPAlerts('not_Shared', 'ialert');
                    }
                    $('.sendPoint').prop('disabled', false);
                }
            });
        }
    });
    $(document).on("click", ".sendPointMessage", function() {
        var ID = $(this).attr("data-u");
        var type = 'p_p_tips_message';
        if (!$(".i_bottom_left_alert_container")[0]) {
            var data = 'f=' + type + '&tp_u=' + ID;
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                cache: false,
                beforeSend: function() {
                    $('.sendPointMessage').prop('disabled', true);
                },
                success: function(response) {
                    if (response != '404') {
                        $("body").append(response);
                        setTimeout(() => {
                            $(".i_modal_bg_in").addClass('i_modal_display_in');
                            $(".more_textarea").focus();
                        }, 200);
                    } else if (response == '404') {
                        PopUPAlerts('not_Shared', 'ialert');
                    }
                    $('.sendPointMessage').prop('disabled', false);
                }
            });
        }
    });
    function VideoCallAlert(callID) {
        var data = 'f=videoCallAlert' + '&call=' + callID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {},
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                        $("#notification-sound-call")[0].play();
                    }, 200);
                }
            }
        });
    }
    $(document).on("click", ".call_accept", function() {
        var ID = $(this).attr("id");
        var type = 'call_accepted';
        var data = 'f=' + type + '&accID=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $('.call_accept').prop('disabled', true);
                $(".i_modal_in_in").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response) {
                    $("#notification-sound-call")[0].pause();
                    window.location.href = response;
                }
            }
        });
    });
    $(document).on("click", ".call_decline", function() {
        var ID = $(this).attr("id");
        var type = 'call_declined';
        var data = 'f=' + type + '&accID=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $('.call_decline').prop('disabled', true);
                $(".i_modal_in_in").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                $(".i_modal_bg_in").remove();
                $("#notification-sound-call")[0].pause();
            }
        });
    });
    /*SEND TIPS*/
    $(document).on("click", ".send_tip_btn_message", function() {
        var ID = $(".subU").attr("id");
        var chatID = $(".message_send_form_wrapper").attr("id");
        var tipValue = $("#tipVal").val();
        var type = 'p_sendTipMessage';
        if (!$(".i_bottom_left_alert_container")[0]) {
            var data = 'f=' + type + '&tip_u=' + ID + '&tipVal=' + tipValue +'&chID=' + chatID;
            $.ajax({
                type: "POST",
                url: siteurl + 'requests/request.php',
                data: data,
                cache: false,
                beforeSend: function() {
                    $('.send_tip_btn').prop('disabled', true);
                },
                success: function(response) {
                    if (response == 'notEnough') {
                        $(".i_tip_not").css("color", "red");
                    }
                    if (response == 'notEnouhCredit') {
                        window.location.href = siteurl + 'purchase/purchase_point';
                    }

                    if (response != '404') {
                        if(response){
                            PopUPAlerts('tipSuccess', 'ialert');
                            $(".i_modal_in_in").addClass("i_modal_in_in_out");
                            setTimeout(() => {
                                $(".i_modal_bg_in").remove();
                            }, 200);
                            $(".all_messages_container").append(response);
                            ScrollBottomChat();
                        }else{
                            $(".aval").val('').focus();
                        }
                    }
                    $('.send_tip_btn_message').prop('disabled', false);
                }
            });
        }
    });
    $(document).on("click", ".send_tip_btn_profile", function() {
        var ID = $(".i_set_subscription_fee").attr("id");
        var tipValue = $("#tipVal").val();
        var type = 'p_sendTipProfile';
        if (!$(".i_bottom_left_alert_container")[0]) {
            var data = 'f=' + type + '&tip_u=' + ID + '&tipVal=' + tipValue;
            $.ajax({
                type: "POST",
                url: siteurl + 'requests/request.php',
                data: data,
                dataType: "json",
                cache: false,
                beforeSend: function() {
                    $('.send_tip_btn').prop('disabled', true);
                },
                success: function(response) {

                    var responseStatus = response.status;
                    var responseRedirect = response.redirect;
                    var senamount = response.enamount;
                    if (responseStatus == 'ok') {
                        PopUPAlerts('tipSuccess', 'ialert');
                        $(".i_modal_in_in").addClass("i_modal_in_in_out");
                        setTimeout(() => {
                            $(".i_modal_bg_in").remove();
                        }, 200);
                    }

                    if (senamount == 'notEnough') {
                        $(".i_tip_not").css("color", "red");
                    }
                    if (senamount == 'notEnouhCredit') {
                        window.location.href = responseRedirect;
                    }
                    $('.send_tip_btn').prop('disabled', false);
                }
            });
        }
    });
    /*Unlock Message*/
    $(document).on("click",".unlockFor", function(){
        var ID = $(this).attr("id");
        var cID = $(".message_send_form_wrapper").attr("id");
        var type = 'unlockMessage';
        var data = 'f='+type+'&mi='+ID+'&ci='+cID;
        var csrfToken = $('meta[name=csrf-token]').attr('content') || $("#poll_csrf_token").val();
        if (csrfToken) {
            data += '&csrf_token=' + encodeURIComponent(csrfToken);
        }
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
            },
            success: function(response) {
                if(response == '404'){
                   $(".unlc_"+ID).show();
                }else if(response == '403'){
                  PopUPAlerts('sWrong', 'ialert');
                }else if ((response || '').toString().toLowerCase().indexOf('csrf') !== -1) {
                  PopUPAlerts('sWrong', 'ialert');
                }else{
                   $("#msg_"+ID).html('').append(response);
                }
            }
        });
    });
    $(document).on("click",".joinOffline", function(){
        PopUPAlerts('camOffline', 'camAlert');
    });
    $(document).on("click",".rplyComment", function(){
        var postID = $(this).attr('data-post') || $(this).attr('id');
        var commentID = $(this).attr('data-comment');
        var who = $(this).attr('data-fullname') || $(this).attr("data-who");
        var username = $(this).attr("data-who") || '';
        if (!postID || !commentID) {
            return;
        }
        setReplyContext(postID, commentID, who);
        var $input = getCommentInput(postID);
        if ($input.length) {
            $input.focus();
            var currentValue = $input.val() || '';
            if (username && currentValue.indexOf('@' + username) === -1) {
                var prefix = currentValue.length ? currentValue + ' ' : '';
                $input.val(prefix + '@' + username + ' ');
            }
        }
    });
    $(document).on("click",".boostThisPost", function(){
        var type = 'getBoostList';
        var ID = $(this).attr("id");
        var data = 'f='+type+'&bp='+ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
	    $(document).on("click",".bThisP", function(){
	        var type = 'boostThisPlan';
	        var planID = $(this).attr("id");
	        var postID = $(this).attr("data-post");
	        var data = 'f='+type+'&pbID='+planID+'&bpID='+postID;
	        var csrfToken = $('meta[name=csrf-token]').attr('content') || $("#poll_csrf_token").val();
	        if (csrfToken) {
	            data += '&csrf_token=' + encodeURIComponent(csrfToken);
	        }
	        $.ajax({
	            type: "POST",
	            url: siteurl + 'requests/request.php',
	            data: data,
            cache: false,
            beforeSend: function() {
                $(".i_modal_in_in").append(plreLoadingAnimationPlus);
                $(".warning_boost_post").hide();
            },
	            success: function(response) {
	                if (response == '404') {
	                    $(".warning_boost_post").show();
	                } else if ((response || '').toString().toLowerCase().indexOf('csrf') !== -1) {
	                    window.location.reload();
	                } else {
	                    window.location.href = response;
	                }
	                $(".loaderWrapper").remove();
	            }
	        });
	    });
	    /*Change Notifications*/
	    $(document).on("click", ".boosStat", function() {
	        var $el = $(this);
	        var type = 'updateBoostStatus';
	        var setChange = $el.val();
	        var setType = $el.attr("data-id");
	        var dataNot = 'f=' + type + '&mod=' + encodeURIComponent(setChange) + '&bpid=' + setType;
	        var csrfToken = $('meta[name=csrf-token]').attr('content') || $("#poll_csrf_token").val();
	        if (csrfToken) {
	            dataNot += '&csrf_token=' + encodeURIComponent(csrfToken);
	        }
	        $.ajax({
	            type: 'POST',
	            url: siteurl + "requests/request.php",
	            data: dataNot,
	            cache: false,
	            beforeSend: function() {

	            },
	            success: function(response) {
	                if ((response || '').toString().toLowerCase().indexOf('csrf') !== -1) {
	                    window.location.reload();
	                    return;
	                }
	                if (response == '200') {
	                    $el.val(setChange === 'yes' ? 'no' : 'yes');
	                }
	            }
	        });
	    });
    $(document).on("click",".bankOpen", function(){
        if ($("div").hasClass("displayNone")) {
            $(".bank_container").removeClass('displayNone');
        }else{
            $(".bank_container").addClass('displayNone');
        }
    });
    /*Upload Verification Files*/
    $(document).on("change", "#id_card", function(e) {
        e.preventDefault();
        var id = $(this).attr("data-id");
        var type = $(this).attr("data-type");
        var data = { f: id, c: type };
        $("#pBUploadForm").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function() {
                $(".f_" + type).html('');
                $("#uploadVal_" + type).val('');
                $("#" + type).append('<div class="i_upload_progress"></div>');
            },
            uploadProgress: function(e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function(response) {
                if (response) {
                    $(".f_" + type).append(response);
                    var K = $(".f_" + type + " > div:last").attr("id");
                    var T = K;
                    if (T != "undefined,") {
                        $("#uploadVal_" + type).val(T);
                    }
                    $("#id_card").val('');
                }
                $(".i_upload_progress").remove();
            },
            error: function() {}
        }).submit();
    });
    /*Send Verification Certificate Request*/
    $(document).on("click", ".bnk_Next", function() {
        var type = 'verificationRequestForBankPayment';
        var IDPhoto = $("#uploadVal_sec_one").val();
        var planID = $(this).attr('id');
        var planAmount = $("#bank_plan_amount").val();
        var planPoints = $("#bank_plan_points").val();
        var bankPlanId = $("#bank_plan_id").val();
        var payload = { f: type, cP: IDPhoto, pID: planID };
        var csrfToken = $('meta[name=csrf-token]').attr('content') || $("#poll_csrf_token").val();
        if (csrfToken) {
            payload.csrf_token = csrfToken;
        }
        if (bankPlanId) {
            payload.plan_id = bankPlanId;
        }
        if (planAmount) {
            payload.plan_amount = planAmount;
        }
        if (planPoints) {
            payload.plan_points = planPoints;
        }
        if (window.userData && window.userData.payment_type === 'agency_boost') {
            payload.payment_type = 'agency_boost';
            payload.agency_id = window.userData.agency_id || '';
            payload.creator_id = window.userData.creator_id || window.userData.payed_profile_id || '';
            payload.duration_days = window.userData.duration_days || '';
            payload.order_id = window.userData.order_id || '';
        }
        var data = $.param(payload);
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".i_nex_btn").css("pointer-events", "none");
                $(".card , .both , .photo").hide();
            },
            success: function(response) {
                if (response == '200') {
                    $(".payment_success_bank").show();
                    $(".bank_container").hide();
                    setTimeout(() => {
                        window.location.href = siteurl + 'settings?tab=purchased_points';
                    }, 5000);
                } else if (response == 'card') {
                    $("#id_card").val('');
                    $(".card").show();
                } else if (response == 'photo') {
                    $("#id_card").val('');
                    $(".photo").show();
                } else if (response == 'both') {
                    $("#id_card").val('');
                    $(".both").show();
                }
                $(".i_nex_btn").css("pointer-events", "auto");
            }
        });
    });
    /*Call Post for Share*/
    $(document).on("click", ".sendFrame", function() {
        var ID = $(this).attr("data-u");
        var type = 'p_p_frame';
        if (!$(".i_bottom_left_alert_container")[0]) {
            var data = 'f=' + type + '&tp_u=' + ID;
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: data,
                cache: false,
                beforeSend: function() {
                    $('.sendPoint').prop('disabled', true);
                },
                success: function(response) {
                    if (response != '404') {
                        $("body").append(response);
                        setTimeout(() => {
                            $(".i_modal_bg_in").addClass('i_modal_display_in');
                            $(".more_textarea").focus();
                        }, 200);
                    } else if (response == '404') {
                        PopUPAlerts('not_Shared', 'ialert');
                    }
                    $('.sendPoint').prop('disabled', false);
                }
            });
        }
    });
    $(document).on("click", ".buyFrameGift", function() {
        var type = 'buyFrameGift';
        var $currentFrameCard = $(this);
        var pointID = $currentFrameCard.attr("id");
        var purchaseToThisUser = $(".profile_wrapper").attr("data-u");
        var csrfToken = $('meta[name=csrf-token]').attr('content') || $("#poll_csrf_token").val() || '';
        var data = 'f=' + type + '&type=' + pointID + '&pUf=' + purchaseToThisUser;
        if (csrfToken) {
            data += '&csrf_token=' + encodeURIComponent(csrfToken);
        }
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $currentFrameCard.css("pointer-events", "none");
                $currentFrameCard.append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '404') {
                    PopUPAlerts('sWrong', 'ialert');
                } else if (response == '200') {
                    PopUPAlerts('buySuccess', 'ialert');
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else if (response == 'notEnouhCredit') {
                    window.location.href = siteurl + 'purchase/purchase_point';
                } else if (typeof response === 'string' && response.indexOf('purchase/purchase_point') !== -1) {
                    window.location.href = response;
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            },
            error: function() {
                PopUPAlerts('sWrong', 'ialert');
            },
            complete: function() {
                $currentFrameCard.css("pointer-events", "auto");
                $currentFrameCard.find(".loaderWrapper").remove();
            }
        });
    });
    /*Update My Frame*/
    $(document).on("click", ".updateMyFrame", function() {
        var type = 'UpdateMyFrame';
        var frameID = $(this).attr("data-id");
        var data = 'f=' + type + '&frameID=' + frameID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".credit_plan_box").css("pointer-events", "none");
                $(".credit_plan_box_" + frameID).append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if(response == '400'){
                    PopUPAlerts('sWrong', 'ialert');
                }else{
                    PopUPAlerts('frameSuccess', 'ialert');
                }

                setTimeout(() => {
                   $(".loaderWrapper").remove();
                   $(".credit_plan_box").css("pointer-events", "auto");
                }, 3000);

            }
        });
    });
    /*Update My Frame*/
    $(document).on("click", ".inv_btn", function() {
        var type = 'inviteFriend';
        var iemail = $("#inv_email").val();
        var data = 'f=' + type + '&invEmail=' + encodeURIComponent(iemail);
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/inviteEmail.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".inv_btn").css("pointer-events", "none");
                $(".already_in_use").hide();
                $(".inviteemail").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if(response == '404'){
                    PopUPAlerts('sWrong', 'ialert');
                }else if(response == '1'){
                    $(".already_in_use").show();
                }else{
                    PopUPAlerts('emailSendsuccess', 'ialert');
                }
                $(".inviteemail_input").val('');
                setTimeout(() => {
                   $(".inv_btn").css("pointer-events", "auto");
                   $(".already_in_use").hide();
                }, 3000);
                $(".loaderWrapper").remove();
            }
        });
    });
	    $(document).on("click", ".stat_icon", function() {
	        $(this).hide();
	        $(this).next(".stat_icona").show();
	        var ID = $(this).attr("id");
	        $(".bstatistick_"+ID).addClass("changeHeight");
	        if (typeof window.initBoostCharts === "function") {
	            setTimeout(function () {
	                window.initBoostCharts(ID);
	            }, 50);
	        }
	    });

	    $(document).on("click", ".stat_icona", function() {
	        $(this).hide();
	        $(this).prev(".stat_icon").show();
	        var ID = $(this).attr("id");
	        $(".bstatistick_"+ID).removeClass("changeHeight");
	    });

	    window.loadBoostChartJsOnce = function () {
	        if (window.__boostChartJsLoading) {
	            return window.__boostChartJsLoading;
	        }
	        window.__boostChartJsLoading = new Promise(function (resolve, reject) {
	            if (typeof Chart !== "undefined") {
	                resolve();
	                return;
	            }
	            var script = document.createElement("script");
	            script.src = siteurl + "admin/default/js/chartJs/chart.js";
	            script.async = true;
	            script.onload = function () {
	                resolve();
	            };
	            script.onerror = function () {
	                reject(new Error("Chart.js load failed"));
	            };
	            document.head.appendChild(script);
	        });
	        return window.__boostChartJsLoading;
	    };

	    window.initBoostCharts = function (boostID) {
	        try {
	            if (!boostID) {
	                return;
	            }
	            var wrapper = document.getElementById("boost_charts_" + boostID);
	            if (!wrapper) {
	                return;
	            }
	            if (wrapper.getAttribute("data-chart-init") === "1") {
	        return;
	    }

	    var viewsTotal = parseInt(wrapper.getAttribute("data-views-total") || "0", 10) || 0;
        var domDaysLeft = parseInt(wrapper.getAttribute("data-days-left") || "0", 10) || 0;
        var domDaysTotal = parseInt(wrapper.getAttribute("data-days-total") || "0", 10) || 0;

        var formatDateYMD = function(dateObj) {
            var y = dateObj.getFullYear();
            var m = (dateObj.getMonth() + 1);
            var d = dateObj.getDate();
            var mm = (m < 10 ? "0" + m : "" + m);
            var dd = (d < 10 ? "0" + d : "" + d);
            return y + "-" + mm + "-" + dd;
        };

	            window.loadBoostChartJsOnce().then(function () {
	                if (typeof Chart === "undefined") {
	                    return;
	                }
	                window.boostCharts = window.boostCharts || {};
	                if (window.boostCharts[boostID]) {
	                    wrapper.setAttribute("data-chart-init", "1");
	                    return;
	                }

	                var csrfToken = $('meta[name=csrf-token]').attr('content') || $("#poll_csrf_token").val();
	                var data = "f=boostChartData&bid=" + encodeURIComponent(boostID);
	                if (csrfToken) {
	                    data += "&csrf_token=" + encodeURIComponent(csrfToken);
	                }

	                $.ajax({
	                    type: "POST",
	                    url: siteurl + "requests/request.php",
	                    data: data,
	                    cache: false,
	                    success: function (response) {
	                        try {
	                            if ((response || '').toString().toLowerCase().indexOf('csrf') !== -1) {
	                                window.location.reload();
	                                return;
	                            }
	                            if (typeof response === "string") {
	                                response = JSON.parse(response);
	                            }
	                            if (!response || response.status !== 200 || !response.data) {
	                                return;
	                            }
	                            var labels = response.data.labels || [];
	                            var cumulative = response.data.cumulative || [];
                            var daily = response.data.daily || [];
	                            var totalSeen = parseInt(response.data.total_seen || 0, 10) || 0;
	                            // Fallback to DOM (server-rendered) count if API returns 0
	                            var wrapperEl = document.getElementById("boost_charts_" + boostID);
	                            var domSeen = wrapperEl ? parseInt(wrapperEl.getAttribute("data-views-seen") || "0", 10) || 0 : 0;
	                            if (totalSeen <= 0 && domSeen > 0) {
	                                totalSeen = domSeen;
	                            }
	                            var total = parseInt(response.data.view_total || viewsTotal || 0, 10) || 0;
                                var maxShownVal = 0;
                                for (var ms = 0; ms < cumulative.length; ms++) {
                                    var cVal = parseInt(cumulative[ms] || 0, 10) || 0;
                                    if (cVal > maxShownVal) {
                                        maxShownVal = cVal;
                                    }
                                }
	                            // Normalize cumulative: ensure array length matches labels
	                            if (!Array.isArray(cumulative)) {
	                                cumulative = [];
	                            }
                            if (!Array.isArray(daily)) {
                                daily = [];
                            }
	                            if (cumulative.length !== labels.length) {
	                                var tmp = [];
	                                for (var j = 0; j < labels.length; j++) {
	                                    tmp.push(parseInt(cumulative[j] || 0, 10) || 0);
	                                }
	                                cumulative = tmp;
	                            }
                            if (daily.length !== labels.length) {
                                var dtmp = [];
                                for (var jd = 0; jd < labels.length; jd++) {
                                    dtmp.push(parseInt(daily[jd] || 0, 10) || 0);
                                }
                                daily = dtmp;
                            }
	                            // If we have no labels, create a single point with totalSeen
	                            if (labels.length === 0) {
	                                labels = ["Total"];
	                                cumulative = [totalSeen];
                                daily = [totalSeen];
	                            } else if (cumulative.length === 0) {
	                                // Create zeroed series aligned to labels
	                                cumulative = labels.map(function () { return 0; });
                                daily = labels.map(function () { return 0; });
	                            }
	                            // If totalSeen > last cumulative point, lift the last point so tooltip shows correct seen count
	                            if (cumulative.length > 0 && totalSeen > 0) {
	                                var lastIdx = cumulative.length - 1;
	                                var lastVal = parseInt(cumulative[lastIdx] || 0, 10) || 0;
	                                if (totalSeen > lastVal) {
	                                    cumulative[lastIdx] = totalSeen;
	                                }
	                            }

                            // If we have total duration info, rebuild labels to cover full boost period
                            if (domDaysTotal > 0 && labels.length > 0) {
                                var startDate = new Date(labels[0]);
                                if (!isNaN(startDate.getTime())) {
                                    var valueByDate = {};
                                var dailyByDate = {};
                                    for (var m = 0; m < labels.length; m++) {
                                        var key = labels[m];
                                        valueByDate[key] = parseInt(cumulative[m] || 0, 10) || 0;
                                    dailyByDate[key] = parseInt(daily[m] || 0, 10) || 0;
                                    }
                                    var rebuiltLabels = [];
                                    var rebuiltCumulative = [];
                                var rebuiltDaily = [];
                                    for (var di = 0; di < domDaysTotal; di++) {
                                        var dt = new Date(startDate);
                                        dt.setDate(startDate.getDate() + di);
                                        var lbl = formatDateYMD(dt);
                                        rebuiltLabels.push(lbl);
                                        if (valueByDate.hasOwnProperty(lbl)) {
                                            rebuiltCumulative.push(valueByDate[lbl]);
                                        } else {
                                            // carry last known cumulative so line stays flat if no views
                                            var prevVal = rebuiltCumulative.length ? rebuiltCumulative[rebuiltCumulative.length - 1] : 0;
                                            rebuiltCumulative.push(prevVal);
                                        }
                                    if (dailyByDate.hasOwnProperty(lbl)) {
                                        rebuiltDaily.push(dailyByDate[lbl]);
                                    } else {
                                        rebuiltDaily.push(0);
                                    }
                                    }
                                    labels = rebuiltLabels;
                                    cumulative = rebuiltCumulative;
                                daily = rebuiltDaily;
                                }
                            }

                            // recompute max based on daily values
                            maxShownVal = 0;
                            for (var ms2 = 0; ms2 < daily.length; ms2++) {
                                var cVal2 = parseInt(daily[ms2] || 0, 10) || 0;
                                if (cVal2 > maxShownVal) {
                                    maxShownVal = cVal2;
                                }
                            }

                            var lineCanvas = document.getElementById("boost_line_chart_" + boostID);
                            if (!lineCanvas || !lineCanvas.getContext) {
                                return;
                            }
	                            var isDarkTheme = !!document.querySelector('link[href*="night_style.css"]');
	                            var primary = "#f65169";
	                            var primaryBg = "rgba(246,81,105,0.12)";
	                            var gridColor = isDarkTheme ? "rgba(255,255,255,0.08)" : "rgba(15,23,42,0.06)";
	                            var tickColor = isDarkTheme ? "rgba(255,255,255,0.75)" : "rgba(15,23,42,0.55)";
	                            var targetColor = isDarkTheme ? "rgba(255,255,255,0.18)" : "rgba(224,227,235,0.90)";
	                            var tooltipBg = isDarkTheme ? "rgba(0,0,0,0.9)" : "rgba(15,23,42,0.95)";
	                            var tooltipText = "rgba(255,255,255,0.95)";

	                            var ctx = lineCanvas.getContext("2d");
	                            var gradient = null;
	                            try {
	                                gradient = ctx.createLinearGradient(0, 0, 0, (lineCanvas.height || 180));
	                                gradient.addColorStop(0, "rgba(246,81,105,0.26)");
	                                gradient.addColorStop(1, "rgba(246,81,105,0)");
	                            } catch (e) {
	                                gradient = primaryBg;
	                            }
	                            var datasets = [{
	                                data: daily,
	                                label: "Shown (daily)",
	                                borderColor: primary,
	                                backgroundColor: gradient,
	                                borderWidth: 3,
	                                pointRadius: 0,
	                                pointHoverRadius: 4,
	                                pointHitRadius: 10,
	                                fill: true,
	                                lineTension: 0.25
	                            }];

                                var suggestedMax = maxShownVal > 0 ? (maxShownVal + 2) : (totalSeen > 0 ? totalSeen + 2 : 5);
	                            window.boostCharts[boostID] = new Chart(lineCanvas.getContext("2d"), {
	                                type: "line",
	                                data: {
	                                    labels: labels,
	                                    datasets: datasets
	                                },
	                                options: {
	                                    responsive: true,
	                                    maintainAspectRatio: false,
	                                    legend: { display: false },
	                                    elements: {
	                                        point: { radius: 0 }
	                                    },
	                                    hover: {
	                                        mode: "index",
	                                        intersect: false
	                                    },
	                                    scales: {
	                                        xAxes: [{
	                                            display: true,
	                                            gridLines: {
                                                    display: true,
                                                    color: gridColor,
                                                    zeroLineColor: gridColor,
                                                    drawBorder: false
                                                },
	                                            ticks: {
	                                                autoSkip: false,
	                                                maxTicksLimit: (domDaysTotal > 0 ? domDaysTotal : (labels.length || 6)),
	                                                fontColor: tickColor,
	                                                callback: function (value) {
	                                                    if (typeof value === "string" && value.length >= 10) {
	                                                        return value.substr(8, 2);
	                                                    }
	                                                    return value;
	                                                }
	                                            }
	                                        }],
	                                        yAxes: [{
	                                            display: true,
	                                            gridLines: {
	                                                display: true,
	                                                color: gridColor,
	                                                zeroLineColor: gridColor,
	                                                drawBorder: false
	                                            },
	                                            ticks: {
	                                                beginAtZero: true,
	                                                precision: 0,
	                                                fontColor: tickColor,
                                                    display: false,
                                                    suggestedMax: suggestedMax
	                                            }
	                                        }]
	                                    },
	                                    tooltips: {
	                                        enabled: true,
	                                        backgroundColor: tooltipBg,
	                                        titleFontSize: 12,
                                        bodyFontColor: tooltipText,
	                                        titleFontColor: tooltipText,
	                                        displayColors: false,
	                                        xPadding: 10,
	                                        yPadding: 8,
	                                        cornerRadius: 10,
	                                        callbacks: {
	                                            title: function (tooltipItems, data) {
	                                                if (!tooltipItems || !tooltipItems.length) {
	                                                    return "";
	                                                }
	                                                var idx = tooltipItems[0].index;
	                                                return (data.labels && data.labels[idx]) ? data.labels[idx] : "";
	                                            },
	                                            label: function (tooltipItem, data) {
	                                                var ds = (data.datasets && data.datasets[tooltipItem.datasetIndex]) || {};
	                                                var prefix = ds.label ? ds.label + ": " : "";
	                                                var val = tooltipItem.yLabel;
	                                                // If Chart.js gives falsy/zero but we have totalSeen, use it for the last point
	                                                if (!val && totalSeen > 0) {
	                                                    val = totalSeen;
	                                                }
                                                    var remainingDays = "";
                                                    if (domDaysLeft > 0) {
                                                        var daysLeftPerPoint = domDaysLeft - tooltipItem.index;
                                                        if (daysLeftPerPoint < 0) { daysLeftPerPoint = 0; }
                                                        remainingDays = " (Days left: " + daysLeftPerPoint + ")";
                                                    }
	                                                return prefix + val + remainingDays;
	                                            }
	                                        }
	                                    }
	                                }
	                            });
	                            wrapper.setAttribute("data-chart-init", "1");
	                        } catch (e) {
	                            // ignore
	                        }
	                    }
	                });
	            }).catch(function () {
	                // Fail silently (charts optional)
	            });
	        } catch (e) {
	            // Fail silently (charts optional)
	        }
	    };

    $(document).on("click", ".move_my_point", function () {
      const type = "moveMyAffilateBalance";
      const point = window.affiliateConfig?.earnings || 0;
      const data = "f=" + type + "&myp=" + point;

      $.ajax({
        type: "POST",
        url: siteurl + "requests/request.php",
        data: data,
        cache: false,
        beforeSend: function () {
          $(".move_my_point").hide().css("pointer-events", "none");
        },
        success: function (response) {
          if (response === "me") {
            PopUPAlerts("sRong", "ialert");
          } else {
            location.reload();
          }

          setTimeout(() => {
            $(".move_my_point").show().css("pointer-events", "auto");
          }, 2000);
        }
      });
    });
    $(document).on("click",".bCountry", function(){
        var ID = $(this).attr("data-c");
        var i = $(this).attr("id");
        var type = 'bCountry';
        var data =  'f='+type+'&c='+ID;
        $.ajax({
          type: 'POST',
          url: siteurl + "requests/request.php",
          data: data,
          cache: false,
          success: function(response) {
             if(response == '1'){
               $("#"+i).addClass('chsed');
             }else{
               $("#"+i).removeClass('chsed');
             }
          }
        });
      });
      $(document).on("click", ".move_my_point_earn", function () {
      const data = {
        f: "moveMyEarnedPoints",
        myp: window.earnedPointData?.allTotal || 0
      };

      const $button = $(".move_my_point");
      const $alertBox = $(window.earnedPointData.alertSuccessSelector);

      $.ajax({
        type: "POST",
        url: siteurl + "requests/request.php",
        data: data,
        cache: false,
        beforeSend: function () {
          $button.hide().css("pointer-events", "none");
          $alertBox.text("").hide();
        },
        success: function (response) {
          if (response === "me") {
            PopUPAlerts("sRong", "ialert");
          } else if (response === "ok") {
            location.reload();
          } else {
            $alertBox.text(response).show();
          }

          setTimeout(() => {
            $button.show().css("pointer-events", "auto");
          }, 2000);
        }
      });
    });
    // Toggle advanced settings (question and slots)
    $(document).on("change", ".subfeea", function (e) {
      const type = $(this).data("id");

      // Only handle for product edit/create toggles
      if (type === "askaquestion" || type === "limitslots") {
        const checked = $(this).prop("checked");
        const $target = $("." + type);

        // Keep hidden value in 'ok'/'not' form expected by backend
        $("#" + type).val(checked ? "ok" : "not");

        if (checked) {
          // Make visible: remove nonePoint and restore stylesheet display (flex)
          $target.removeClass("nonePoint").css("display", "");
        } else {
          // Hide: add nonePoint and hide
          $target.addClass("nonePoint").hide();
        }

        // Prevent other generic .subfeea handlers from overriding our values
        e.stopImmediatePropagation();
      }
    });

    // Save product edit
    $(document).on("click", ".pr_save_btna", function () {
      // Only handle clicks when editProduct context exists (edit page)
      if (!window.editProduct || !window.editProduct.productID) {
        return; // Not on edit page; let other handlers (create pages) run
      }

      const data = {
        f: "saveEditPr",
        prid: window.editProduct.productID,
        prnm: $("#pr_name").val(),
        prprc: $("#pr_price").val(),
        prdsc: $("#pr_description").val(),
        prdscinf: $("#pr_conf").val(),
        lmSlot: $("#limitslots").prop("checked") ? "ok" : "not",
        askQ: $("#askaquestion").prop("checked") ? "ok" : "not",
        qAsk: $("#question_ask").val(),
        lSlot: $("#limit_slot").val()
      };

      $(window.editProduct.warningTextSelector).hide();
      $(window.editProduct.successTextSelector).hide();

      $.ajax({
        type: "POST",
        url: siteurl + "requests/request.php",
        data: data,
        cache: false,
        success: function (response) {
          if (response === "200") {
            $(window.editProduct.successTextSelector).show();
          } else {
            $(window.editProduct.warningTextSelector).show();
          }
        }
      });
    });
    $(document).ready(function () {
        let debounceTimer;

        $(document).on("keyup", "#newEmail", function () {
          clearTimeout(debounceTimer);
          const email = $(this).val();

          if (email.length < 3) return;

          debounceTimer = setTimeout(() => {
            const data = {
              f: "checkemail",
              newEmail: email
            };

            $(".warning_inuse, .warning_invalid").hide();

            $.ajax({
              type: "POST",
              url: siteurl + "requests/request.php",
              data: data,
              cache: false,
              success: function (response) {
                if (response === "404") {
                  $(".warning_invalid").hide();
                  $(".warning_inuse").show();
                } else if (response === "no") {
                  $(".warning_inuse").hide();
                  $(".warning_invalid").show();
                } else {
                  $(".warning_inuse, .warning_invalid").hide();
                }
              }
            });
          }, 500);
    });
    $(document).on("keyup", ".aval", function () {
      const val = parseFloat($(this).val());
      const ID = $(this).attr("id");

      $(".i_t_warning").hide();

      if (ID === "spweek" && val < parseFloat(window.subscriptionConfig.minWeekAmount)) {
        $("#waweekly").show();
      } else if (ID === "spmonth" && val < parseFloat(window.subscriptionConfig.minMonthAmount)) {
        $("#mamonthly").show();
      } else if (ID === "spyear" && val < parseFloat(window.subscriptionConfig.minYearAmount)) {
        $("#yayearly").show();
      }
    });

    $(document).on("change", ".subfeea", function () {
      if ($(this).hasClass("subpointfee")) {
        return;
      }
      const newValue = $(this).is(":checked") ? "1" : "0";
      $(this).val(newValue);
    });

    $(document).on("click", ".c_uNext", function () {
      const loading = '<div class="i_loading product_page_loading"><div class="dot-pulse"></div></div>';
      const loader = '<div class="loaderWrapper"><div class="loaderContainer"><div class="loader">' + loading + '</div></div></div>';

      const data = {
        f: "updateSubscriptionPayments",
        wSubWeekAmount: window.subscriptionConfig.subWeekStatus === "yes" ? $("#spweek").val() : "",
        mSubMonthAmount: window.subscriptionConfig.subMonthlyStatus === "yes" ? $("#spmonth").val() : "",
        mSubYearAmount: window.subscriptionConfig.subYearlyStatus === "yes" ? $("#spyear").val() : "",
        wStatus: $('input[name="weekly"]').val() || "0",
        mStatus: $('input[name="monthly"]').val() || "0",
        yStatus: $('input[name="yearly"]').val() || "0"
      };

      // Validation
      if (data.wStatus === "1" && data.wSubWeekAmount === "") {
        $("#wweekly").show();
        return;
      }
      if (data.mStatus === "1" && data.mSubMonthAmount === "") {
        $("#wmonthly").show();
        return;
      }
      if (data.yStatus === "1" && data.mSubYearAmount === "") {
        $("#wyearly").show();
        return;
      }

      $(".i_nex_btn").css("pointer-events", "none");
      $("#wweekly, #wmonthly, #wyearly, .weekly_success, .monthly_success, .yearly_success").hide();
      $(".i_become_creator_box_footer").append(loader);

          $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: data,
            dataType: "json",
            cache: false,
            success: function (res) {
              $(".loaderWrapper").remove();

              if (res.weekly === "404") $("#wweekly").show();
              if (res.weekly === "200") $(".weekly_success").show();

              if (res.monthly === "404") $("#wmonthly").show();
              if (res.monthly === "200") $(".monthly_success").show();

              if (res.yearly === "404") $("#wyearly").show();
              if (res.yearly === "200") $(".yearly_success").show();

              $(".i_nex_btn").css("pointer-events", "auto");
            }
          });
        });
    });
    /*Change Module Statuses*/
    $(document).on("change", ".chmdProd", function() {
        var type = 'productStatus';
        var value = $(this).val();
        var prID = $(this).attr("data-id");
        var data = 'f=' + type + '&mod=' + value + '&id='+prID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: data,
            beforeSend: function() {
                $("#pr_s_"+prID).append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    if (value == '1') {
                        $("#pr_i_"+prID).val('0');
                    } else {
                        $("#pr_i_"+prID).val('1');
                    }
                } else if (response == '404') {
                    $(".warning_").show();
                }
                $(".loaderWrapper").remove();

            }
        });
    });
    /*Follow Profile PopUp Call*/
    $(document).on("click", ".delprod", function() {
        var type = 'delete_product';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
    /*Follow Profile PopUp Call*/
    $(document).on("click", ".del_stor", function() {
        var type = 'delete_storie_alert';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
    /*Credit Card Form Call*/
    $(document).on("click", ".stViewers", function() {
        var type = 'storieViewers';
        var ID = $(this).attr("data-viewer");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            beforeSend: function() {

            },
            success: function(response) {
                $("body").append(response);
                setTimeout(() => {
                    $(".i_modal_bg_in").addClass('i_modal_display_in');
                }, 200);
            }
        });
    });
    $(document).on("keyup", ".avalv", function() {
        var inputVal = $(this).val();
        $(".i_t_warning").hide();

        if (inputVal === "0" || inputVal === "" || inputVal === "undefined") {
          $(".i_t_warning").show();
        }

        var calculatedValue = parseFloat(inputVal) * parseFloat(pointEqualValue);
        if (!isNaN(calculatedValue)) {
          var formatted = (typeof window.dizzyFormatCurrency === "function") ? window.dizzyFormatCurrency(calculatedValue) : calculatedValue.toFixed(2);
          $(".pricecal").text(formatted);
        }
      });

    $(document).on("click", ".c_UpdateCostV", function() {
        var videoCost = $(".avalv").val();
        var data = "f=vCost&vCostFee=" + encodeURIComponent(videoCost);

        $.ajax({
          type: "POST",
          url: siteurl + "requests/request.php",
          data: data,
          beforeSend: function() {
            $(".i_t_warning, .successNot").hide();
          },
          success: function(response) {
            if (response === "not") {
              $(".i_t_warning").show();
            } else {
              $(".successNot").show();
            }
          }
        });
    });
    $(document).on("click", ".payClose", function() {
        $(".i_payment_pop_box").addClass("i_modal_in_in_out");
        setTimeout(() => {
            $(".i_subs_modal").remove();
        }, 200);
    });

    $(document).on("change", "#i_reels_video", function (e) {
        e.preventDefault();
        var values = $("#uploadVal").val();
        var id = $("#i_reels_video").attr("data-id");
        var data = { f: id };

        $('.i_uploaded_iv').append('<div class="i_upload_progress"></div>');

        $("#uploadReelsform").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function () {
                $(".i_warning_unsupported").hide();
                $(".i_uploaded_iv").show();
                $(".i_upload_progress").width('0%');
                $(".publish").prop("disabled", true);
                $(".publish").css("pointer-events", "none");
            },
            uploadProgress: function (e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');

                if (percentageComplete >= 100) {
                    $('#upload-error-msg').hide();
                    $('.i_upload_progress').addClass('processing-animation');
                    $(".i_uploaded_iv").append('<div class="processing-msg"></div>');
                }
            },
            success: function (response) {
                $(".processing-msg").remove();
                $(".i_upload_progress").removeClass('processing-animation');
                try {
                    const json = typeof response === 'object' ? response : JSON.parse(response);

                    if (json.status === 'success') {
                        const fileId = typeof json.file_id === 'object' ? json.file_id.upload_id : json.file_id;
                        const normalized = (siteurl || '').replace(/\/+$/,'');
                        const reelUrl = normalized + "/createReels?r=" + encodeURIComponent(fileId);
                        window.location.href = reelUrl;
                    } else {
                        const message = json.message || 'Unknown error occurred.';
                        $(".i_upload_warning").text(message).fadeIn();
                        $(".i_upload_warning").show();
                    }
                } catch (e) {
                    console.error("JSON parse error:", e);
                    $(".i_upload_warning").text("Invalid server response. Please try again later.").fadeIn();
                    $(".i_upload_warning").show();
                }
            },
            error: function (xhr, status, error) { handleUploadError(xhr, status, error); }
        }).submit();
    });
})(jQuery);

$(function() {
    var search = window.location.search || '';
    if (search.indexOf('subscribe=1') !== -1 && $('.uSubsModal').length) {
        setTimeout(function() {
            $('.uSubsModal').first().trigger('click');
        }, 300);
    }
});

// Connections visibility guard for profile links
function showConnectionsAlert(alertKey) {
    if (!alertKey) { return; }
    if (document.querySelector('.i_bottom_left_alert_container')) { return; }

    if (typeof PopUPAlerts === 'function') {
        PopUPAlerts(alertKey, 'ialert');
        return;
    }
    if (typeof $ === 'function' && typeof siteurl !== 'undefined') {
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: { f: 'ialert', al: alertKey },
            cache: false,
            success: function (response) {
                if (response) {
                    $('body').append(response);
                    setTimeout(function () {
                        $('.i_bottom_left_alert_container').addClass('fadeOutDown');
                    }, 5000);
                    setTimeout(function () {
                        $('.i_bottom_left_alert_container').remove();
                    }, 5000);
                }
            }
        });
    }
}

document.addEventListener('click', function (e) {
    var prw = document.getElementById('prw');
    if (!prw) { return; }
    var isOwner = prw.dataset.owner === '1';
    var visibility = prw.dataset.connectionsVisibility || '1';
    if (isOwner || visibility === '1') {
        return;
    }

    var anchor = e.target.closest('a');
    if (!anchor) { return; }
    var href = anchor.getAttribute('href') || '';
    var lower = href.toLowerCase();
    var alertKey = '';
    if (lower.indexOf('pcat=followers') !== -1) {
        alertKey = 'connections_followers_hidden';
    } else if (lower.indexOf('pcat=following') !== -1) {
        alertKey = 'connections_following_hidden';
    } else if (lower.indexOf('pcat=subscribers') !== -1) {
        alertKey = 'connections_subscribers_hidden';
    }
    if (alertKey) {
        e.preventDefault();
        showConnectionsAlert(alertKey);
    }
});
