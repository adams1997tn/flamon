(function ($) {
    "use strict";

    const siteurl = window.siteurl || "";
    const preLoadingAnimation = '<div class="i_loading product_page_loading"><div class="dot-pulse"></div></div>';
    const loaderHTML = '<div class="loaderWrapper"><div class="loaderContainer"><div class="loader">' + preLoadingAnimation + '</div></div></div>';

    function decodeBase64Unicode(value) {
        if (!value) {
            return "";
        }
        try {
            return decodeURIComponent(atob(value).split("").map(function (c) {
                return "%" + ("00" + c.charCodeAt(0).toString(16)).slice(-2);
            }).join(""));
        } catch (err) {
            try {
                return atob(value);
            } catch (err2) {
                return "";
            }
        }
    }

    function readPayConfig() {
        const modal = document.querySelector(".community_pay_modal[data-pay-config]");
        if (!modal) {
            return null;
        }
        const encoded = modal.getAttribute("data-pay-config");
        if (!encoded) {
            return null;
        }
        const decoded = decodeBase64Unicode(encoded);
        if (!decoded) {
            return null;
        }
        try {
            return JSON.parse(decoded);
        } catch (err) {
            return null;
        }
    }

    function appendModal(html) {
        if (!html) {
            return;
        }
        $("body").append(html);
        initializeCommunityForms(document);
        setTimeout(() => {
            $(".i_modal_bg_in").addClass("i_modal_display_in");
        }, 200);
    }

    function getCommunityAccessType($form) {
        const $selected = $form.find('input[name="community_access_type"]:checked');
        if ($selected.length) {
            return String($selected.val() || "");
        }
        const $hidden = $form.find('input[name="community_access_type"][type="hidden"]');
        if ($hidden.length) {
            return String($hidden.val() || "");
        }
        return "paid";
    }

    function applyMemberLimitState($form, isFree, freeLimit) {
        const $select = $form.find('select[name="community_member_limit"]');
        if (!$select.length) {
            return;
        }
        if (isFree && freeLimit > 0) {
            if ($select.data("previousValue") === undefined) {
                $select.data("previousValue", $select.val());
            }
            let hasLimitOption = false;
            $select.find("option").each(function () {
                if (String($(this).val()) === String(freeLimit)) {
                    hasLimitOption = true;
                }
            });
            if (!hasLimitOption) {
                $select.append('<option value="' + freeLimit + '" data-free-option="1">' + freeLimit + "</option>");
            }
            $select.find("option").each(function () {
                const value = $(this).val();
                if (value === "") {
                    $(this).prop("disabled", true);
                    return;
                }
                const parsed = parseInt(value, 10);
                if (!isNaN(parsed) && parsed > freeLimit) {
                    $(this).prop("disabled", true);
                } else {
                    $(this).prop("disabled", false);
                }
            });
            const currentVal = $select.val();
            const currentNum = parseInt(currentVal, 10);
            if (!currentVal || isNaN(currentNum) || currentNum > freeLimit) {
                $select.val(String(freeLimit));
            }
            return;
        }
        $select.find("option").prop("disabled", false);
        $select.find("option[data-free-option='1']").remove();
        const previousValue = $select.data("previousValue");
        if (previousValue !== undefined && previousValue !== null && previousValue !== "") {
            $select.val(previousValue);
        }
    }

    function setCommunityAccessState($form) {
        if (!$form || !$form.length) {
            return;
        }
        const accessType = getCommunityAccessType($form);
        const freeLimit = parseInt($form.data("free-limit"), 10);
        const isFree = accessType === "free" || accessType === "0";
        const $priceWrap = $form.find(".community_price_group_wrap");
        const $priceInput = $form.find('input[name="community_monthly_price"]');
        const $freeNote = $form.find("[data-free-limit-note]");

        if (isFree) {
            if ($priceWrap.length) {
                $priceWrap.addClass("is-hidden");
            }
            if ($priceInput.length) {
                const currentValue = $priceInput.val();
                if (currentValue && currentValue !== "0") {
                    $priceInput.data("paidValue", currentValue);
                }
                $priceInput.val("0");
                $priceInput.prop("disabled", true).removeAttr("required");
            }
            if ($freeNote.length) {
                $freeNote.removeClass("is-hidden");
            }
        } else {
            if ($priceWrap.length) {
                $priceWrap.removeClass("is-hidden");
            }
            if ($priceInput.length) {
                const paidValue = $priceInput.data("paidValue");
                if ((!$priceInput.val() || $priceInput.val() === "0") && paidValue) {
                    $priceInput.val(paidValue);
                }
                $priceInput.prop("disabled", false).attr("required", true);
            }
            if ($freeNote.length) {
                $freeNote.addClass("is-hidden");
            }
        }
        applyMemberLimitState($form, isFree, isNaN(freeLimit) ? 0 : freeLimit);
    }

    function initializeCommunityForms(context) {
        const $scope = context ? $(context) : $(document);
        $scope.find(".community_form").each(function () {
            setCommunityAccessState($(this));
        });
    }

    function getCommunityCsrfToken() {
        const tokenInput = document.querySelector(".community_csrf_token");
        return tokenInput ? tokenInput.value : "";
    }

    function parseJsonResponse(response) {
        if (response && typeof response === "object") {
            return response;
        }
        if (typeof response !== "string") {
            return null;
        }
        const raw = response.trim();
        if (!raw) {
            return null;
        }
        if ((raw.charAt(0) === "{" && raw.charAt(raw.length - 1) === "}") || (raw.charAt(0) === "[" && raw.charAt(raw.length - 1) === "]")) {
            try {
                return JSON.parse(raw);
            } catch (err) {
                return null;
            }
        }
        return null;
    }

    function buildSubscriptionSuccessUrl(orderId) {
        const root = siteurl.slice(-1) === "/" ? siteurl : siteurl + "/";
        const params = new URLSearchParams();
        params.set("payment_type", "subscription");
        if (orderId) {
            params.set("order_id", orderId);
        }
        return root + "payment-success?" + params.toString();
    }

    $(document).on("click", ".community_enter_btn", function (e) {
        const href = this.getAttribute("href");
        if (href !== "#community-content") {
            return;
        }
        const target = document.getElementById("community-content");
        if (!target) {
            return;
        }
        e.preventDefault();
        e.stopImmediatePropagation();
        target.scrollIntoView({ behavior: "smooth", block: "start" });
    });

    $(document).on("click", ".communityCreateModal", function (e) {
        e.preventDefault();
        $(".community_modal").remove();
        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: { f: "communityCreateModal" },
            cache: false,
            success: function (response) {
                appendModal(response);
            }
        });
    });

    $(document).on("submit", "#communityCreateForm", function (e) {
        e.preventDefault();
        const $form = $(this);
        const $message = $form.find(".community_form_message");
        const $submit = $form.find(".communityCreateSubmit");
        const $priceInput = $form.find('input[name="community_monthly_price"]');
        const priceWasDisabled = $priceInput.length && $priceInput.prop("disabled");
        if (priceWasDisabled) {
            $priceInput.prop("disabled", false);
        }
        const formData = new FormData(this);
        if (priceWasDisabled) {
            $priceInput.prop("disabled", true);
        }

        $message.removeClass("is-error is-success").text("");
        $submit.prop("disabled", true);
        $form.append(loaderHTML);

        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function (response) {
                $(".loaderWrapper").remove();
                if (response && response.status === "success") {
                    if (response.redirect) {
                        window.location.href = response.redirect;
                        return;
                    }
                    location.reload();
                    return;
                }
                if (response && response.message) {
                    $message.addClass("is-error").text(response.message);
                } else if (typeof PopUPAlerts === "function") {
                    PopUPAlerts("sWrong", "ialert");
                }
                $submit.prop("disabled", false);
            },
            error: function () {
                $(".loaderWrapper").remove();
                if (typeof PopUPAlerts === "function") {
                    PopUPAlerts("sWrong", "ialert");
                }
                $submit.prop("disabled", false);
            }
        });
    });

    $(document).on("submit", "#communityManageForm", function (e) {
        e.preventDefault();
        const $form = $(this);
        const $message = $form.find(".community_form_message");
        const $submit = $form.find(".communityManageSubmit");
        const $priceInput = $form.find('input[name="community_monthly_price"]');
        const priceWasDisabled = $priceInput.length && $priceInput.prop("disabled");
        if (priceWasDisabled) {
            $priceInput.prop("disabled", false);
        }
        const formData = new FormData(this);
        if (priceWasDisabled) {
            $priceInput.prop("disabled", true);
        }

        $message.removeClass("is-error is-success").text("");
        $submit.prop("disabled", true);
        $form.append(loaderHTML);

        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function (response) {
                $(".loaderWrapper").remove();
                if (response && response.status === "success") {
                    $message.addClass("is-success").text(response.message || "");
                    setTimeout(() => {
                        location.reload();
                    }, 800);
                    return;
                }
                if (response && response.message) {
                    $message.addClass("is-error").text(response.message);
                } else if (typeof PopUPAlerts === "function") {
                    PopUPAlerts("sWrong", "ialert");
                }
                $submit.prop("disabled", false);
            },
            error: function () {
                $(".loaderWrapper").remove();
                if (typeof PopUPAlerts === "function") {
                    PopUPAlerts("sWrong", "ialert");
                }
                $submit.prop("disabled", false);
            }
        });
    });

    $(document).on("click", ".editCommunityAvatarCover", function (e) {
        e.preventDefault();
        const communityId = $(this).data("community");
        if (!communityId) {
            return;
        }
        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: { f: "communityUpdateAvatarCover", community_id: communityId },
            cache: false,
            success: function (response) {
                appendModal(response);
            }
        });
    });

    $(document).on("click", ".communityEditModalBtn", function (e) {
        e.preventDefault();
        const communityId = $(this).data("community");
        if (!communityId) {
            return;
        }
        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: { f: "communityEditModal", community_id: communityId },
            cache: false,
            success: function (response) {
                appendModal(response);
            }
        });
    });

    $(document).on("change", 'input[name="community_access_type"]', function () {
        const $form = $(this).closest("form");
        setCommunityAccessState($form);
    });

    let moderationSearchTimer = null;
    let moderationSearchXhr = null;
    let moderatorSearchTimer = null;
    let moderatorSearchXhr = null;
    let moderatorSelectedUsers = new Set();

    const toggleModeratorOptionsModal = function ($modal, show) {
        const $overlay = $modal.find(".community_moderator_options_modal");
        if ($overlay.length === 0) {
            return;
        }
        if (show) {
            $overlay.addClass("is-visible").attr("aria-hidden", "false");
        } else {
            $overlay.removeClass("is-visible").attr("aria-hidden", "true");
        }
    };

    const updateModeratorSelectionInput = function ($context) {
        const $modal = $context.closest(".community_moderator_modal");
        if ($modal.length === 0) {
            return;
        }
        const $form = $modal.find(".community_moderator_form");
        if ($form.length === 0) {
            return;
        }
        const ids = Array.from(moderatorSelectedUsers);
        $form.find('input[name="moderator_user_ids"]').val(ids.join(","));
    };

    const syncModeratorSelections = function ($container) {
        $container.find(".community_moderator_user_card").each(function () {
            const $card = $(this);
            const userId = parseInt($card.data("user"), 10);
            if (!userId) {
                return;
            }
            if ($card.hasClass("is-added")) {
                $card.removeClass("is-selected");
                moderatorSelectedUsers.delete(userId);
                return;
            }
            if (moderatorSelectedUsers.has(userId)) {
                $card.addClass("is-selected");
            } else {
                $card.removeClass("is-selected");
            }
        });
        updateModeratorSelectionInput($container);
    };

    $(document).on("click", ".communityModerationModalBtn", function (e) {
        e.preventDefault();
        const communityId = $(this).data("community");
        if (!communityId) {
            return;
        }
        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: { f: "communityModerationModal", community_id: communityId },
            cache: false,
            success: function (response) {
                appendModal(response);
            }
        });
    });

    $(document).on("click", ".communityModeratorModalBtn", function (e) {
        e.preventDefault();
        const communityId = $(this).data("community");
        if (!communityId) {
            return;
        }
        moderatorSelectedUsers = new Set();
        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: { f: "communityModeratorModal", community_id: communityId },
            cache: false,
            success: function (response) {
                appendModal(response);
                const $modal = $(".community_moderator_modal").last();
                syncModeratorSelections($modal);
                toggleModeratorOptionsModal($modal, false);
            }
        });
    });

    $(document).on("keyup", ".community_moderation_search_input", function () {
        const $input = $(this);
        const searchValue = $input.val() || "";
        const $modal = $input.closest(".community_moderation_modal");
        const $defaultList = $modal.find(".community_moderation_default_list");
        const $resultsList = $modal.find(".community_moderation_search_results");
        const communityId = $defaultList.data("community") || $resultsList.data("community");
        const emptyText = $resultsList.data("empty-text") || "";
        const trimmed = searchValue.trim();

        if (!communityId) {
            return;
        }

        if (moderationSearchTimer) {
            clearTimeout(moderationSearchTimer);
            moderationSearchTimer = null;
        }
        if (moderationSearchXhr && typeof moderationSearchXhr.abort === "function") {
            moderationSearchXhr.abort();
            moderationSearchXhr = null;
        }

        if (trimmed.length < 2) {
            $resultsList.hide().html("");
            $defaultList.css("display", "flex");
            return;
        }

        $defaultList.hide();
        $resultsList.css("display", "flex").html(preLoadingAnimation);
        moderationSearchTimer = setTimeout(() => {
            moderationSearchXhr = $.ajax({
                type: "POST",
                url: siteurl + "requests/request.php",
                data: { f: "communityModerationSearch", community_id: communityId, key: trimmed },
                cache: false,
                success: function (response) {
                    if (response) {
                        $resultsList.html(response);
                    } else if (emptyText) {
                        $resultsList.html('<div class="community_moderation_empty">' + emptyText + '</div>');
                    } else {
                        $resultsList.html("");
                    }
                }
            });
        }, 400);
    });

    $(document).on("keyup", ".community_moderator_search_input", function () {
        const $input = $(this);
        const searchValue = $input.val() || "";
        const $modal = $input.closest(".community_moderator_modal");
        const $defaultList = $modal.find(".community_moderator_default_list");
        const $resultsList = $modal.find(".community_moderator_search_results");
        const communityId = $defaultList.data("community") || $resultsList.data("community");
        const emptyText = $resultsList.data("empty-text") || "";
        const trimmed = searchValue.trim();

        if (!communityId) {
            return;
        }

        if (moderatorSearchTimer) {
            clearTimeout(moderatorSearchTimer);
            moderatorSearchTimer = null;
        }
        if (moderatorSearchXhr && typeof moderatorSearchXhr.abort === "function") {
            moderatorSearchXhr.abort();
            moderatorSearchXhr = null;
        }

        if (trimmed.length < 2) {
            $resultsList.hide().html("");
            $defaultList.css("display", "flex");
            syncModeratorSelections($defaultList);
            return;
        }

        $defaultList.hide();
        $resultsList.css("display", "flex").html(preLoadingAnimation);
        moderatorSearchTimer = setTimeout(() => {
            moderatorSearchXhr = $.ajax({
                type: "POST",
                url: siteurl + "requests/request.php",
                data: { f: "communityModeratorSearch", community_id: communityId, key: trimmed },
                cache: false,
                success: function (response) {
                    if (response) {
                        $resultsList.html(response);
                        syncModeratorSelections($resultsList);
                    } else if (emptyText) {
                        $resultsList.html('<div class="community_moderation_empty">' + emptyText + "</div>");
                    } else {
                        $resultsList.html("");
                    }
                }
            });
        }, 400);
    });

    $(document).on("click", ".community_moderator_user_card", function () {
        const $card = $(this);
        if ($card.hasClass("is-added")) {
            return;
        }
        const userId = parseInt($card.data("user"), 10);
        if (!userId) {
            return;
        }
        moderatorSelectedUsers = new Set([userId]);
        const $modal = $card.closest(".community_moderator_modal");
        $modal.find(".community_moderator_user_card").removeClass("is-selected");
        $card.addClass("is-selected");
        updateModeratorSelectionInput($card);
        toggleModeratorOptionsModal($modal, true);
    });

    $(document).on("click", ".community_moderator_card", function (e) {
        e.preventDefault();
        const $card = $(this);
        const communityId = $card.data("community");
        const moderatorId = $card.data("user");
        if (!communityId || !moderatorId) {
            return;
        }
        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: { f: "communityModeratorEditModal", community_id: communityId, moderator_id: moderatorId },
            cache: false,
            success: function (response) {
                appendModal(response);
            }
        });
    });

    $(document).on("click", ".community_moderator_options_close", function () {
        const $modal = $(this).closest(".community_moderator_modal");
        toggleModeratorOptionsModal($modal, false);
    });

    $(document).on("click", ".community_moderator_options_modal", function (e) {
        if (!$(e.target).closest(".community_moderator_options_panel").length) {
            const $modal = $(this).closest(".community_moderator_modal");
            toggleModeratorOptionsModal($modal, false);
        }
    });

    $(document).on("submit", ".community_moderator_update_form", function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find(".community_moderator_submit_btn");
        if ($btn.prop("disabled")) {
            return;
        }
        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            dataType: "json",
            data: $form.serialize(),
            beforeSend: function () {
                $btn.prop("disabled", true);
            },
            success: function (response) {
                $btn.prop("disabled", false);
                if (response && response.status === "success") {
                    $form.closest(".community_moderator_edit_modal").remove();
                    return;
                }
                if (response && response.message) {
                    alert(response.message);
                    return;
                }
                if (typeof PopUPAlerts === "function") {
                    PopUPAlerts("sWrong", "ialert");
                }
            },
            error: function () {
                $btn.prop("disabled", false);
                if (typeof PopUPAlerts === "function") {
                    PopUPAlerts("sWrong", "ialert");
                }
            }
        });
    });

    $(document).on("click", ".community_moderator_remove_btn", function (e) {
        e.preventDefault();
        const $btn = $(this);
        if ($btn.prop("disabled")) {
            return;
        }
        const communityId = $btn.data("community");
        const moderatorId = $btn.data("user");
        const confirmMessage = $btn.data("confirm");
        if (!communityId || !moderatorId) {
            return;
        }
        if (confirmMessage && !window.confirm(confirmMessage)) {
            return;
        }
        const csrfToken = getCommunityCsrfToken();
        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            dataType: "json",
            data: {
                f: "communityModeratorRemove",
                community_id: communityId,
                moderator_id: moderatorId,
                csrf_token: csrfToken
            },
            beforeSend: function () {
                $btn.prop("disabled", true);
            },
            success: function (response) {
                $btn.prop("disabled", false);
                if (response && response.status === "success") {
                    const $modal = $btn.closest(".community_moderator_edit_modal");
                    if ($modal.length) {
                        $modal.remove();
                    }
                    const $listCard = $('.community_moderator_card[data-user="' + moderatorId + '"]');
                    if ($listCard.length) {
                        $listCard.remove();
                    }
                    return;
                }
                if (response && response.message) {
                    alert(response.message);
                    return;
                }
                if (typeof PopUPAlerts === "function") {
                    PopUPAlerts("sWrong", "ialert");
                }
            },
            error: function () {
                $btn.prop("disabled", false);
                if (typeof PopUPAlerts === "function") {
                    PopUPAlerts("sWrong", "ialert");
                }
            }
        });
    });

    $(document).on("click", ".community_moderation_add_btn", function () {
        const $btn = $(this);
        if ($btn.prop("disabled")) {
            return;
        }
        const communityId = $btn.data("community");
        const userId = $btn.data("user");
        const addedText = $btn.data("added-text") || "Added";
        const csrfToken = getCommunityCsrfToken();
        if (!communityId || !userId) {
            return;
        }
        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            dataType: "json",
            data: {
                f: "communityModerationAdd",
                community_id: communityId,
                user_id: userId,
                csrf_token: csrfToken
            },
            beforeSend: function () {
                $btn.prop("disabled", true);
            },
            success: function (response) {
                $btn.prop("disabled", false);
                if (response && response.status === "success") {
                    const $card = $btn.closest(".community_moderation_user_card");
                    $card.addClass("is-added");
                    $btn.replaceWith('<div class="community_moderation_added_label">' + addedText + '</div>');
                    if (response.row) {
                        const $moderationCard = $(".community_moderation_card");
                        let $list = $moderationCard.find(".community_moderation_grid");
                        if ($list.length === 0) {
                            $moderationCard.find(".community_empty_state").remove();
                            $list = $('<div class="community_moderation_grid"></div>');
                            $moderationCard.find(".profile_meta_bio").append($list);
                        }
                        if ($list.find('.community_moderation_member_card[data-user="' + userId + '"]').length === 0) {
                            $list.append(response.row);
                        }
                    }
                    return;
                }
                if (response && response.message) {
                    alert(response.message);
                    return;
                }
                if (typeof PopUPAlerts === "function") {
                    PopUPAlerts("sWrong", "ialert");
                }
            },
            error: function () {
                $btn.prop("disabled", false);
                if (typeof PopUPAlerts === "function") {
                    PopUPAlerts("sWrong", "ialert");
                }
            }
        });
    });

    $(document).on("submit", ".community_moderator_form", function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $form.find(".community_moderator_submit_btn");
        if ($btn.prop("disabled")) {
            return;
        }
        if ($form.find('input[name="moderator_user_ids"]').length > 0) {
            updateModeratorSelectionInput($form);
            const selectedValue = $form.find('input[name="moderator_user_ids"]').val() || "";
            if (selectedValue.trim() === "") {
                const fallbackMessage = $form.data("error-text") || "Unable to add user.";
                alert(fallbackMessage);
                return;
            }
        }
        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            dataType: "json",
            data: $form.serialize(),
            beforeSend: function () {
                $btn.prop("disabled", true);
            },
            success: function (response) {
                $btn.prop("disabled", false);
                if (response && response.status === "success") {
                    const assignedIds = response.assigned_ids || [];
                    if (assignedIds.length > 0) {
                        const $modal = $form.closest(".community_moderator_modal");
                        const addedText = $modal.data("added-text") || "Added";
                        assignedIds.forEach(function (id) {
                            const userId = parseInt(id, 10);
                            if (!userId) {
                                return;
                            }
                            moderatorSelectedUsers.delete(userId);
                            $modal.find('.community_moderator_user_card[data-user="' + userId + '"]')
                                .addClass("is-added")
                                .removeClass("is-selected")
                                .each(function () {
                                    const $card = $(this);
                                    if ($card.find(".community_moderation_added_label").length === 0) {
                                        $card.append('<div class="community_moderation_added_label">' + addedText + "</div>");
                                    }
                                });
                        });
                        updateModeratorSelectionInput($form);
                    }
                    if (response && response.rows) {
                        const $moderatorsCard = $(".community_moderators_card");
                        let $list = $moderatorsCard.find(".community_moderators_grid");
                        if ($list.length === 0) {
                            $moderatorsCard.find(".community_empty_state").remove();
                            $list = $('<div class="community_moderators_grid"></div>');
                            $moderatorsCard.find(".profile_meta_bio").append($list);
                        }
                        Object.keys(response.rows).forEach(function (userId) {
                            if ($list.find('.community_moderator_card[data-user="' + userId + '"]').length === 0) {
                                $list.append(response.rows[userId]);
                            }
                        });
                    }
                    toggleModeratorOptionsModal($form.closest(".community_moderator_modal"), false);
                    return;
                }
                if (response && response.message) {
                    alert(response.message);
                    return;
                }
                if (typeof PopUPAlerts === "function") {
                    PopUPAlerts("sWrong", "ialert");
                }
            },
            error: function () {
                $btn.prop("disabled", false);
                if (typeof PopUPAlerts === "function") {
                    PopUPAlerts("sWrong", "ialert");
                }
            }
        });
    });

    $(document).on("click", ".communityDeleteBtn", function () {
        const $btn = $(this);
        const communityID = $btn.data("community");
        if (!communityID) {
            return;
        }
        const confirmMessage = $btn.data("confirm");
        if (confirmMessage && !window.confirm(confirmMessage)) {
            return;
        }
        const csrfToken = getCommunityCsrfToken();
        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            dataType: "json",
            data: {
                f: "communityDelete",
                community_id: communityID,
                csrf_token: csrfToken
            },
            success: function (response) {
                if (response && response.status === "success" && response.redirect) {
                    window.location.href = response.redirect;
                    return;
                }
                if (response && response.message) {
                    alert(response.message);
                    return;
                }
                if (typeof PopUPAlerts === "function") {
                    PopUPAlerts("sWrong", "ialert");
                }
            },
            error: function () {
                if (typeof PopUPAlerts === "function") {
                    PopUPAlerts("sWrong", "ialert");
                }
            }
        });
    });

    $(document).on("submit", "#communityPostForm", function (e) {
        e.preventDefault();
        const $form = $(this);
        const $message = $form.find(".community_post_message");
        const $submit = $form.find(".communityPostSubmit");
        const formData = new FormData(this);

        $message.removeClass("is-error is-success").text("");
        $submit.prop("disabled", true);
        $form.append(loaderHTML);

        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function (response) {
                $(".loaderWrapper").remove();
                if (response && response.status === "success") {
                    $message.addClass("is-success").text(response.message || "");
                    setTimeout(() => {
                        location.reload();
                    }, 800);
                    return;
                }
                if (response && response.message) {
                    $message.addClass("is-error").text(response.message);
                } else if (typeof PopUPAlerts === "function") {
                    PopUPAlerts("sWrong", "ialert");
                }
                $submit.prop("disabled", false);
            },
            error: function () {
                $(".loaderWrapper").remove();
                if (typeof PopUPAlerts === "function") {
                    PopUPAlerts("sWrong", "ialert");
                }
                $submit.prop("disabled", false);
            }
        });
    });

    $(document).on("submit", ".community_member_form", function (e) {
        e.preventDefault();
        const $form = $(this);
        const $submit = $form.find(".communityMemberSubmit");
        const formData = $form.serialize();
        $submit.prop("disabled", true);
        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            dataType: "json",
            data: formData,
            success: function (response) {
                if (response && response.status === "success") {
                    $submit.prop("disabled", false);
                    const $memberModal = $form.closest(".community_moderation_member_modal");
                    if ($memberModal.length) {
                        $memberModal.remove();
                    }
                    return;
                }
                if (response && response.message) {
                    alert(response.message);
                } else if (typeof PopUPAlerts === "function") {
                    PopUPAlerts("sWrong", "ialert");
                }
                $submit.prop("disabled", false);
            },
            error: function () {
                if (typeof PopUPAlerts === "function") {
                    PopUPAlerts("sWrong", "ialert");
                }
                $submit.prop("disabled", false);
            }
        });
    });

    $(document).on("click", ".community_moderation_revert", function (e) {
        e.preventDefault();
        const $btn = $(this);
        const actionId = $btn.data("action");
        const communityId = $btn.data("community");
        const revertedText = $btn.data("reverted-text") || "Reverted";
        if (!actionId || !communityId) {
            return;
        }
        const csrfToken = getCommunityCsrfToken();
        $btn.prop("disabled", true);
        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            dataType: "json",
            data: {
                f: "communityModerationRevert",
                action_id: actionId,
                community_id: communityId,
                csrf_token: csrfToken
            },
            success: function (response) {
                if (response && response.status === "success") {
                    const $row = $btn.closest(".community_moderation_action_row");
                    $row.addClass("is-reverted");
                    $btn.replaceWith('<span class="community_moderation_reverted_label">' + revertedText + "</span>");
                    return;
                }
                if (response && response.message) {
                    alert(response.message);
                } else if (typeof PopUPAlerts === "function") {
                    PopUPAlerts("sWrong", "ialert");
                }
                $btn.prop("disabled", false);
            },
            error: function () {
                if (typeof PopUPAlerts === "function") {
                    PopUPAlerts("sWrong", "ialert");
                }
                $btn.prop("disabled", false);
            }
        });
    });

    $(document).on("click", ".community_moderation_member_card", function (e) {
        e.preventDefault();
        const $card = $(this);
        const communityId = $card.data("community");
        const memberId = $card.data("user");
        if (!communityId || !memberId) {
            return;
        }
        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: { f: "communityModerationMemberModal", community_id: communityId, member_id: memberId },
            cache: false,
            success: function (response) {
                appendModal(response);
            }
        });
    });

    $(document).on("click", ".communityMembersModalBtn", function (e) {
        e.preventDefault();
        const communityID = $(this).data("community");
        if (!communityID) {
            return;
        }
        $(".community_members_modal").remove();
        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: { f: "communityMembersModal", community_id: communityID },
            cache: false,
            success: function (response) {
                appendModal(response);
            }
        });
    });

    $(document).on("click", ".communityPlanModal", function (e) {
        e.preventDefault();
        $(".community_pay_modal").remove();
        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: { f: "communityPlanModal" },
            cache: false,
            success: function (response) {
                appendModal(response);
            }
        });
    });

    $(document).on("click", ".communityJoinModal", function (e) {
        e.preventDefault();
        const communityID = $(this).data("community");
        if (!communityID) {
            return;
        }
        $(".community_pay_modal").remove();
        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: { f: "communityJoinModal", community_id: communityID },
            cache: false,
            success: function (response) {
                appendModal(response);
            }
        });
    });

    $(document).on("click", ".communityJoinFree", function (e) {
        e.preventDefault();
        const communityID = $(this).data("community");
        if (!communityID) {
            return;
        }
        const csrfToken = getCommunityCsrfToken();
        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            dataType: "json",
            data: { f: "communityJoinFree", community_id: communityID, csrf_token: csrfToken },
            cache: false,
            success: function (response) {
                if (response && response.status === "success" && response.redirect) {
                    window.location.href = response.redirect;
                    return;
                }
                if (response && response.message) {
                    alert(response.message);
                    return;
                }
                if (typeof PopUPAlerts === "function") {
                    PopUPAlerts("sWrong", "ialert");
                }
            },
            error: function () {
                if (typeof PopUPAlerts === "function") {
                    PopUPAlerts("sWrong", "ialert");
                }
            }
        });
    });

    $(document).on("click", ".communityPointSubscribe", function () {
        const $btn = $(this);
        const scope = $btn.data("scope") || "community";
        const communityID = $btn.data("community");
        const config = readPayConfig() || {};
        const csrfToken = config.csrfToken || "";
        const requestType = scope === "community_plan" ? "communityPlanSubscribeWithPoints" : "communitySubscribeWithPoints";

        if (scope === "community" && !communityID) {
            return;
        }

        const data = { f: requestType };
        if (scope === "community") {
            data.community_id = communityID;
        }
        if (csrfToken) {
            data.csrf_token = csrfToken;
        }

        $btn.prop("disabled", true);
        $(".community_points_form").append(loaderHTML);

        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: data,
            cache: false,
            success: function (response) {
                $(".loaderWrapper").remove();
                $btn.prop("disabled", false);
                const parsed = parseJsonResponse(response);
                if (parsed && parsed.status === "success") {
                    const redirectUrl = parsed.redirect || parsed.redirect_url || buildSubscriptionSuccessUrl(parsed.order_id || parsed.orderId || "");
                    window.location.href = redirectUrl;
                    return;
                }
                if (String(response).trim() === "200") {
                    window.location.href = buildSubscriptionSuccessUrl("");
                    return;
                }
                if (String(response).trim() === "302") {
                    if (typeof PopUPAlerts === "function") {
                        PopUPAlerts("insufficient_balance", "ialert");
                    }
                    return;
                }
                if (typeof PopUPAlerts === "function") {
                    PopUPAlerts("sWrong", "ialert");
                }
            },
            error: function () {
                $(".loaderWrapper").remove();
                $btn.prop("disabled", false);
                if (typeof PopUPAlerts === "function") {
                    PopUPAlerts("sWrong", "ialert");
                }
            }
        });
    });

    $(document).on("click", ".communityUnsubModal", function (e) {
        e.preventDefault();
        const communityID = $(this).data("community");
        if (!communityID) {
            return;
        }
        $(".community_unsub_modal").remove();
        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            data: { f: "communityUnsubModal", community_id: communityID },
            cache: false,
            success: function (response) {
                appendModal(response);
            }
        });
    });

    $(document).on("click", ".communityUnsubConfirm", function () {
        const communityID = $(this).data("community");
        const csrfToken = $(".community_csrf_token").val() || "";
        if (!communityID) {
            return;
        }
        $(".community_unsub_modal .i_modal_in_in").append(loaderHTML);
        $.ajax({
            type: "POST",
            url: siteurl + "requests/request.php",
            dataType: "json",
            data: {
                f: "communityUnsubscribe",
                community_id: communityID,
                csrf_token: csrfToken
            },
            success: function (response) {
                $(".loaderWrapper").remove();
                if (response && response.status === "200" && response.redirect) {
                    window.location.href = response.redirect;
                    return;
                }
                if (response && response.message) {
                    alert(response.message);
                    return;
                }
                if (typeof PopUPAlerts === "function") {
                    PopUPAlerts("sWrong", "ialert");
                }
            },
            error: function () {
                $(".loaderWrapper").remove();
                if (typeof PopUPAlerts === "function") {
                    PopUPAlerts("sWrong", "ialert");
                }
            }
        });
    });

    function initCommunityDirectoryFilters() {
        const $wrapper = $(".communities_wrapper");
        if (!$wrapper.length) {
            return;
        }
        const $grid = $wrapper.find(".communities_grid");
        const $cards = $grid.find(".community_card[data-community-card]");
        const $searchInput = $wrapper.find(".communities_search_input");
        const $categoryChips = $wrapper.find(".community_filter_chip");
        const $categorySelect = $wrapper.find(".communities_category_select");
        const $priceToggles = $wrapper.find(".community_filter_toggle");
        const $sortSelect = $wrapper.find(".communities_sort_select");
        const $emptyState = $grid.find(".community_empty_state");
        const $highlights = $wrapper.find(".communities_highlights");
        let filterTimer = null;

        function normalize(value) {
            return String(value || "").toLowerCase();
        }

        function getSortValue() {
            return $sortSelect.length ? String($sortSelect.val() || "new") : "new";
        }

        function sortCards() {
            if (!$cards.length) {
                return;
            }
            const sortValue = getSortValue();
            const cards = $cards.get();
            cards.sort(function (a, b) {
                const $a = $(a);
                const $b = $(b);
                const membersA = parseInt($a.data("members"), 10) || 0;
                const membersB = parseInt($b.data("members"), 10) || 0;
                const createdA = parseInt($a.data("created"), 10) || 0;
                const createdB = parseInt($b.data("created"), 10) || 0;
                const amountA = parseFloat($a.data("amount")) || 0;
                const amountB = parseFloat($b.data("amount")) || 0;
                if (sortValue === "popular") {
                    if (membersA !== membersB) {
                        return membersB - membersA;
                    }
                    return amountB - amountA;
                }
                if (sortValue === "trending") {
                    if (membersA !== membersB) {
                        return membersB - membersA;
                    }
                    return createdB - createdA;
                }
                return createdB - createdA;
            });
            const $empty = $grid.find(".community_empty_state");
            $empty.before(cards);
        }

        function filterCards() {
            const query = normalize($searchInput.val());
            const $activeCategory = $categoryChips.filter(".is-active").first();
            const $activePrice = $priceToggles.filter(".is-active").first();
            const activeCategory = normalize($activeCategory.data("category") || "all");
            const activePrice = normalize($activePrice.data("price") || "all");
            let visibleCount = 0;

            $cards.each(function () {
                const $card = $(this);
                const title = normalize($card.data("title"));
                const description = normalize($card.data("description"));
                const category = normalize($card.data("category"));
                const price = normalize($card.data("price"));
                let matches = true;
                if (activeCategory !== "all" && category !== activeCategory) {
                    matches = false;
                }
                if (activePrice !== "all" && price !== activePrice) {
                    matches = false;
                }
                if (query && title.indexOf(query) === -1 && description.indexOf(query) === -1) {
                    matches = false;
                }
                $card.toggle(matches);
                if (matches) {
                    visibleCount += 1;
                }
            });

            if ($emptyState.length) {
                $emptyState.toggle(visibleCount === 0);
            }

            if ($highlights.length) {
                const showHighlights = !query && activeCategory === "all" && activePrice === "all";
                $highlights.toggle(showHighlights);
            }
        }

        function scheduleFilter() {
            $wrapper.addClass("is-filtering");
            if (filterTimer) {
                clearTimeout(filterTimer);
            }
            filterTimer = setTimeout(function () {
                filterCards();
                sortCards();
                $wrapper.removeClass("is-filtering");
            }, 120);
        }

        function syncCategorySelect() {
            if (!$categorySelect.length) {
                return;
            }
            const $activeCategory = $categoryChips.filter(".is-active").first();
            const activeValue = String($activeCategory.data("category") || "all");
            $categorySelect.val(activeValue);
        }

        $categoryChips.on("click", function () {
            $categoryChips.removeClass("is-active").attr("aria-pressed", "false");
            $(this).addClass("is-active").attr("aria-pressed", "true");
            syncCategorySelect();
            scheduleFilter();
        });

        if ($categorySelect.length) {
            $categorySelect.on("change", function () {
                const selectedValue = String($(this).val() || "all");
                let $targetChip = $categoryChips.filter(function () {
                    return String($(this).data("category") || "") === selectedValue;
                }).first();
                if (!$targetChip.length) {
                    $targetChip = $categoryChips.filter("[data-category='all']").first();
                }
                $categoryChips.removeClass("is-active").attr("aria-pressed", "false");
                if ($targetChip.length) {
                    $targetChip.addClass("is-active").attr("aria-pressed", "true");
                }
                scheduleFilter();
            });
        }

        $priceToggles.on("click", function () {
            $priceToggles.removeClass("is-active").attr("aria-pressed", "false");
            $(this).addClass("is-active").attr("aria-pressed", "true");
            scheduleFilter();
        });

        if ($sortSelect.length) {
            $sortSelect.on("change", function () {
                scheduleFilter();
            });
        }

        if ($searchInput.length) {
            $searchInput.on("input", function () {
                scheduleFilter();
            });
        }

        filterCards();
        sortCards();
        syncCategorySelect();
    }

    $(document).ready(function () {
        initCommunityDirectoryFilters();
    });
})(jQuery);
