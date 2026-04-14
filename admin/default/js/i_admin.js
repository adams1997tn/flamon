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
	    var preLoadingAnimation = '<div class="i_loading product_page_loading"><div class="dot-pulse"></div></div>';
	    var plreLoadingAnimationPlus = '<div class="loaderWrapper"><div class="loaderContainer"><div class="loader">' + preLoadingAnimation + '</div></div></div>';
	    var uploadLoading = '<div class="i_upload_progress"></div>';
	    if (typeof window !== "undefined") {
	        window.preLoadingAnimation = window.preLoadingAnimation || preLoadingAnimation;
	        window.plreLoadingAnimationPlus = window.plreLoadingAnimationPlus || plreLoadingAnimationPlus;
	        window.uploadLoading = window.uploadLoading || uploadLoading;
	    }
	    $(document).on("click", ".subCaller", function() {
            if ($(".i_admin_menu_wrapper").hasClass("menu-searching")) {
                return;
            }
		        var type = $(this).attr("data-id");
		        if (!$("#" + type).hasClass("sub_in")) {
		            $("#" + type).addClass("sub_in");
		        } else {
	            $(".sub_menu_wrapper").removeClass("sub_in");
	        }
	    });
	    var adminMenuSearchInput = $("#adminMenuSearchInput");
	    var adminMenuSearchClear = $("#adminMenuSearchClear");
	    var adminMenuAutocomplete = $("#adminMenuAutocomplete");
	    var adminMenuWrapper = $(".i_admin_menu_wrapper");
	    if (adminMenuSearchInput.length && adminMenuWrapper.length) {
	        var adminMenuSectionTitles = adminMenuWrapper.children(".menu_section_title");
	        var adminMenuGroupRows = adminMenuWrapper.children(".menu_item.subCaller");
	        var adminMenuSubWrappers = adminMenuWrapper.children(".sub_menu_wrapper");
	        var adminMenuTopLevelLinks = adminMenuWrapper.children("a");
	        adminMenuSubWrappers.each(function() {
	            var wrapper = $(this);
	            wrapper.attr("data-default-open", wrapper.hasClass("sub_in") ? "1" : "0");
	        });

	        var normalizeMenuSearch = function(value) {
	            return $.trim((value || "").toString().toLowerCase());
	        };
	        var adminMenuSuggestionValues = [];
	        var activeAutocompleteIndex = -1;
	        var escapeHtml = function(value) {
	            return (value || "").toString()
	                .replace(/&/g, "&amp;")
	                .replace(/</g, "&lt;")
	                .replace(/>/g, "&gt;")
	                .replace(/"/g, "&quot;")
	                .replace(/'/g, "&#39;");
	        };
	        var buildMenuSuggestions = function() {
	            var values = [];
	            var seen = {};
	            adminMenuWrapper.find("[data-menu-label]").each(function() {
	                var label = $.trim(($(this).attr("data-menu-label") || "").toString());
	                var key = normalizeMenuSearch(label);
	                if (label === "" || key === "" || seen[key]) {
	                    return;
	                }
	                seen[key] = true;
	                values.push(label);
	            });
	            values.sort(function(a, b) {
	                return a.localeCompare(b);
	            });
	            adminMenuSuggestionValues = values;
	        };
	        var renderAutocompleteItems = function(items) {
	            if (!adminMenuAutocomplete.length) {
	                return;
	            }
	            var html = "";
	            $.each(items, function(index, value) {
	                var safeValue = escapeHtml(value);
	                html += '<button type="button" class="admin_menu_autocomplete_item" data-value="' + safeValue + '">' + safeValue + '</button>';
	            });
	            adminMenuAutocomplete.html(html);
	            adminMenuAutocomplete.toggle(items.length > 0);
	            activeAutocompleteIndex = -1;
	        };
	        var updateAutocompleteActiveState = function() {
	            if (!adminMenuAutocomplete.length) {
	                return;
	            }
	            var items = adminMenuAutocomplete.find(".admin_menu_autocomplete_item");
	            items.removeClass("is-active");
	            if (activeAutocompleteIndex >= 0 && activeAutocompleteIndex < items.length) {
	                $(items[activeAutocompleteIndex]).addClass("is-active");
	            }
	        };
	        var updateAutocomplete = function(rawQuery) {
	            if (!adminMenuAutocomplete.length) {
	                return;
	            }
	            var query = normalizeMenuSearch(rawQuery);
	            if (query === "") {
	                adminMenuAutocomplete.hide();
	                adminMenuAutocomplete.empty();
	                activeAutocompleteIndex = -1;
	                return;
	            }
	            var startsWithMatches = [];
	            var containsMatches = [];
	            $.each(adminMenuSuggestionValues, function(_, itemLabel) {
	                var normalized = normalizeMenuSearch(itemLabel);
	                if (normalized.indexOf(query) === 0) {
	                    startsWithMatches.push(itemLabel);
	                } else if (normalized.indexOf(query) !== -1) {
	                    containsMatches.push(itemLabel);
	                }
	            });
	            var matched = startsWithMatches.concat(containsMatches).slice(0, 8);
	            renderAutocompleteItems(matched);
	        };
	        var getMenuSearchText = function(element) {
	            var el = $(element);
	            var fromData = normalizeMenuSearch(el.attr("data-menu-label"));
	            if (fromData !== "") {
	                return fromData;
	            }
	            var fromLabel = normalizeMenuSearch(el.find(".lm").first().text());
	            if (fromLabel !== "") {
	                return fromLabel;
	            }
	            return normalizeMenuSearch(el.text());
	        };
	        var updateMenuSectionVisibility = function() {
	            adminMenuSectionTitles.each(function() {
	                var title = $(this);
	                var blockElements = title.nextUntil(".menu_section_title");
	                var hasVisibleElement = false;
	                blockElements.each(function() {
	                    var block = $(this);
	                    if (!block.is(":visible")) {
	                        return;
	                    }
	                    if (block.hasClass("sub_menu_wrapper")) {
	                        if (block.find("a:visible").length > 0) {
	                            hasVisibleElement = true;
	                            return false;
	                        }
	                        return;
	                    }
	                    hasVisibleElement = true;
	                    return false;
	                });
	                title.toggle(hasVisibleElement);
	            });
	        };
	        var restoreAdminMenuSearch = function() {
	            adminMenuWrapper.removeClass("menu-searching");
	            adminMenuTopLevelLinks.show();
	            adminMenuGroupRows.show();
	            adminMenuSubWrappers.each(function() {
	                var wrapper = $(this);
	                var openBeforeSearch = wrapper.attr("data-open-before-search");
	                var openState = openBeforeSearch !== undefined ? openBeforeSearch : wrapper.attr("data-default-open");
	                wrapper.find("a").show();
	                wrapper.removeClass("menu-search-open");
	                wrapper.css("display", "");
	                if (openState === "1") {
	                    wrapper.addClass("sub_in");
	                } else {
	                    wrapper.removeClass("sub_in");
	                }
	                wrapper.removeAttr("data-open-before-search");
	            });
	            updateMenuSectionVisibility();
	        };
	        var applyAdminMenuSearch = function(rawQuery) {
	            var query = normalizeMenuSearch(rawQuery);
	            if (query === "") {
	                restoreAdminMenuSearch();
	                return;
	            }

	            if (!adminMenuWrapper.hasClass("menu-searching")) {
	                adminMenuSubWrappers.each(function() {
	                    var wrapper = $(this);
	                    wrapper.attr("data-open-before-search", wrapper.hasClass("sub_in") ? "1" : "0");
	                });
	            }
	            adminMenuWrapper.addClass("menu-searching");
	            adminMenuTopLevelLinks.each(function() {
	                var link = $(this);
	                var row = link.children(".menu_item, .sub_menu_item").first();
	                if (!row.length || row.hasClass("subCaller")) {
	                    return;
	                }
	                var linkMatch = getMenuSearchText(link).indexOf(query) !== -1;
	                var rowMatch = getMenuSearchText(row).indexOf(query) !== -1;
	                link.toggle(linkMatch || rowMatch);
	            });

	            adminMenuGroupRows.each(function() {
	                var groupRow = $(this);
	                var groupId = groupRow.attr("data-id") || "";
	                var subWrapper = groupId ? $("#" + groupId) : $();
	                var groupMatch = getMenuSearchText(groupRow).indexOf(query) !== -1;
	                var hasChildMatch = false;

	                if (subWrapper.length) {
	                    subWrapper.find("a").each(function() {
	                        var childLink = $(this);
	                        var childMatch = groupMatch || getMenuSearchText(childLink).indexOf(query) !== -1;
	                        childLink.toggle(childMatch);
	                        if (childMatch) {
	                            hasChildMatch = true;
	                        }
	                    });

	                    if (groupMatch || hasChildMatch) {
	                        subWrapper.addClass("sub_in menu-search-open").show();
	                    } else {
	                        subWrapper.removeClass("menu-search-open").hide();
	                    }
	                }

	                groupRow.toggle(groupMatch || hasChildMatch);
	            });

	            updateMenuSectionVisibility();
	        };
	        adminMenuSearchInput.on("input search", function() {
	            var currentValue = $(this).val();
	            applyAdminMenuSearch(currentValue);
	            updateAutocomplete(currentValue);
	            adminMenuSearchClear.toggle(normalizeMenuSearch(currentValue) !== "");
	        });
	        adminMenuSearchInput.on("keydown", function(event) {
	            var autocompleteItems = adminMenuAutocomplete.find(".admin_menu_autocomplete_item");
	            if (event.key === "ArrowDown" && autocompleteItems.length) {
	                event.preventDefault();
	                activeAutocompleteIndex = (activeAutocompleteIndex + 1) % autocompleteItems.length;
	                updateAutocompleteActiveState();
	                return;
	            }
	            if (event.key === "ArrowUp" && autocompleteItems.length) {
	                event.preventDefault();
	                activeAutocompleteIndex = activeAutocompleteIndex <= 0 ? autocompleteItems.length - 1 : activeAutocompleteIndex - 1;
	                updateAutocompleteActiveState();
	                return;
	            }
	            if (event.key === "Escape") {
	                event.preventDefault();
	                $(this).val("");
	                applyAdminMenuSearch("");
	                updateAutocomplete("");
	                adminMenuSearchClear.hide();
	                return;
	            }
	            if (event.key === "Enter") {
	                if (autocompleteItems.length && activeAutocompleteIndex >= 0 && activeAutocompleteIndex < autocompleteItems.length) {
	                    event.preventDefault();
	                    var chosenItem = $(autocompleteItems[activeAutocompleteIndex]).attr("data-value") || "";
	                    adminMenuSearchInput.val(chosenItem);
	                    applyAdminMenuSearch(chosenItem);
	                    updateAutocomplete(chosenItem);
	                    adminMenuSearchClear.show();
	                    return;
	                }
	                var query = normalizeMenuSearch($(this).val());
	                if (query === "") {
	                    return;
	                }
	                var firstVisibleLink = adminMenuWrapper.find("a:visible").filter(function() {
	                    return getMenuSearchText(this).indexOf(query) !== -1;
	                }).first();
	                if (firstVisibleLink.length) {
	                    event.preventDefault();
	                    window.location.href = firstVisibleLink.attr("href");
	                }
	            }
	        });
	        adminMenuAutocomplete.on("click", ".admin_menu_autocomplete_item", function() {
	            var selectedValue = ($(this).attr("data-value") || "").toString();
	            adminMenuSearchInput.val(selectedValue);
	            applyAdminMenuSearch(selectedValue);
	            updateAutocomplete(selectedValue);
	            adminMenuSearchClear.show();
	            adminMenuSearchInput.trigger("focus");
	        });
	        $(document).on("mousedown", function(event) {
	            if (!$(event.target).closest(".admin_menu_search_wrapper").length) {
	                adminMenuAutocomplete.hide();
	            }
	        });
	        adminMenuSearchInput.on("focus", function() {
	            updateAutocomplete($(this).val());
	        });
	        adminMenuSearchClear.on("click", function() {
	            adminMenuSearchInput.val("");
	            applyAdminMenuSearch("");
	            updateAutocomplete("");
	            adminMenuSearchClear.hide();
	            adminMenuSearchInput.trigger("focus");
	        });
	        buildMenuSuggestions();
	        restoreAdminMenuSearch();
	        adminMenuSearchClear.hide();
	        if (adminMenuAutocomplete.length) {
	            adminMenuAutocomplete.hide();
	        }
	    }
	    var adminAlertsToggle = $("#adminAlertsToggle");
	    var adminAlertsCenter = $(".admin_alert_center");
	    var adminPendingToggle = $("#adminPendingToggle");
	    var adminPendingCenter = $(".admin_pending_center");
	    var adminHealthToggle = $("#adminHealthToggle");
	    var adminHealthCenter = $(".admin_health_center");
	    var closeAdminPending = function() {
	        adminPendingCenter.removeClass("is-open");
	        adminPendingToggle.attr("aria-expanded", "false");
	    };
	    var closeAdminHealth = function() {
	        adminHealthCenter.removeClass("is-open");
	        adminHealthToggle.attr("aria-expanded", "false");
	    };
	    var closeAdminAlerts = function() {
	        adminAlertsCenter.removeClass("is-open");
	        adminAlertsToggle.attr("aria-expanded", "false");
	    };
	    if (adminPendingToggle.length && adminPendingCenter.length) {
	        adminPendingToggle.on("click", function(event) {
	            event.preventDefault();
	            event.stopPropagation();
	            var shouldOpen = !adminPendingCenter.hasClass("is-open");
	            closeAdminAlerts();
	            closeAdminHealth();
	            closeAdminPending();
	            if (shouldOpen) {
	                adminPendingCenter.addClass("is-open");
	                adminPendingToggle.attr("aria-expanded", "true");
	            }
	        });
	    }
	    if (adminHealthToggle.length && adminHealthCenter.length) {
	        adminHealthToggle.on("click", function(event) {
	            event.preventDefault();
	            event.stopPropagation();
	            var shouldOpen = !adminHealthCenter.hasClass("is-open");
	            closeAdminAlerts();
	            closeAdminPending();
	            closeAdminHealth();
	            if (shouldOpen) {
	                adminHealthCenter.addClass("is-open");
	                adminHealthToggle.attr("aria-expanded", "true");
	            }
	        });
	    }
	    if (adminAlertsToggle.length && adminAlertsCenter.length) {
	        adminAlertsToggle.on("click", function(event) {
	            event.preventDefault();
	            event.stopPropagation();
	            var shouldOpen = !adminAlertsCenter.hasClass("is-open");
	            closeAdminPending();
	            closeAdminHealth();
	            closeAdminAlerts();
	            if (shouldOpen) {
	                adminAlertsCenter.addClass("is-open");
	                adminAlertsToggle.attr("aria-expanded", "true");
	            }
	        });
	        $(document).on("click", function(event) {
	            if (
	                !$(event.target).closest(".admin_alert_center").length
	                && !$(event.target).closest(".admin_pending_center").length
	                && !$(event.target).closest(".admin_health_center").length
	            ) {
	                closeAdminPending();
	                closeAdminHealth();
	                closeAdminAlerts();
	            }
	        });
	    } else {
	        $(document).on("click", function(event) {
	            if (
	                !$(event.target).closest(".admin_pending_center").length
	                && !$(event.target).closest(".admin_health_center").length
	            ) {
	                closeAdminPending();
	                closeAdminHealth();
	            }
	        });
	    }
	    var adminCommandPalette = $("#adminCommandPalette");
	    if (adminCommandPalette.length) {
	        var adminCommandTrigger = $("#adminOpenCommandPalette");
	        var adminCommandInput = $("#adminCommandInput");
	        var adminCommandList = $("#adminCommandList");
	        var adminCommandEmpty = $("#adminCommandEmpty");
	        var commandActiveIndex = -1;
	        var allCommands = [];
	        var filteredCommands = [];
	        var normalizeCommandText = function(value) {
	            return $.trim((value || "").toString().toLowerCase());
	        };
	        var addCommand = function(label, url, source) {
	            var normalizedLabel = $.trim((label || "").toString());
	            var normalizedUrl = $.trim((url || "").toString());
	            if (normalizedLabel === "" || normalizedUrl === "" || normalizedUrl === "#") {
	                return;
	            }
	            var dedupeKey = normalizedUrl.toLowerCase();
	            var alreadyExists = allCommands.some(function(commandItem) {
	                return commandItem.key === dedupeKey;
	            });
	            if (alreadyExists) {
	                return;
	            }
	            allCommands.push({
	                key: dedupeKey,
	                label: normalizedLabel,
	                url: normalizedUrl,
	                source: source || "menu"
	            });
	        };
	        $(".i_admin_menu_wrapper a[data-menu-link='1']").each(function() {
	            var menuLink = $(this);
	            addCommand(menuLink.attr("data-menu-label") || menuLink.text(), menuLink.attr("href"), "menu");
	        });
	        $(".admin_pending_item[href]").each(function() {
	            var taskLink = $(this);
	            addCommand(taskLink.text(), taskLink.attr("href"), "task");
	        });
	        allCommands.sort(function(left, right) {
	            if (left.source !== right.source) {
	                return left.source === "task" ? -1 : 1;
	            }
	            return left.label.localeCompare(right.label);
	        });
	        var setActiveCommandIndex = function(newIndex) {
	            var commandItems = adminCommandList.find(".admin_command_item");
	            commandItems.removeClass("is-active");
	            if (!commandItems.length) {
	                commandActiveIndex = -1;
	                return;
	            }
	            if (newIndex < 0) {
	                newIndex = commandItems.length - 1;
	            } else if (newIndex >= commandItems.length) {
	                newIndex = 0;
	            }
	            commandActiveIndex = newIndex;
	            var activeItem = $(commandItems[commandActiveIndex]);
	            activeItem.addClass("is-active");
	            var listContainer = adminCommandList.get(0);
	            if (listContainer && activeItem.length) {
	                var containerTop = listContainer.scrollTop;
	                var containerBottom = containerTop + listContainer.clientHeight;
	                var itemTop = activeItem.get(0).offsetTop;
	                var itemBottom = itemTop + activeItem.outerHeight();
	                if (itemTop < containerTop) {
	                    listContainer.scrollTop = itemTop;
	                } else if (itemBottom > containerBottom) {
	                    listContainer.scrollTop = itemBottom - listContainer.clientHeight;
	                }
	            }
	        };
	        var renderCommands = function(rawQuery) {
	            var query = normalizeCommandText(rawQuery);
	            adminCommandList.empty();
	            filteredCommands = [];
	            $.each(allCommands, function(_, commandItem) {
	                var commandLabel = normalizeCommandText(commandItem.label);
	                var commandUrl = normalizeCommandText(commandItem.url);
	                if (query !== "" && commandLabel.indexOf(query) === -1 && commandUrl.indexOf(query) === -1) {
	                    return;
	                }
	                filteredCommands.push(commandItem);
	            });
	            if (!filteredCommands.length) {
	                adminCommandEmpty.show();
	                setActiveCommandIndex(-1);
	                return;
	            }
	            adminCommandEmpty.hide();
	            $.each(filteredCommands.slice(0, 40), function(_, commandItem) {
	                var itemButton = $("<button>", {
	                    type: "button",
	                    "class": "admin_command_item",
	                    "data-url": commandItem.url
	                });
	                var labelSpan = $("<span>", {
	                    "class": "admin_command_item_label",
	                    text: commandItem.label
	                });
	                var pathSpan = $("<span>", {
	                    "class": "admin_command_item_path",
	                    text: commandItem.url.replace((window.siteRoot || ""), "")
	                });
	                itemButton.append(labelSpan).append(pathSpan);
	                adminCommandList.append(itemButton);
	            });
	            setActiveCommandIndex(0);
	        };
	        var closeCommandPalette = function() {
	            adminCommandPalette.removeClass("is-open").attr("aria-hidden", "true");
	            adminCommandInput.val("");
	            adminCommandList.empty();
	            adminCommandEmpty.hide();
	            commandActiveIndex = -1;
	        };
	        var openCommandPalette = function() {
	            adminCommandPalette.addClass("is-open").attr("aria-hidden", "false");
	            renderCommands("");
	            adminCommandInput.trigger("focus");
	        };
	        adminCommandTrigger.on("click", function(event) {
	            event.preventDefault();
	            openCommandPalette();
	        });
	        adminCommandPalette.on("click", "[data-command-close='1']", function() {
	            closeCommandPalette();
	        });
	        adminCommandList.on("click", ".admin_command_item", function() {
	            var targetUrl = ($(this).attr("data-url") || "").toString();
	            if (targetUrl !== "") {
	                window.location.href = targetUrl;
	            }
	        });
	        adminCommandInput.on("input", function() {
	            renderCommands($(this).val());
	        });
	        adminCommandInput.on("keydown", function(event) {
	            if (event.key === "ArrowDown") {
	                event.preventDefault();
	                setActiveCommandIndex(commandActiveIndex + 1);
	                return;
	            }
	            if (event.key === "ArrowUp") {
	                event.preventDefault();
	                setActiveCommandIndex(commandActiveIndex - 1);
	                return;
	            }
	            if (event.key === "Enter") {
	                event.preventDefault();
	                if (commandActiveIndex >= 0 && commandActiveIndex < filteredCommands.length) {
	                    window.location.href = filteredCommands[commandActiveIndex].url;
	                }
	            }
	        });
	        $(document).on("keydown", function(event) {
	            var isPaletteShortcut = (event.ctrlKey || event.metaKey) && (event.key === "k" || event.key === "K");
	            if (isPaletteShortcut) {
	                event.preventDefault();
	                openCommandPalette();
	                return;
	            }
	            if (event.key === "Escape" && adminCommandPalette.hasClass("is-open")) {
	                event.preventDefault();
	                closeCommandPalette();
	            }
	        });
	    }
	    $(document).on("click",".saveChange", function(){
	        var dataID = $(this).attr("data-id");
	        var val = $("[name='" + dataID + "']").val();
        var data = 'f=changeColor&data='+dataID+'&clr='+val;
        if (val.trim() !== "") {
            $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("." + dataID).append(plreLoadingAnimationPlus); 
            },
            success: function(response) {
                if(response){ 
                    $(".i_modal_display_in_picker").remove();
                } 
                $(".loaderWrapper").remove();
            }
        });
        }
    });
    $(document).on("click",".setDefaultColor", function(){
        var dataID = $(this).attr("data-id"); 
        var data = 'f=setDefaultColor&data='+dataID;
        if (dataID.trim() !== "") {
            $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                 $("." + dataID).append(plreLoadingAnimationPlus); 
            },
            success: function(response) {
                $("[name='" + dataID + "']").val(''); 
                $("." + dataID).css('background-color', '');
                $(".loaderWrapper").remove(); 
            }
        });
        }
    });
    $(document).on("keyup input", "#gsearchsimple", function() {
        if (this.value.length > 0) {
            $(".i_choose_country").addClass("boxactive");
            $("#simple div").hide().filter(function() {
                return $(this).text().toLowerCase().lastIndexOf($("#gsearchsimple").val().toLowerCase(), 0) == 0;
            }).show();
        } else {
            $(".i_choose_country").removeClass("boxactive");
        }
    });
    $(document).on("mouseup touchend", function(e) {
        var boxContainer = $('.i_choose_country , .i_limit_list_mp_container , .i_limit_list_container , .i_limit_list_ch_container , .i_limit_list_cp_container , .i_limit_list_p_container , .i_limit_list_s3_container, .i_point_sub_list_container , .i_limit_list_cpads_container , .i_limit_list_cpsugg_container , .i_limit_list_cpprod_container , .i_limit_list_cptrend_container , .i_limit_list_cptrendhash_container , .i_limit_list_cpfriendactivities_container , .i_limit_list_cpfriendactivitiesshown_container');
        if (!boxContainer.is(e.target) && boxContainer.has(e.target).length === 0) {
            $(boxContainer).removeClass('boxactive');
        }
    });
    $(document).on("click", ".i_s_country", function() {
        var countryCode = $(this).attr("id");
        var countryName = $(this).attr("data-c");
        $("#newCountry").val(countryCode);
        $("#gsearchsimple").val(countryName);
        $(".i_choose_country").removeClass("boxactive");
    });
    $(document).on('submit', "#genderOptionsForm", function(e) {
        e.preventDefault();
        var $form = $(this);
        $.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: $form.serialize(),
            beforeSend: function() {
                $(".successNot, .warning_invalid_genders").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                $form.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    $form.find(':input[type=submit]').prop('disabled', false);
                }, 500);
                if (data == '200') {
                    $(".successNot").show();
                } else if (data == 'invalid') {
                    $(".warning_invalid_genders").show();
                } else {
                    $("body").append('<div class=\"nnauthority\"><div class=\"no_permis flex_ c3 border_one tabing\">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            },
            error: function() {
                $form.find(':input[type=submit]').prop('disabled', false);
                $(".loaderWrapper").remove();
            }
        });
    });

    $(document).on('submit', "#sLoginSet", function(e) {
        e.preventDefault();
        var sLoginSet = $("#sLoginSet");
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: sLoginSet.serialize(),
            beforeSend: function() {
                $(".warning_").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                sLoginSet.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    sLoginSet.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    location.reload();
                } else if (data == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on('submit', "#ageVerifSettingsForm", function(e) {
        e.preventDefault();
        var $form = $("#ageVerifSettingsForm");
        $.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: $form.serialize(),
            beforeSend: function() {
                $(".successNot, .warning_ageverif_incomplete, .warning_ageverif_columns_missing, .warning_ageverif_failed, .warning_ageverif_missing_client_id, .warning_ageverif_missing_client_secret, .warning_ageverif_missing_authorize_url, .warning_ageverif_missing_token_url, .warning_ageverif_invalid_authorize_url, .warning_ageverif_invalid_token_url, .warning_ageverif_invalid_verify_url, .warning_ageverif_missing_client_id_test, .warning_ageverif_missing_client_secret_test, .warning_ageverif_missing_authorize_url_test, .warning_ageverif_missing_token_url_test, .warning_ageverif_invalid_authorize_url_test, .warning_ageverif_invalid_token_url_test, .warning_ageverif_invalid_verify_url_test, .warning_yoti_missing_client_id, .warning_yoti_missing_client_secret, .warning_yoti_missing_authorize_url, .warning_yoti_missing_token_url, .warning_yoti_invalid_authorize_url, .warning_yoti_invalid_token_url, .warning_yoti_invalid_verify_url, .warning_yoti_missing_client_id_test, .warning_yoti_missing_client_secret_test, .warning_yoti_missing_authorize_url_test, .warning_yoti_missing_token_url_test, .warning_yoti_invalid_authorize_url_test, .warning_yoti_invalid_token_url_test, .warning_yoti_invalid_verify_url_test, .warning_didit_missing_api_key, .warning_didit_invalid_api_key, .warning_didit_missing_webhook_secret, .warning_didit_invalid_webhook_secret, .warning_didit_missing_workflow_id, .warning_didit_invalid_workflow_id").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                $form.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    $form.find(':input[type=submit]').prop('disabled', false);
                }, 500);
                if (data == '200') {
                    $(".successNot").show();
                } else if (data == 'ageverif_missing_client_id') {
                    $(".warning_ageverif_missing_client_id").show();
                } else if (data == 'ageverif_missing_client_secret') {
                    $(".warning_ageverif_missing_client_secret").show();
                } else if (data == 'ageverif_missing_authorize_url') {
                    $(".warning_ageverif_missing_authorize_url").show();
                } else if (data == 'ageverif_missing_token_url') {
                    $(".warning_ageverif_missing_token_url").show();
                } else if (data == 'ageverif_invalid_authorize_url') {
                    $(".warning_ageverif_invalid_authorize_url").show();
                } else if (data == 'ageverif_invalid_token_url') {
                    $(".warning_ageverif_invalid_token_url").show();
                } else if (data == 'ageverif_invalid_verify_url') {
                    $(".warning_ageverif_invalid_verify_url").show();
                } else if (data == 'ageverif_missing_client_id_test') {
                    $(".warning_ageverif_missing_client_id_test").show();
                } else if (data == 'ageverif_missing_client_secret_test') {
                    $(".warning_ageverif_missing_client_secret_test").show();
                } else if (data == 'ageverif_missing_authorize_url_test') {
                    $(".warning_ageverif_missing_authorize_url_test").show();
                } else if (data == 'ageverif_missing_token_url_test') {
                    $(".warning_ageverif_missing_token_url_test").show();
                } else if (data == 'ageverif_invalid_authorize_url_test') {
                    $(".warning_ageverif_invalid_authorize_url_test").show();
                } else if (data == 'ageverif_invalid_token_url_test') {
                    $(".warning_ageverif_invalid_token_url_test").show();
                } else if (data == 'ageverif_invalid_verify_url_test') {
                    $(".warning_ageverif_invalid_verify_url_test").show();
                } else if (data == 'yoti_missing_client_id') {
                    $(".warning_yoti_missing_client_id").show();
                } else if (data == 'yoti_missing_client_secret') {
                    $(".warning_yoti_missing_client_secret").show();
                } else if (data == 'yoti_missing_authorize_url') {
                    $(".warning_yoti_missing_authorize_url").show();
                } else if (data == 'yoti_missing_token_url') {
                    $(".warning_yoti_missing_token_url").show();
                } else if (data == 'yoti_invalid_authorize_url') {
                    $(".warning_yoti_invalid_authorize_url").show();
                } else if (data == 'yoti_invalid_token_url') {
                    $(".warning_yoti_invalid_token_url").show();
                } else if (data == 'yoti_invalid_verify_url') {
                    $(".warning_yoti_invalid_verify_url").show();
                } else if (data == 'yoti_missing_client_id_test') {
                    $(".warning_yoti_missing_client_id_test").show();
                } else if (data == 'yoti_missing_client_secret_test') {
                    $(".warning_yoti_missing_client_secret_test").show();
                } else if (data == 'yoti_missing_authorize_url_test') {
                    $(".warning_yoti_missing_authorize_url_test").show();
                } else if (data == 'yoti_missing_token_url_test') {
                    $(".warning_yoti_missing_token_url_test").show();
                } else if (data == 'yoti_invalid_authorize_url_test') {
                    $(".warning_yoti_invalid_authorize_url_test").show();
                } else if (data == 'yoti_invalid_token_url_test') {
                    $(".warning_yoti_invalid_token_url_test").show();
                } else if (data == 'yoti_invalid_verify_url_test') {
                    $(".warning_yoti_invalid_verify_url_test").show();
                } else if (data == 'didit_missing_api_key') {
                    $(".warning_didit_missing_api_key").show();
                } else if (data == 'didit_invalid_api_key') {
                    $(".warning_didit_invalid_api_key").show();
                } else if (data == 'didit_missing_webhook_secret') {
                    $(".warning_didit_missing_webhook_secret").show();
                } else if (data == 'didit_invalid_webhook_secret') {
                    $(".warning_didit_invalid_webhook_secret").show();
                } else if (data == 'didit_missing_workflow_id') {
                    $(".warning_didit_missing_workflow_id").show();
                } else if (data == 'didit_invalid_workflow_id') {
                    $(".warning_didit_invalid_workflow_id").show();
                } else if (data == 'config_incomplete') {
                    $(".warning_ageverif_incomplete").show();
                } else if (data == 'columns_missing') {
                    $(".warning_ageverif_columns_missing").show();
                } else {
                    $(".warning_ageverif_failed").show();
                }
                $(".loaderWrapper").remove();
            },
            error: function() {
                $form.find(':input[type=submit]').prop('disabled', false);
                $(".warning_ageverif_failed").show();
                $(".loaderWrapper").remove();
            }
        });
    });
    function toggleAgeVerifProvider() {
        var provider = $('select[name="age_verif_provider"]').val() || 'ageverif';
        $(".ageverif-provider-section").hide();
        $('.ageverif-provider-section[data-provider="' + provider + '"]').show();
    }
    function toggleAgeVerifEnvironment(provider) {
        var selector = provider === 'ageverif'
            ? 'select[name="age_verif_environment"]'
            : 'select[name="' + provider + '_age_verif_environment"]';
        var env = $(selector).val() || 'live';
        $('.ageverif-env-section[data-provider="' + provider + '"]').hide();
        $('.ageverif-env-section[data-provider="' + provider + '"][data-env="' + env + '"]').show();
    }
    $(document).on('change', 'select[name="age_verif_provider"]', function() {
        toggleAgeVerifProvider();
    });
    $(document).on('change', '.ageverif-env-select', function() {
        var provider = $(this).data('provider') || 'ageverif';
        toggleAgeVerifEnvironment(provider);
    });
    $(function() {
        toggleAgeVerifProvider();
        toggleAgeVerifEnvironment('ageverif');
        toggleAgeVerifEnvironment('yoti');
        toggleAgeVerifEnvironment('didit');
    });
    $(document).on('submit', "#updatePaymentGataway", function(e) {
        e.preventDefault();
        var updateGateway = $("#updatePaymentGataway");
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: updateGateway.serialize(),
            beforeSend: function() {
                $(".successNot , .warning_").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                updateGateway.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    updateGateway.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    $(".successNot").show();
                } else if (data == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on('submit', "#paymentSettings", function(e) {
        e.preventDefault();
        var paymentSettings = $("#paymentSettings");
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: paymentSettings.serialize(),
            beforeSend: function() {
                $(".successNot , .warning_one , .warning_").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                paymentSettings.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    paymentSettings.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    $(".successNot").show();
                } else if (data == '1') {
                    $(".warning_one").show();
                } else if (data == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on('submit', "#adsenseSettings", function(e) {
        e.preventDefault();
        var $form = $(this);
        $.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: $form.serialize(),
            beforeSend: function() {
                $(".successNot , .warning_ , .warning_one").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                $form.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    $form.find(':input[type=submit]').prop('disabled', false);
                }, 800);
                if (data == '200') {
                    $(".successNot").show();
                } else {
                    $(".warning_ , .warning_one").show();
                }
                $(".loaderWrapper").remove();
            },
            error: function() {
                $form.find(':input[type=submit]').prop('disabled', false);
                $(".warning_ , .warning_one").show();
                $(".loaderWrapper").remove();
            }
        });
    });
    function adsResponseSuccess(data){
        if (typeof data !== 'string') { return false; }
        data = $.trim(data);
        if (data === '' || data.toLowerCase() === 'error' || data.toLowerCase().indexOf('invalid csrf') !== -1) { return false; }
        return true;
    }
    $(document).on('submit', "#adsCreatePlacement", function(e) {
        e.preventDefault();
        var $form = $(this);
        $.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: $form.serialize(),
            beforeSend: function() {
                $(".successNot , .warning_ , .warning_one").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                $form.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                $(".loaderWrapper").remove();
                data = $.trim(data);
                $form.find(':input[type=submit]').prop('disabled', false);
                if (data === '200' || data === 'ok' || data === 'exists' || /^\d+$/.test(data) || adsResponseSuccess(data)) {
                    $(".successNot").show();
                    setTimeout(function(){ window.location.reload(); }, 600);
                } else if (data === 'error' || data === '' || data.toLowerCase().indexOf('invalid') !== -1) {
                    $(".warning_ , .warning_one").show();
                } else {
                    $(".successNot").show();
                    setTimeout(function(){ window.location.reload(); }, 600);
                }
            },
            error: function() {
                $(".loaderWrapper").remove();
                $form.find(':input[type=submit]').prop('disabled', false);
                $(".warning_ , .warning_one").show();
            }
        });
    });
    $(document).on('submit', "#adsUpdatePlacement", function(e) {
        e.preventDefault();
        var $form = $(this);
        $.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: $form.serialize(),
            beforeSend: function() {
                $(".successNot , .warning_ , .warning_one").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                $form.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                $(".loaderWrapper").remove();
                data = $.trim(data);
                $form.find(':input[type=submit]').prop('disabled', false);
                if (data === '200' || data === 'ok' || /^\d+$/.test(data) || adsResponseSuccess(data)) {
                    $(".successNot").show();
                } else if (data === 'error' || data === '' || data.toLowerCase().indexOf('invalid') !== -1) {
                    $(".warning_ , .warning_one").show();
                } else { $(".successNot").show(); }
            },
            error: function() {
                $(".loaderWrapper").remove();
                $form.find(':input[type=submit]').prop('disabled', false);
                $(".warning_ , .warning_one").show();
            }
        });
    });
    function adsConfirm(message, onYes, onClose) {
        $(".ads_modal_overlay").remove();
        var modal = '<div class="ads_modal_overlay"><div class="ads_modal_box">';
        modal += '<div class="ads_modal_title">' + message + '</div>';
        modal += '<div class="ads_modal_actions"><button class="i_nex_btn_btn ads_modal_yes">'+(typeof LangYes !== 'undefined' ? LangYes : 'Yes')+'</button>';
        modal += '<button class="ghost_btn ads_modal_no">'+(typeof LangNo !== 'undefined' ? LangNo : 'Cancel')+'</button></div>';
        modal += '</div></div>';
        $("body").append(modal);
        $(document).one('click', '.ads_modal_yes', function() {
            $(".ads_modal_overlay").remove();
            if (typeof onYes === 'function') { onYes(); }
        });
        $(document).one('click', '.ads_modal_no', function() {
            $(".ads_modal_overlay").remove();
            if (typeof onClose === 'function') { onClose(); }
        });
        $(document).one('click', '.ads_modal_overlay', function(e){
            if (e.target.classList.contains('ads_modal_overlay')) {
                $(".ads_modal_overlay").remove();
                if (typeof onClose === 'function') { onClose(); }
            }
        });
    }
    function adsNotice(message, onClose) {
        $(".ads_modal_overlay").remove();
        var modal = '<div class="ads_modal_overlay"><div class="ads_modal_box">';
        modal += '<div class="ads_modal_title">' + message + '</div>';
        modal += '<div class="ads_modal_actions"><button class="i_nex_btn_btn ads_modal_ok">'+(typeof LangOk !== 'undefined' ? LangOk : 'OK')+'</button></div>';
        modal += '</div></div>';
        $("body").append(modal);
        $(document).one('click', '.ads_modal_ok', function() {
            $(".ads_modal_overlay").remove();
            if (typeof onClose === 'function') { onClose(); }
        });
        $(document).one('click', '.ads_modal_overlay', function(e){
            if (e.target.classList.contains('ads_modal_overlay')) {
                $(".ads_modal_overlay").remove();
                if (typeof onClose === 'function') { onClose(); }
            }
        });
    }
    $(document).on('click', ".adsDeletePlacement", function() {
        var placementId = $(this).data('placement');
        var csrf = $('input[name=csrf_token]').first().val();
        if (!placementId) { return; }
        adsConfirm('Delete this placement and its codes?', function(){
            $.post(siteurl + "request/request.php", {f:'ads_delete_placement', placement_id: placementId, csrf_token: csrf}, function(data){
                data = $.trim(data);
                if (data === '200' || data === 'ok' || adsResponseSuccess(data)) {
                    adsNotice('Ad placement deleted.', function(){ window.location.reload(); });
                }
            });
        });
    });
    $(document).on('submit', "#adsCreateCode", function(e) {
        e.preventDefault();
        var $form = $(this);
        $.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: $form.serialize(),
            beforeSend: function() {
                $(".successNot , .warning_ , .warning_one").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                $form.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                $(".loaderWrapper").remove();
                data = $.trim(data);
                $form.find(':input[type=submit]').prop('disabled', false);
                if (data === '200' || data === 'ok' || /^\d+$/.test(data) || adsResponseSuccess(data)) {
                    $(".successNot").show();
                    setTimeout(function(){ window.location.reload(); }, 700);
                } else if (data === 'error' || data === '' || data.toLowerCase().indexOf('invalid') !== -1) {
                    $(".warning_ , .warning_one").show();
                } else { $(".successNot").show(); setTimeout(function(){ window.location.reload(); }, 700); }
            },
            error: function() {
                $(".loaderWrapper").remove();
                $form.find(':input[type=submit]').prop('disabled', false);
                $(".warning_ , .warning_one").show();
            }
        });
    });
    $(document).on('submit', ".ads_code_form", function(e) {
        e.preventDefault();
        var $form = $(this);
        $.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: $form.serialize(),
            beforeSend: function() {
                $(".successNot , .warning_ , .warning_one").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                $form.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                $(".loaderWrapper").remove();
                data = $.trim(data);
                $form.find(':input[type=submit]').prop('disabled', false);
                if (data === '200' || data === 'ok' || /^\d+$/.test(data) || adsResponseSuccess(data)) {
                    $(".successNot").show();
                    setTimeout(function(){ window.location.reload(); }, 500);
                } else if (data === 'error' || data === '' || data.toLowerCase().indexOf('invalid') !== -1) {
                    $(".warning_ , .warning_one").show();
                } else { $(".successNot").show(); setTimeout(function(){ window.location.reload(); }, 500); }
            },
            error: function() {
                $(".loaderWrapper").remove();
                $form.find(':input[type=submit]').prop('disabled', false);
                $(".warning_ , .warning_one").show();
            }
        });
    });
    $(document).on('click', ".adsSetActiveCode", function() {
        var codeId = $(this).data('code');
        var csrf = $(this).closest('form').find('input[name=csrf_token]').val();
        if (!codeId) { return; }
        $.post(siteurl + "request/request.php", {f:'ads_set_active_code', code_id: codeId, csrf_token: csrf}, function(data){
            if (data == '200') { window.location.reload(); } else { alert('Operation failed'); }
        });
    });
    $(document).on('click', ".adsDeleteCode", function() {
        var codeId = $(this).data('code');
        var csrf = $(this).closest('form').find('input[name=csrf_token]').val();
        if (!codeId) { return; }
        adsConfirm('Delete this ad code?', function(){
            $.post(siteurl + "request/request.php", {f:'ads_delete_code', code_id: codeId, csrf_token: csrf}, function(data){
                data = $.trim(data);
                if (data === '200' || data === 'ok' || adsResponseSuccess(data)) {
                    adsNotice('Ad code deleted.', function(){ window.location.reload(); });
                }
            });
        });
    });
    $(document).on('submit', "#updateVerificationStatus", function(e) {
        e.preventDefault();
        var updateVerificationStatus = $("#updateVerificationStatus");
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: updateVerificationStatus.serialize(),
            beforeSend: function() {
                $(".successNot , .warning_one").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                updateVerificationStatus.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    updateVerificationStatus.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    $('.successNot').show();
                } else if (data == '1') {
                    $('.warning_one').show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on('submit', "#editUserDetails", function(e) {
        e.preventDefault();
        var editUserDetails = $("#editUserDetails");
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: editUserDetails.serialize(),
            beforeSend: function() {
                $(".successNot , .warning_").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                editUserDetails.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    editUserDetails.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    $(".successNot").show();
                } else if (data == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    function updateModeratorPermissionPackages() {
        $(".mod_perm_package").each(function() {
            var $package = $(this);
            var packageId = ($package.attr("data-package") || "").toString();
            var $checkboxes = $package.find(".mod_perm_checkbox");
            var $toggleButton = $package.find(".mod_perm_package_toggle");
            var totalCount = $checkboxes.length;
            var selectedCount = $checkboxes.filter(":checked").length;
            if (packageId !== "") {
                $("[data-package-count='" + packageId + "']").text(selectedCount + "/" + totalCount);
            }
            if (totalCount > 0 && selectedCount === totalCount) {
                $toggleButton.addClass("is_full");
            } else {
                $toggleButton.removeClass("is_full");
            }
            if ($toggleButton.length) {
                var allText = ($toggleButton.attr("data-text-all") || "All").toString();
                var noneText = ($toggleButton.attr("data-text-none") || "None").toString();
                if (totalCount > 0 && selectedCount === totalCount) {
                    $toggleButton.text(noneText);
                } else {
                    $toggleButton.text(allText);
                }
            }
            $package.find(".mod_perm_chip").each(function() {
                var $chip = $(this);
                var isChecked = $chip.find(".mod_perm_checkbox").is(":checked");
                $chip.toggleClass("is_checked", isChecked);
            });
        });
    }
    function toggleModeratorPermissionRow() {
        var selectedUserType = ($("#usertype").val() || '').toString();
        if (selectedUserType === '3') {
            $(".moderator_permission_row").show();
        } else {
            $(".moderator_permission_row").hide();
            $(".moderator_permission_row input[type='checkbox']").prop('checked', false);
        }
        updateModeratorPermissionPackages();
    }
    $(function() {
        toggleModeratorPermissionRow();
    });
    $(document).on("change", ".mod_perm_checkbox", function() {
        updateModeratorPermissionPackages();
    });
    $(document).on("click", ".mod_perm_package_toggle", function(e) {
        e.preventDefault();
        var targetPackage = ($(this).attr("data-target-package") || "").toString();
        if (targetPackage === "") {
            return;
        }
        var $targetCheckboxes = $(".mod_perm_checkbox[data-package='" + targetPackage + "']");
        if (!$targetCheckboxes.length) {
            return;
        }
        var selectedCount = $targetCheckboxes.filter(":checked").length;
        var shouldCheckAll = selectedCount !== $targetCheckboxes.length;
        $targetCheckboxes.prop("checked", shouldCheckAll);
        updateModeratorPermissionPackages();
    });
    $(document).on('submit', "#editPointPackage", function(e) {
        e.preventDefault();
        var editPointPackage = $("#editPointPackage");
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: editPointPackage.serialize(),
            beforeSend: function() {
                $(".pk_wraning").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                editPointPackage.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    editPointPackage.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    window.location.href = siteurlRedirect + 'manage_point_packages';
                } else if (data == '404') {
                    $(".pk_wraning").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on('submit', "#customCodes", function(e) {
        e.preventDefault();
        var customCodes = $("#customCodes");
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: customCodes.serialize(),
            beforeSend: function() {
                $(".successNot , .warning_").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                customCodes.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    customCodes.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    $(".successNot").show();
                } else if (data == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on('submit', "#robotsTxtForm", function(e) {
        e.preventDefault();
        var robotsTxtForm = $("#robotsTxtForm");
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: robotsTxtForm.serialize(),
            beforeSend: function() {
                $(".successNot , .warning_").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                robotsTxtForm.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    robotsTxtForm.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    $(".successNot").show();
                } else if (data == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on('submit', "#editPostForm", function(e) {
        e.preventDefault();
        var editPostForm = $("#editPostForm");
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: editPostForm.serialize(),
            beforeSend: function() {
                $(".warning_").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                editPostForm.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    editPostForm.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    window.location.href = siteurlRedirect + 'allPosts';
                } else if (data == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });

    $(document).on('submit', "#approvePostForm", function(e) {
        e.preventDefault();
        var approvePostForm = $("#approvePostForm");
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: approvePostForm.serialize(),
            beforeSend: function() {
                $(".successNot , .warning_").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                approvePostForm.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    approvePostForm.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    $(".successNot").show();
                } else if (data == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on('submit', '#storageSettings', function(e) {
        e.preventDefault();
        var storageSettings = $('#storageSettings');
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: storageSettings.serialize(),
            beforeSend: function() {
                $(".warning_ , .successNot").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                storageSettings.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    storageSettings.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    $(".successNot").show();
                } else if (data == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on('submit', '#myEmailForm', function(e) {
        e.preventDefault();
        var myEmailForm = $('#myEmailForm');
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: myEmailForm.serialize(),
            beforeSend: function() {
                $(".successNot , .warning_one , .warning_").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                myEmailForm.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    myEmailForm.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    $(".successNot").show();
                } else if (data == '1') {
                    $(".warning_one").show();
                } else if (data == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on('submit', '#myProfileForm', function(e) {
        e.preventDefault();
        var generalSettingsForm = $('#myProfileForm');
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: generalSettingsForm.serialize(),
            beforeSend: function() {
                $(".warning_").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                generalSettingsForm.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    generalSettingsForm.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    $(".successNot").show();
                } else if (data == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on('submit', '#creatorBulkSettingsForm', function(e) {
        e.preventDefault();
        var creatorBulkSettingsForm = $('#creatorBulkSettingsForm');
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: creatorBulkSettingsForm.serialize(),
            beforeSend: function() {
                $(".warning_ , .successNot").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                creatorBulkSettingsForm.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    creatorBulkSettingsForm.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    $(".successNot").show();
                } else if (data == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on('submit', '#obsOverlaySettingsForm', function(e) {
        e.preventDefault();
        var obsOverlaySettingsForm = $('#obsOverlaySettingsForm');
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: obsOverlaySettingsForm.serialize(),
            beforeSend: function() {
                $(".warning_ , .successNot").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                obsOverlaySettingsForm.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    obsOverlaySettingsForm.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    $(".successNot").show();
                } else if (data == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on('submit', '#agencyBoostSettingsForm', function(e) {
        e.preventDefault();
        var agencyBoostSettingsForm = $('#agencyBoostSettingsForm');
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: agencyBoostSettingsForm.serialize(),
            beforeSend: function() {
                $(".warning_ , .successNot").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                agencyBoostSettingsForm.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    agencyBoostSettingsForm.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    $(".agencyBoostSettingsSaved").show();
                } else if (data == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on('submit', '#registrationRoleSettingsForm', function(e) {
        e.preventDefault();
        var registrationRoleSettingsForm = $('#registrationRoleSettingsForm');
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: registrationRoleSettingsForm.serialize(),
            beforeSend: function() {
                $(".warning_ , .successNot").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                registrationRoleSettingsForm.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    registrationRoleSettingsForm.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    $(".registrationRoleSettingsSaved").show();
                } else if (data == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on('submit', '#siteBusinessInformation', function(e) {
        e.preventDefault();
        var businessInformation = $('#siteBusinessInformation');
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: businessInformation.serialize(),
            beforeSend: function() {
                $(".warning_one , .successNot").hide();
                $("#business_conf").append(plreLoadingAnimationPlus);
                businessInformation.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    businessInformation.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    $(".successNot").show();
                } else if (data == '1') {
                    $(".warning_one").show();
                } else if (data == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on('submit', '#limits', function(e) {
        e.preventDefault();
        var limits = $('#limits');
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: limits.serialize(),
            beforeSend: function() {
                $(".warning_two , .successNot , .warning_one, .webpushSchemaWarning, .webpushKeysWarning").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                limits.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    limits.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    $(".successNot").show();
                    if (typeof document !== 'undefined') {
                        if (typeof CustomEvent === 'function') {
                            document.dispatchEvent(new CustomEvent('limits:save-success'));
                        } else {
                            var limitsSavedEvent = document.createEvent('Event');
                            limitsSavedEvent.initEvent('limits:save-success', true, true);
                            document.dispatchEvent(limitsSavedEvent);
                        }
                    }
                } else if (data == 'webpush_schema_missing') {
                    $(".webpushSchemaWarning").show();
                } else if (data == 'webpush_keys_missing') {
                    $(".webpushKeysWarning").show();
                } else if (data == 'webpush_subject_invalid' || data == 'webpush_save_failed') {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                } else if (data == '1') {
                    $(".warning_two").show();
                } else if (data == '2') {
                    $(".warning_one").show();
                } else if (data == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on('click', '.webPushGenerateKeys', function(e) {
        e.preventDefault();
        var button = $(this);
        var csrfToken = $('input[name=\"csrf_token\"]').first().val() || '';
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: {
                f: 'webpush_generate_vapid',
                csrf_token: csrfToken
            },
            beforeSend: function() {
                button.prop('disabled', true);
                $(".webpushSchemaWarning, .webpushKeysWarning").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                var payload = null;
                try {
                    payload = (typeof response === 'string') ? JSON.parse(response) : response;
                } catch (err) {
                    payload = null;
                }
                if (payload && payload.status === 'ok') {
                    if (payload.public) {
                        $('#webPushVapidPublic').val(payload.public);
                    }
                    if (payload.private_masked) {
                        $('#webPushVapidPrivateMasked').val(payload.private_masked);
                    }
                    $('body').append('<div class=\"nnauthority\"><div class=\"no_permis flex_ c3 border_one tabing\">' + (payload.message || 'VAPID keys generated.') + '</div></div>');
                    setTimeout(function() {
                        $('.nnauthority').remove();
                    }, 5000);
                } else if (payload && payload.message) {
                    if (payload.code === 'schema_missing') {
                        $(".webpushSchemaWarning").show();
                    } else {
                        $('body').append('<div class=\"nnauthority\"><div class=\"no_permis flex_ c3 border_one tabing\">' + payload.message + '</div></div>');
                        setTimeout(function() {
                            $('.nnauthority').remove();
                        }, 5000);
                    }
                }
            },
            complete: function() {
                button.prop('disabled', false);
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Upload Verification Files*/
    $(document).on("change", "#id_logo", function(e) {
        e.preventDefault();
        
        var values = $("#logo").val();
        var id = $(this).attr("data-id");
        var type = $(this).attr("data-type");
        var data = { f: id, c: type };
    
        $("#lUploadForm").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function() {
                if (type === 'sec_one') {
                    $("#logo").val('');
                    $("#sec_logo").append(uploadLoading);
                } else {
                    $("#favicon").val('');
                    $("#sec_fav").append(uploadLoading);
                }
                $("." + type).hide();
            },
            uploadProgress: function(e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function(response) {
                if (response && response !== "undefined,") {
                    if (type === 'sec_one') {
                        $("#logo").val(response);
                    } else {
                        $("#favicon").val(response);
                    }
                    $("." + type).show();
                }
                $(".i_upload_progress").remove();
            }
        });
     
        $("#lUploadForm").trigger("submit");
    });
    /*Upload Verification Files*/
    $(document).on("change", "#id_fav", function(e) {
        e.preventDefault();
    
        var values = $("#logo").val();
        var id = $(this).attr("data-id");
        var type = $(this).attr("data-type");
        var data = { f: id, c: type };
    
        $("#lfUploadForm").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function() {
                if (type === 'sec_one') {
                    $("#logo").val('');
                    $("#sec_logo").append(uploadLoading);
                } else {
                    $("#favicon").val('');
                    $("#sec_fav").append(uploadLoading);
                }
                $("." + type).hide();
            },
            uploadProgress: function(e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function(response) {
                if (response && response !== "undefined,") {
                    if (type === 'sec_one') {
                        $("#logo").val(response);
                    } else {
                        $("#favicon").val(response);
                    }
                    $("." + type).show();
                }
                $(".i_upload_progress").remove();
            }
        });
     
        $("#lfUploadForm").trigger("submit");
    });
    $(document).on("click", ".i_limit", function() {
        var type = $(this).attr("data-type");
        if (type == 'fl_limit') {
            if (!$(".i_limit_list_container").hasClass("boxactive")) {
                $(".i_limit_list_container").addClass("boxactive");
            } else {
                $(".i_limit_list_container").removeClass("boxactive");
            }
        } else if (type == 'ch_limit') {
            if (!$(".i_limit_list_ch_container").hasClass("boxactive")) {
                $(".i_limit_list_ch_container").addClass("boxactive");
            } else {
                $(".i_limit_list_ch_container").removeClass("boxactive");
            }
        } else if (type == 'cp_limit') {
            if (!$(".i_limit_list_cp_container").hasClass("boxactive")) {
                $(".i_limit_list_cp_container").addClass("boxactive");
            } else {
                $(".i_limit_list_cp_container").removeClass("boxactive");
            }
        }else if (type == 'cpm_limit') {
            if (!$(".i_limit_list_mp_container").hasClass("boxactive")) {
                $(".i_limit_list_mp_container").addClass("boxactive");
            } else {
                $(".i_limit_list_mp_container").removeClass("boxactive");
            }
        } else if (type == 'p_limit') {
            if (!$(".i_limit_list_p_container").hasClass("boxactive")) {
                $(".i_limit_list_p_container").addClass("boxactive");
            } else {
                $(".i_limit_list_p_container").removeClass("boxactive");
            }
        } else if (type == 'chm_limit') {
            if (!$(".i_limit_list_cp_container").hasClass("boxactive")) {
                $(".i_limit_list_cp_container").addClass("boxactive");
            } else {
                $(".i_limit_list_cp_container").removeClass("boxactive");
            }
        } else if (type == 'smtpormail') {
            if (!$(".i_limit_list_cp_container").hasClass("boxactive")) {
                $(".i_limit_list_cp_container").addClass("boxactive");
            } else {
                $(".i_limit_list_cp_container").removeClass("boxactive");
            }
        } else if (type == 'smtp_encription') {
            if (!$(".i_limit_list_ch_container").hasClass("boxactive")) {
                $(".i_limit_list_ch_container").addClass("boxactive");
            } else {
                $(".i_limit_list_ch_container").removeClass("boxactive");
            }
        } else if (type == 's3update') {
            if (!$(".i_limit_list_s3_container").hasClass("boxactive")) {
                $(".i_limit_list_s3_container").addClass("boxactive");
            } else {
                $(".i_limit_list_s3_container").removeClass("boxactive");
            }
        } else if (type == 'verification') {
            if (!$(".i_limit_list_ch_container").hasClass("boxactive")) {
                $(".i_limit_list_ch_container").addClass("boxactive");
            } else {
                $(".i_limit_list_ch_container").removeClass("boxactive");
            }
        } else if (type == 'usertype') {
            if (!$(".i_limit_list_cp_container").hasClass("boxactive")) {
                $(".i_limit_list_cp_container").addClass("boxactive");
            } else {
                $(".i_limit_list_cp_container").removeClass("boxactive");
            }
        } else if (type == 'pl_limit') {
            if (!$(".i_point_sub_list_container").hasClass("boxactive")) {
                $(".i_point_sub_list_container").addClass("boxactive");
            } else {
                $(".i_point_sub_list_container").removeClass("boxactive");
            }
        } else if(type == 'cpa_limit'){
            if (!$(".i_limit_list_cpads_container").hasClass("boxactive")) {
                $(".i_limit_list_cpads_container").addClass("boxactive");
            } else {
                $(".i_limit_list_cpads_container").removeClass("boxactive");
            }
        }else if(type == 'cpu_limit'){
            if (!$(".i_limit_list_cpsugg_container").hasClass("boxactive")) {
                $(".i_limit_list_cpsugg_container").addClass("boxactive");
            } else {
                $(".i_limit_list_cpsugg_container").removeClass("boxactive");
            }
        }else if(type == 'cprod_limit'){
            if (!$(".i_limit_list_cpprod_container").hasClass("boxactive")) {
                $(".i_limit_list_cpprod_container").addClass("boxactive");
            } else {
                $(".i_limit_list_cpprod_container").removeClass("boxactive");
            }
        }else if(type == 'cptrend_limit'){
            var $trendContainer = $(this).closest(".i_box_limit").find(".i_limit_list_cptrend_container");
            if (!$trendContainer.hasClass("boxactive")) {
                $trendContainer.addClass("boxactive");
            } else {
                $trendContainer.removeClass("boxactive");
            }
        }else if(type == 'cptrendhash_limit'){
            var $trendHashContainer = $(this).closest(".i_box_limit").find(".i_limit_list_cptrendhash_container");
            if (!$trendHashContainer.hasClass("boxactive")) {
                $trendHashContainer.addClass("boxactive");
            } else {
                $trendHashContainer.removeClass("boxactive");
            }
        }else if(type == 'cpactivity_limit'){
            if (!$(".i_limit_list_cpfriendactivities_container").hasClass("boxactive")) {
                $(".i_limit_list_cpfriendactivities_container").addClass("boxactive");
            } else {
                $(".i_limit_list_cpfriendactivities_container").removeClass("boxactive");
            }
        }else if(type == 'cpactivityshown_limit'){
            if (!$(".i_limit_list_cpfriendactivitiesshown_container").hasClass("boxactive")) {
                $(".i_limit_list_cpfriendactivitiesshown_container").addClass("boxactive");
            } else {
                $(".i_limit_list_cpfriendactivitiesshown_container").removeClass("boxactive");
            }
        }
    });
    $(document).on("click", ".i_s_limit", function() {
        var newLimit = $(this).attr("id");
        var newLimitText = $(this).attr("data-c");
        var type = $(this).attr("data-type");
        if (type == 'mb_limit') {
            $("#upLimit").val(newLimit);
            $(".lmt").html(newLimitText);
            $(".i_limit_list_container").removeClass("boxactive");
        } else if (type == 'ps_limit') {
            $("#pSLimit").val(newLimit);
            $(".pslmt").html(newLimitText);
            $(".i_point_sub_list_container").removeClass("boxactive");
        } else if (type == 'characterLimit') {
            $("#upcLimit").val(newLimit);
            $(".lct").html(newLimitText);
            $(".i_limit_list_ch_container").removeClass("boxactive");
        } else if (type == 'postLimit') {
            $("#uppLimit").val(newLimit);
            $(".lppt").html(newLimitText);
            $(".i_limit_list_cp_container").removeClass("boxactive");
        } else if (type == 'adsLimit') {
            $("#uppadLimit").val(newLimit);
            $(".lppat").html(newLimitText);
            $(".i_limit_list_cpads_container").removeClass("boxactive");
        } else if (type == 'sugUserLimit') {
            $("#uppsugLimit").val(newLimit);
            $(".lppsug").html(newLimitText);
            $(".i_limit_list_cpsugg_container").removeClass("boxactive");
        } else if (type == 'sugProdLimit') {
            $("#uppprodLimit").val(newLimit);
            $(".lppprod").html(newLimitText);
            $(".i_limit_list_cpprod_container").removeClass("boxactive");
        }else if (type == 'trendLimit') {
            $("#uppTrendLimit").val(newLimit);
            $(".lpptrend").html(newLimitText);
            $(this).closest(".i_box_limit").find(".i_limit_list_cptrend_container").removeClass("boxactive");
        }else if (type == 'trendHashLimit') {
            $("#trendHashLimit").val(newLimit);
            $(".lpptrendhash").html(newLimitText);
            $(this).closest(".i_box_limit").find(".i_limit_list_cptrendhash_container").removeClass("boxactive");
        }else if (type == 'activityLimit') {
            $("#uppFriendAvtivityLimit").val(newLimit);
            $(".lppfractivity").html(newLimitText);
            $(".i_limit_list_cpfriendactivities_container").removeClass("boxactive");
        }else if (type == 'activityshownLimit') {
            $("#uppFriendAvtivitySlownLimit").val(newLimit);
            $(".lppfractivityshown").html(newLimitText);
            $(".i_limit_list_cpfriendactivitiesshown_container").removeClass("boxactive");
        } else if (type == 'pagLimit') {
            $("#ppLimit").val(newLimit);
            $(".ppt").html(newLimitText);
            $(".i_limit_list_p_container").removeClass("boxactive");
        } else if (type == 'language') {
            $("#upcmLimit").val(newLimit);
            $(".lclt").html(newLimitText);
            $(".i_limit_list_cp_container").removeClass("boxactive");
        } else if (type == 'smtpOrMail') {
            $("#smtp_or_mail").val(newLimit);
            $(".sm_or_ma").html(newLimitText);
            $(".i_limit_list_cp_container").removeClass("boxactive");
        } else if (type == 'smtpEncryption') {
            $("#smtp_encription").val(newLimit);
            $(".ssl_or_tls").html(newLimitText);
            $(".i_limit_list_ch_container").removeClass("boxactive");
        } else if (type == 's3set') {
            $("#s3region").val(newLimit);
            $(".s3choosed").html(newLimitText);
            $(".i_limit_list_s3_container").removeClass("boxactive");
        }else if(type == 'wasSet'){
            $("#wasregion").val(newLimit);
            $(".wasChoosed").html(newLimitText);
            $(".i_limit_list_s3_container").removeClass("boxactive");
        } else if (type == 'verfUser') {
            $("#verification").val(newLimit);
            $(".lct").html(newLimitText);
            $(".i_limit_list_ch_container").removeClass("boxactive");
        } else if (type == 'usrtyp') {
            $("#usertype").val(newLimit);
            $(".lut").html(newLimitText);
            $(".i_limit_list_cp_container").removeClass("boxactive");
            toggleModeratorPermissionRow();
        } else if (type == 'chooseAnnouncementType') {
            $("#upcLimit").val(newLimit);
            $(".lct").html(newLimitText);
            $(".i_limit_list_ch_container").removeClass("boxactive");
        }else if (type == 'postMLimit') {
            $("#uppmLimit").val(newLimit);
            $(".lpptm").html(newLimitText);
            $(".i_limit_list_mp_container").removeClass("boxactive");
        }
    });
    /*Change Default Language*/
    $(document).on("click", ".setDefault", function() {
        var type = 'updateDefaultLang';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&lang=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    $(".up_lng").show();
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
                setTimeout(() => {
                    $(".up_lng").hide();
                }, 5000);
            }
        });
    });
    /*Change Default Theme Style*/
    $(document).on("change", "#default_style_mode", function() {
        var type = 'default_style_mode';
        var value = $(this).val();
        var data = 'f=' + type + '&mod=' + encodeURIComponent(value);
        var csrf = $('input[name=csrf_token]').first().val();
        if (csrf) {
            data += '&csrf_token=' + encodeURIComponent(csrf);
        }
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    $(".default_style_mode").show();
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
                setTimeout(() => {
                    $(".default_style_mode").hide();
                }, 5000);
            }
        });
    });
    /*Change Module Statuses*/
    $(document).on("change", ".chmd", function() {
        var type = $(this).attr("id");
        var value = $(this).val();
        var data = 'f=' + type + '&mod=' + value;
        var csrf = $('input[name=csrf_token]').first().val();
        if (csrf) {
            data += '&csrf_token=' + encodeURIComponent(csrf);
        }
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    if (value == '1') {
                        $("#" + type).val('0');
                    } else {
                        $("#" + type).val('1');
                    }
                    if (type === 'epoch_postback_enabled') {
                        $("#epoch_postback_enabled_hidden").val($("#epoch_postback_enabled").is(":checked") ? '1' : '0');
                    }
                    $("." + type).show();
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
                setTimeout(() => {
                    $("." + type).hide();
                }, 5000);
            }
        });
    });
    $(document).on("change", ".sstat", function() {
        var value = $(this).val();
        if (value == '1') {
            $("#sstat").val('0');
            $("#stats3").val('0');
        } else {
            $("#stats3").val('1');
            $("#sstat").val('1');
        }
    });
    $(document).on("change", ".sfstat", function() {
        var value = $(this).val();
        if (value == '1') {
            $("#sfstat").val('0');
            $("#sftats3").val('0');
        } else {
            $("#sftats3").val('1');
            $("#sfstat").val('1');
        }
    });
    $(document).on("change", ".spstat", function() {
        var value = $(this).val();
        if (value == '1') {
            $("#spstat").val('0');
            $("#sptats3").val('0');
        } else {
            $("#sptats3").val('1');
            $("#spstat").val('1');
        }
    });
    $(document).on("change", ".lpstat", function() {
        var value = $(this).val();
        if (value == '1') {
            $("#lpstat").val('0');
            $("#livePollStatusValue").val('0');
        } else {
            $("#livePollStatusValue").val('1');
            $("#lpstat").val('1');
        }
    });
    $(document).on("change", ".lgstat", function() {
        var value = $(this).val();
        if (value == '1') {
            $("#lgstat").val('0');
            $("#liveGiftStatusValue").val('0');
        } else {
            $("#liveGiftStatusValue").val('1');
            $("#lgstat").val('1');
        }
    });
    $(document).on("change", ".lqstat", function() {
        var value = $(this).val();
        if (value == '1') {
            $("#lqstat").val('0');
            $("#liveQAStatusValue").val('0');
        } else {
            $("#liveQAStatusValue").val('1');
            $("#lqstat").val('1');
        }
    });
    $(document).on("change", ".lcstat", function() {
        var value = $(this).val();
        if (value == '1') {
            $("#lcstat").val('0');
            $("#liveChatStatusValue").val('0');
        } else {
            $("#liveChatStatusValue").val('1');
            $("#lcstat").val('1');
        }
    });
    /*Select Approve / Decline / Rejected */
    $(document).on("click", ".approve_ch_item", function() {
        var ID = $(this).attr("data-val");
        $(".approve_ch_item").removeClass("choosed");
        $("#appr_" + ID).addClass("choosed");
        $("#approve_type").val(ID);
    });
    /*Delete Premium Post From aWaiting Post Page*/
    $(document).on("click", ".delete_post", function() {
        var type = 'deletePost';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".i_modal_g_footer").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == 200) {
                    location.reload();
                } else if (response == 404) {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Scheduled posts actions*/
    function handleScheduledAction(postId, action) {
        if (!postId || !action) { return; }
        var token = $("#general_conf").data("scheduled-token") || '';
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: { f: 'scheduled_post_action', action: action, post_id: postId, csrf_token: token },
            cache: false,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                $(".loaderWrapper").remove();
                if (response == '200') {
                    location.reload();
                } else {
                    PopUPAlerts('sWrong', 'ialert');
                }
            },
            error: function() {
                $(".loaderWrapper").remove();
                PopUPAlerts('sWrong', 'ialert');
            }
        });
    }
    $(document).on("click", ".publishScheduledNow", function() {
        handleScheduledAction($(this).data("post"), 'publish');
    });
    $(document).on("click", ".cancelScheduledPost", function() {
        handleScheduledAction($(this).data("post"), 'cancel');
    });
    $(document).on("click", ".deleteScheduledPost", function() {
        handleScheduledAction($(this).data("post"), 'delete');
    });
    /*Call Delete Post PopUpBox*/
    $(document).on("click", ".delp", function() {
        var type = 'ddelPost';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
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
                }
            }
        });
    });

    $(document).on("click", ".shareClose , .no-del", function() {
        $(".i_modal_in_in , .i_modal_display_in_picker").addClass("i_modal_in_in_out");
        setTimeout(() => {
            $(".i_modal_bg_in , .i_modal_bg_colorpicker_in").remove();
        }, 200);
    });
    /*Sitemap Preview PopUP*/
    $(document).on("click", ".showSitemapPopup", function() {
        var type = 'sitemapPreview';
        var data = 'f=' + type;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Call SVG Edit PopUP*/
    $(document).on("click", ".editSvgIcon", function() {
        var type = 'editSVGPopUp';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&svg=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $("#svg_id_" + ID).append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Change SVG Status*/
    $(document).on("change", ".iaStat", function() {
        var type = 'iconSVGStatus';
        var value = $(this).val();
        var ID = $(this).attr("data-id");
        var data = 'f=' + type + '&mod=' + value + '&svg=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    if (value == '1') {
                        $("#" + type).val('0');
                    } else {
                        $("#" + type).val('1');
                    }
                    $("." + type).show();
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
                setTimeout(() => {
                    $("." + type).hide();
                }, 5000);
            }
        });
    });
    /*Call SVG Edit PopUP*/
    $(document).on("click", ".newCreate", function() {
        var type = $(this).attr("data-type");
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&svg=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $("#svg_id_" + ID).append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on("change", ".pstat", function() {
        var value = $(this).val();
        if (value == '1') {
            $("#plnststs").val('0');
        } else {
            $("#plnststs").val('1');
        }
    });
    /*Change Module Statuses*/
    $(document).on("change", ".pstat", function() {
        var type = $(this).attr("data-type");
        var ID = $(this).attr("data-id");
        var value = $(this).val();
        var data = 'f=' + type + '&mod=' + value + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    if (value == '1') {
                        $("#" + type + ID).val('0');
                    } else {
                        $("#" + type + ID).val('1');
                    }
                    $("." + type).show();
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
                setTimeout(() => {
                    $("." + type).hide();
                }, 5000);
            }
        });
    });
    /*Call Delete Post PopUpBox*/
    $(document).on("click", ".delete_plan", function() {
        var type = 'ddelPlan';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
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
                }
            }
        });
    });
    /*Delete Plan */
    $(document).on("click", ".del__plan", function() {
        var type = 'deleteThisPlan';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    location.reload();
                } else {
                    $(".warning_").show();
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Change Module Statuses*/
    $(document).on("change", ".uplan", function() {
        var type = $(this).attr("data-type");
        var ID = $(this).attr("data-id");
        var value = $(this).val();
        var data = 'f=' + type + '&mod=' + value + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    if (value == '1') {
                        $("#" + type + ID).val('0');
                    } else {
                        $("#" + type + ID).val('1');
                    }
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Change Module Statuses*/
    $(document).on("change", ".upStick", function() {
        var type = $(this).attr("data-type");
        var ID = $(this).attr("data-id");
        var value = $(this).val();
        var data = 'f=' + type + '&mod=' + value + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    if (value == '1') {
                        $("#" + type + ID).val('0');
                    } else {
                        $("#" + type + ID).val('1');
                    }
                    $(".upStick" + ID).show();
                    setTimeout(() => {
                        $(".upStick" + ID).hide();
                    }, 5000);
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Call Delete Post PopUpBox*/
    $(document).on("click", ".edit_lang", function() {
        var type = 'editLanguage';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
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
                }
            }
        });
    });
    /*Call Delete Language PopUpBox*/
    $(document).on("click", ".del_lang", function() {
        var type = 'delLang';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
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
                }
            }
        });
    });
    /*Delete Premium Post From aWaiting Post Page*/
    $(document).on("click", ".delete_lng", function() {
        var type = 'deleteThisLanguage';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    location.reload();
                } else {
                    $(".warning_").show();
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Call Delete User PopUp*/
    $(document).on("click", ".del_us", function() {
        var type = 'deleteUser';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
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
                }
            }
        });
    });
    /*Delete User*/
    $(document).on("click", ".delete_usr", function() {
        var type = 'deleteUser';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 200);
                if (response == '200') {
                    location.reload();
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Call Delete User Verification Request PopUp*/
    $(document).on("click", ".del_verf", function() {
        var type = 'deleteUserVerification';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
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
                }
            }
        });
    });
    /*Delete User*/
    $(document).on("click", ".delete_verf", function() {
        var type = 'deleteUserVerification';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 200);
                if (response == '200') {
                    location.reload();
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Call Delete User Verification Request PopUp*/
    $(document).on("click", ".delpage", function() {
        var type = 'ddelPage';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
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
                }
            }
        });
    });
    /*Call Delete User Verification Request PopUp*/
    $(document).on("click", ".delqa", function() {
        var type = 'ddelQA';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
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
                }
            }
        });
    });
    /*Delete Premium Post From aWaiting Post Page*/
    $(document).on("click", ".delete_page", function() {
        var type = 'deletePage';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    location.reload();
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Delete Premium Post From aWaiting Post Page*/
    $(document).on("click", ".delete_qa", function() {
        var type = 'deleteQA';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    location.reload();
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Call Delete User Verification Request PopUp*/
    $(document).on("click", ".edStick", function() {
        var type = 'editStickerUrl';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&sid=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
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
                }
            }
        });
    });
    /*Call Delete User PopUp*/
    $(document).on("click", ".del_stick", function() {
        var type = 'deleteSticker';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
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
                }
            }
        });
    });
    /*Call Delete User Verification Request PopUp*/
    $(document).on("click", ".addNewSticker", function() {
        var type = 'addNewStickerUrl';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&sid=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
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
                }
            }
        });
    });
    /*Change Module Statuses*/
    $(document).on("change", ".chmdPayment", function() {
        var type = $(this).attr("id");
        var value = $(this).val();
        var data = 'f=' + type + '&mod=' + value;
        var csrf = $('input[name=csrf_token]').first().val();
        if (csrf) {
            data += '&csrf_token=' + encodeURIComponent(csrf);
        }
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    if (value == '1') {
                        $("#" + type).val('0');
                    } else {
                        $("#" + type).val('1');
                    }
                    $("." + type).show();
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
                setTimeout(() => {
                    $("." + type).hide();
                }, 5000);
            }
        });
    });
    /*Change Module Statuses*/
    $(document).on("change", ".slog", function() {
        var type = $(this).attr("data-type");
        var value = $(this).val();

        if (value == '1') {
            $("#" + type + '_status').val('0');
            $("#" + type + '_statuss').val('0');
        } else if (value == '0') {
            $("#" + type + '_statuss').val('1');
            $("#" + type + '_status').val('1');
        }

    });
    /*Mark as Paid*/
    $(document).on("click", ".mark_as_paid", function() {
        var type = 'paid';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    window.location.reload();
                } else {
                    $(".warning_").show();
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Call Delete User Verification Request PopUp*/
    $(document).on("click", ".decline_this", function() {
        var type = 'declineSure';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&did=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
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
                }
            }
        });
    });
    /*Yes Decline Request*/
    $(document).on("click", ".yesDecline", function() {
        var type = 'yesDecline';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    location.reload();
                } else {
                    $(".warning_").show();
                }
                $(".loaderWrapper").remove();
            }
        });
    });

    /*Call Delete User PopUp*/
    $(document).on("click", ".del_upout", function() {
        var type = 'deletePayout';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
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
                }
            }
        });
    });
    /*Call Delete Product PopUP*/
    $(document).on("click", ".del_ProdPopUP", function() {
        var type = 'deleteProduct';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
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
                }
            }
        });
    });
    $(document).on("click", ".delete_pryt", function() {
        var type = 'deleteProductt';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 200);
                if (response == '200') {
                    location.reload();
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Delete User*/
    $(document).on("click", ".delete_pyt", function() {
        var type = 'deletePayoutt';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 200);
                if (response == '200') {
                    location.reload();
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on("click", ".clps", function() {
        if (!$(".i_admin_left").hasClass("open_left_menu")) {
            $(".i_admin_left").addClass("open_left_menu");
        } else {
            $(".i_admin_left").removeClass("open_left_menu");
        }
    });
    /*Upload Verification Files*/
    $(document).on("change", "#ad_image", function(e) {
        e.preventDefault();
    
        var values = $("#adsFile").val();
        var id = $(this).attr("data-id");
        var type = $(this).attr("data-type");
        var data = { f: id, c: type };
    
        $("#adsUploadForm").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function() {
                $("#adsFile").val('');
                $("#sec_logo").append(uploadLoading);
                $("." + type).hide();
            },
            uploadProgress: function(e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function(response) {
                if (response && response !== "undefined,") {
                    $("#adsFile").val(response);
                    $("." + type).show();
                }
                $(".i_upload_progress").remove();
            } 
        });
     
        $("#adsUploadForm").trigger("submit");
    });

    $(document).on('submit', "#adsDForm", function(e) {
        e.preventDefault();
        var adsDForm = $(this);
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: adsDForm.serialize(),
            beforeSend: function() {
                $(".successNot , .warning_one , .warning_two , .ppk_wraning , .warning_tree").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                adsDForm.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    adsDForm.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    $(".successNot").show();
                } else if (data == '2') {
                    $(".warning_one").show();
                } else if (data == '3') {
                    $(".warning_two").show();
                } else if (data == '1') {
                    $(".ppk_wraning").show();
                } else if (data == '4') {
                    $(".warning_tree").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Change Ads Status*/
    $(document).on("change", ".adsStat", function() {
        var type = $(this).attr("data-type");
        var ID = $(this).attr("data-id");
        var value = $(this).val();
        var data = 'f=' + type + '&mod=' + value + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $(".plbox" + ID).append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    if (value == '1') {
                        $("#" + type + ID).val('0');
                    } else {
                        $("#" + type + ID).val('1');
                    }
                    $("." + type).show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
                setTimeout(() => {
                    $("." + type).hide();
                }, 5000);
            }
        });
    });

    $(document).on('submit', "#adsUForm", function(e) {
        e.preventDefault();
        var adsUForm = $(this);
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: adsUForm.serialize(),
            beforeSend: function() {
                $(".successNot , .warning_one , .warning_two , .ppk_wraning , .warning_tree").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                adsUForm.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    adsUForm.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    $(".successNot").show();
                } else if (data == '2') {
                    $(".warning_one").show();
                } else if (data == '3') {
                    $(".warning_two").show();
                } else if (data == '1') {
                    $(".ppk_wraning").show();
                } else if (data == '4') {
                    $(".warning_tree").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Call Delete Post PopUpBox*/
    $(document).on("click", ".delete_ads", function() {
        var type = 'ddelAds';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
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
                }
            }
        });
    });
    /*Delete Plan */
    $(document).on("click", ".del__ads", function() {
        var type = 'deleteThisAds';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    location.reload();
                } else {
                    $(".warning_").show();
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Delete Sticker*/
    $(document).on("click", ".delete_sticker", function() {
        var type = 'deleteSticker';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 200);
                if (response == '200') {
                    location.reload();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on('submit', "#updateSubsPaymentGataway", function(e) {
        e.preventDefault();
        var updateSubGateway = $("#updateSubsPaymentGataway");
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: updateSubGateway.serialize(),
            beforeSend: function() {
                $(".successNot , .warning_").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                updateSubGateway.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    updateSubGateway.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    $(".successNot").show();
                } else if (data == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Change Module Statuses*/
    $(document).on("change", ".chmdSubPayment", function() {
        var $input = $(this);
        var type = $input.attr("id");
        var isChecked = $input.is(":checked");
        var activeValue = $input.data("activeValue");
        var inactiveValue = $input.data("inactiveValue");
        var sendValue = isChecked ? (activeValue !== undefined ? String(activeValue) : "1") : (inactiveValue !== undefined ? String(inactiveValue) : "0");
        var data = 'f=' + type + '&mod=' + sendValue;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    var nextValue = isChecked ? (inactiveValue !== undefined ? String(inactiveValue) : "0") : (activeValue !== undefined ? String(activeValue) : "1");
                    $input.val(nextValue);
                    $("." + type).show();
                } else if (response == '404') {
                    $input.prop("checked", !isChecked);
                    $(".warning_").show();
                } else {
                    $input.prop("checked", !isChecked);
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
                setTimeout(() => {
                    $("." + type).hide();
                }, 5000);
            }
        });
    });
    $(document).on('submit', "#updateGiphy", function(e) {
        e.preventDefault();
        var updateGiphy = $("#updateGiphy");
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: updateGiphy.serialize(),
            beforeSend: function() {
                $(".successNot , .warning_").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                updateGiphy.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    updateGiphy.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    $(".successNot").show();
                } else if (data == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on('submit', "#updateAiCredit", function(e) {
        e.preventDefault();
        var updateAiCredit = $("#updateAiCredit");
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: updateAiCredit.serialize(),
            beforeSend: function() {
                $(".successNot , .warning_").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                updateAiCredit.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    updateAiCredit.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    $(".successNot").show();
                } else if (data == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on('click', "#aiHealthCheckBtn", function(e) {
        e.preventDefault();
        var updateAiCredit = $("#updateAiCredit");
        var csrf = updateAiCredit.find('input[name=csrf_token]').val();
        var apiKey = updateAiCredit.find('input[name=apiKey]').val();
        var model = updateAiCredit.find('select[name=ai_model]').val();
        var resultBox = updateAiCredit.find('.ai_health_result');
        var checkingText = $(this).data('checking') || '';
        var errorText = $(this).data('error') || '';
        if (checkingText !== '') {
            resultBox.text(checkingText).removeClass('nonePoint').show();
        }
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: {
                f: 'testAiHealthCheck',
                apiKey: apiKey,
                ai_model: model,
                csrf_token: csrf
            },
            success: function(data) {
                var response = null;
                if (typeof data === 'object' && data !== null) {
                    response = data;
                } else {
                    try {
                        response = JSON.parse(data);
                    } catch (e) {
                        response = {status: 'error', message: data};
                    }
                }
                if (response && typeof response.message === 'object' && response.message !== null) {
                    response.message = response.message.message || JSON.stringify(response.message);
                }
                if (response && response.status === 'ok') {
                    resultBox.text(response.message || '').removeClass('nonePoint').show();
                } else {
                    resultBox.text(response.message || errorText).removeClass('nonePoint').show();
                }
            },
            error: function() {
                if (errorText !== '') {
                    resultBox.text(errorText).removeClass('nonePoint').show();
                }
            }
        });
    });
    $(document).on('submit', '#liveSettings', function(e) {
        e.preventDefault();
        var liveStreamForm = $('#liveSettings');
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: liveStreamForm.serialize(),
            beforeSend: function() {
                $(".successNot , .warning_one , .warning_").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                liveStreamForm.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    liveStreamForm.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    $(".successNot").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Change Module Statuses*/
    $(document).on("change", "input:radio[name='mTheme']", function() {
        var type = 'updateMainPage';
        var ID = $(this).val();
        var planBox = $(this).closest('.plan_box');
        var data = 'f=' + type + '&tm=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                if (planBox.length) {
                    planBox.append(plreLoadingAnimationPlus);
                }
            },
            success: function(response) {
                if (response == '200') {

                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Upload Verification Files*/
    $(document).on("change", "#id_landing_first", function(e) {
        e.preventDefault();
    
        var values = $("#logo").val();
        var id = $(this).attr("data-id");
        var type = $(this).attr("data-type");
        var data = { f: id, c: type };
    
        $("#lUploadForm").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function() {
                $("#sec_one").append(uploadLoading);
                $("." + type).hide();
            },
            uploadProgress: function(e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function(response) {
                if (response && response !== "undefined,") {
                    if (type === 'sec_one') {
                        $("#logo").val(response);
                    } else {
                        $("#favicon").val(response);
                    }
                    $("." + type).show();
                }
                $(".i_upload_progress").remove();
            }
        });
     
        $("#lUploadForm").trigger("submit");
    });
    /*Upload Verification Files*/
    $(document).on("change", "#id_landing_second", function(e) {
        e.preventDefault();
    
        var values = $("#logo").val();
        var id = $(this).attr("data-id");
        var type = $(this).attr("data-type");
        var data = { f: id, c: type };
    
        $("#lsUploadForm").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function() {
                $("#sec_two").append(uploadLoading);
                $("." + type).hide();
            },
            uploadProgress: function(e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function(response) {
                if (response && response !== "undefined,") {
                    if (type === 'sec_two') {
                        $("#logo").val(response);
                    } else {
                        $("#favicon").val(response);
                    }
                    $("." + type).show();
                }
                $(".i_upload_progress").remove();
            } 
        });
     
        $("#lsUploadForm").trigger("submit");
    });
    /*Upload Verification Files*/
    $(document).on("change", "#id_landing_thirth", function(e) {
        e.preventDefault();
    
        var values = $("#logo").val();
        var id = $(this).attr("data-id");
        var type = $(this).attr("data-type");
        var data = { f: id, c: type };
    
        $("#ltUploadForm").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function() {
                $("#sec_three").append(uploadLoading);
                $("." + type).hide();
            },
            uploadProgress: function(e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function(response) {
                if (response && response !== "undefined,") {
                    if (type === 'sec_three') {
                        $("#logo").val(response);
                    } else {
                        $("#favicon").val(response);
                    }
                    $("." + type).show();
                }
                $(".i_upload_progress").remove();
            } 
        });
     
        $("#ltUploadForm").trigger("submit");
    });
    /*Upload Verification Files*/
    $(document).on("change", "#id_landing_four", function(e) {
        e.preventDefault();
    
        var values = $("#logo").val();
        var id = $(this).attr("data-id");
        var type = $(this).attr("data-type");
        var data = { f: id, c: type };
    
        $("#lfUploadForm").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function() {
                $("#sec_four").append(uploadLoading);
                $("." + type).hide();
            },
            uploadProgress: function(e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function(response) {
                if (response && response !== "undefined,") {
                    if (type === 'sec_four') {
                        $("#logo").val(response);
                    } else {
                        $("#favicon").val(response);
                    }
                    $("." + type).show();
                }
                $(".i_upload_progress").remove();
            }
        });
     
        $("#lfUploadForm").trigger("submit");
    });
    /*Upload Verification Files*/
    $(document).on("change", "#id_landing_five", function(e) {
        e.preventDefault();
        
        var values = $("#logo").val();
        var id = $(this).attr("data-id");
        var type = $(this).attr("data-type");
        var data = { f: id, c: type };
    
        $("#lfiUploadForm").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function() {
                $("#sec_five").append(uploadLoading);
                $("." + type).hide();
            },
            uploadProgress: function(e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function(response) {
                if (response && response !== "undefined,") {
                    if (type === 'sec_five') {
                        $("#logo").val(response);
                    } else {
                        $("#favicon").val(response);
                    }
                    $("." + type).show();
                }
                $(".i_upload_progress").remove();
            }
        });
    
        // Modern trigger usage instead of .submit()
        $("#lfiUploadForm").trigger("submit");
    });
    /*Upload Verification Files*/
    $(document).on("change", "#id_landing_six", function(e) {
        e.preventDefault();
    
        var values = $("#logo").val();
        var id = $(this).attr("data-id");
        var type = $(this).attr("data-type");
        var data = { f: id, c: type };
    
        $("#lsiUploadForm").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function() {
                $("#sec_six").append(uploadLoading);
                $("." + type).hide();
            },
            uploadProgress: function(e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function(response) {
                if (response && response !== "undefined,") {
                    if (type === 'sec_six') {
                        $("#logo").val(response);
                    } else {
                        $("#favicon").val(response);
                    }
                    $("." + type).show();
                }
                $(".i_upload_progress").remove();
            }
        });
     
        $("#lsiUploadForm").trigger("submit");
    });
    /*Upload Verification Files*/
    $(document).on("change", "#id_landing_seventh", function(e) {
        e.preventDefault();
    
        var values = $("#logo").val();
        var id = $(this).attr("data-id");
        var type = $(this).attr("data-type");
        var data = { f: id, c: type };
    
        $("#lsevUploadForm").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function() {
                $("#sec_seventh").append(uploadLoading);
                $("." + type).hide();
            },
            uploadProgress: function(e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function(response) {
                if (response && response !== "undefined,") {
                    if (type === 'sec_seventh') {
                        $("#logo").val(response);
                    } else {
                        $("#favicon").val(response);
                    }
                    $("." + type).show();
                }
                $(".i_upload_progress").remove();
            }
        });
     
        $("#lsevUploadForm").trigger("submit");
    });
    /*Upload Verification Files*/
    $(document).on("change", "#id_landing_bg", function(e) {
        e.preventDefault();
    
        var values = $("#logo").val();
        var id = $(this).attr("data-id");
        var type = $(this).attr("data-type");
        var data = { f: id, c: type };
    
        $("#bgUploadForm").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function() {
                $("#sec_bg").append(uploadLoading);
                $("." + type).hide();
            },
            uploadProgress: function(e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function(response) {
                if (response && response !== "undefined,") {
                    if (type === 'sec_bg') {
                        $("#logo").val(response);
                    } else {
                        $("#favicon").val(response);
                    }
                    $("." + type).show();
                }
                $(".i_upload_progress").remove();
            } 
        });
     
        $("#bgUploadForm").trigger("submit");
    });
    /*Upload Verification Files*/
    $(document).on("change", "#id_landing_frnt", function(e) {
        e.preventDefault();
    
        var values = $("#logo").val();
        var id = $(this).attr("data-id");
        var type = $(this).attr("data-type");
        var data = { f: id, c: type };
    
        $("#frntUploadForm").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function() {
                $("#sec_frnt").append(uploadLoading);
                $("." + type).hide();
            },
            uploadProgress: function(e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function(response) {
                if (response && response !== "undefined,") {
                    if (type === 'sec_frnt') {
                        $("#logo").val(response);
                    } else {
                        $("#favicon").val(response);
                    }
                    $("." + type).show();
                }
                $(".i_upload_progress").remove();
            } 
        });
     
        $("#frntUploadForm").trigger("submit");
    });
    /*Call Delete User Verification Request PopUp*/
    $(document).on("click", ".editQA", function() {
        var type = 'editQuestionAnswer';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&sid=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
            data: data,
            cache: false, 
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                }
            }
        });
    });
    $(document).on('submit', "#updateSubsPaymentGatawayCCBILL", function(e) {
        e.preventDefault();
        var updateSubGateway = $("#updateSubsPaymentGatawayCCBILL");
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: updateSubGateway.serialize(),
            beforeSend: function() {
                $(".successNot , .warning_").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                updateSubGateway.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    updateSubGateway.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    $(".successNot").show();
                } else if (data == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Call Delete User PopUp*/
    $(document).on("click", ".show_withdraw_details", function() {
        var type = 'showWithdrawDetails';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
            data: data,
            cache: false, 
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                }
            }
        });
    });
    /*Change Module Statuses*/
    $(document).on("change", ".chmdPost", function() {
        var type = $(this).attr("id");
        var value = $(this).val();
        var data = 'f=' + type + '&mod=' + value;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) { 
                if (response == '200') {
                    if (value == 'yes') {
                        $("#" + type).val('no');
                        $("." + type).val('no');
                    } else {
                        $("." + type).val('yes');
                        $("#" + type).val('yes');
                    }
                    $("." + type).show();
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
                setTimeout(() => {
                    $("." + type).hide();
                }, 5000);
            }
        });
    });
    /*Call Delete Post PopUpBox*/
    $(document).on("click", ".delq", function() {
        var type = 'ddelQuest';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
            data: data,
            cache: false, 
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                }
            }
        });
    });
    /*Delete Question*/
    $(document).on("click", ".delete_quest", function() {
        var type = 'deleteQuest';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    location.reload();
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Call Delete User PopUp*/
    $(document).on("click", ".show_question_details", function() {
        var type = 'showQuestionDetails';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
            data: data,
            cache: false, 
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                }
            }
        });
    });

    /*Change Module Statuses*/
    $(document).on("change", ".chmdquestion", function() {
        var type = 'questionAnswerStatus';
        var value = $(this).val();
        var qID = $(this).attr("data-id");
        var data = 'f=' + type + '&mod=' + value + '&qid=' + qID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    if (value == '1') {
                        $(".q" + qID).val('0');
                    } else {
                        $(".q" + qID).val('1');
                    }
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
                setTimeout(() => {
                    $(".q" + qID).hide();
                }, 5000);
            }
        });
    });
    /*Call Delete Post PopUpBox*/
    $(document).on("click", ".delr", function() {
        var type = 'ddelReportP';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
            data: data,
            cache: false, 
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                }
            }
        });
    });
    /*Delete Question*/
    $(document).on("click", ".delete_report", function() {
        var type = 'deleteReport';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    location.reload();
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Change Module Statuses*/
    $(document).on("change", ".rchmdReport", function() {
        var type = 'rCheckStatus';
        var value = $(this).val();
        var qID = $(this).attr("data-id");
        var data = 'f=' + type + '&mod=' + value + '&rid=' + qID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    if (value == '1') {
                        $(".q" + qID).val('0');
                    } else {
                        $(".q" + qID).val('1');
                    }
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
                setTimeout(() => {
                    $(".q" + qID).hide();
                }, 5000);
            }
        });
    });
    /*Change Module Statuses*/
    $(document).on("change", ".rcchmdReport", function() {
        var type = 'rcCheckStatus';
        var value = $(this).val();
        var qID = $(this).attr("data-id");
        var data = 'f=' + type + '&mod=' + value + '&rid=' + qID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    if (value == '1') {
                        $(".q" + qID).val('0');
                    } else {
                        $(".q" + qID).val('1');
                    }
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
                setTimeout(() => {
                    $(".q" + qID).hide();
                }, 5000);
            }
        });
    });
    /*Call Delete Post PopUpBox*/
    $(document).on("click", ".delrc", function() {
        var type = 'ddelReportC';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
            data: data,
            cache: false, 
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                }
            }
        });
    });
    /*Delete Question*/
    $(document).on("click", ".delete_report_c", function() {
        var type = 'deleteCReport';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    location.reload();
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Open check note popup before marking report as checked*/
    $(document).on("click", ".rmchmdReport", function(e) {
        var value = $(this).val();
        if (value !== '1') {
            return;
        }
        e.preventDefault();
        var ID = $(this).attr("data-id");
        var existingNote = $(this).attr("data-admin-note") || '';
        var data = 'f=rmCheckNotePopup&id=' + ID + '&note=' + encodeURIComponent(existingNote);
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
            data: data,
            cache: false,
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                }
            }
        });
    });
    /*Change Message Report Status (uncheck direct)*/
    $(document).on("change", ".rmchmdReport", function() {
        var type = 'rmCheckStatus';
        var value = $(this).val();
        var qID = $(this).attr("data-id");
        var checkbox = $(this);
        if (value !== '0') {
            return;
        }
        var csrfToken = $("#rmReportsCsrf").val() || (typeof window.csrfToken !== 'undefined' ? window.csrfToken : '');
        var data = 'f=' + type + '&mod=' + value + '&rid=' + qID + '&note=';
        if (csrfToken) {
            data += '&csrf_token=' + encodeURIComponent(csrfToken);
        }
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    $("#rmCheckStatus" + qID).val('1').prop('checked', false);
                    $(".rm_row" + qID).removeClass('rm_row_checked');
                } else if (response == '404') {
                    checkbox.prop('checked', true).val('0');
                    $(".warning_").show();
                } else {
                    checkbox.prop('checked', true).val('0');
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Confirm check with note*/
    $(document).on("click", ".check_report_m_submit", function() {
        var type = 'rmCheckStatus';
        var qID = $(this).attr("data-id");
        var note = $("#rmCheckNoteText" + qID).val() || "";
        var csrfToken = $("#rmReportsCsrf").val() || (typeof window.csrfToken !== 'undefined' ? window.csrfToken : '');
        var data = 'f=' + type + '&mod=1&rid=' + qID + '&note=' + encodeURIComponent(note);
        if (csrfToken) {
            data += '&csrf_token=' + encodeURIComponent(csrfToken);
        }
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    $("#rmCheckStatus" + qID).val('0').prop('checked', true).attr('data-admin-note', note);
                    $(".rm_row" + qID).addClass('rm_row_checked');
                    $(".i_modal_bg_in").removeClass('i_modal_display_in');
                    setTimeout(() => {
                        $(".i_modal_bg_in").remove();
                    }, 200);
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Call Delete Message Report Popup*/
    $(document).on("click", ".delrm", function() {
        var type = 'ddelReportM';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
            data: data,
            cache: false,
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                }
            }
        });
    });
    /*Delete Message Report*/
    $(document).on("click", ".delete_report_m", function() {
        var type = 'deleteMReport';
        var ID = $(this).attr("id");
        var csrfToken = $("#rmReportsCsrf").val() || (typeof window.csrfToken !== 'undefined' ? window.csrfToken : '');
        var data = 'f=' + type + '&id=' + ID + '&note=';
        if (csrfToken) {
            data += '&csrf_token=' + encodeURIComponent(csrfToken);
        }
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    location.reload();
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Change Module Statuses*/
    $(document).on("change", ".chmdBlockCountries", function() {
        var type = $(this).attr("id");
        var value = $(this).val();
        var data = 'f=' + type + '&mod=' + value;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    if (value == 'yes') {
                        $("#" + type).val('no');
                        $("." + type).val('no');
                    } else {
                        $("." + type).val('yes');
                        $("#" + type).val('yes');
                    }
                    $("." + type).show();
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
                setTimeout(() => {
                    $("." + type).hide();
                }, 5000);
            }
        });
    });
    /*Change Module Statuses*/
    $(document).on("change", ".reCaptchaPost", function() {
        var value = $(this).val();
        if (value == 'yes') {
            $(this).val('no');
        } else {
            $(this).val('yes');
        }
    });
    /*Change Module Statuses*/
    $(document).on("change", ".oneSignalStatuss", function() {
        var value = $(this).val();
        if (value == 'open') {
            $(this).val('close');
        } else {
            $(this).val('open');
        }
    });
    /*Upload Verification Files*/
    $(document).on("change", "#id_watermark", function(e) {
        e.preventDefault();
    
        var values = $("#watlogo").val();
        var id = $(this).attr("data-id");
        var type = $(this).attr("data-type");
        var data = { f: id, c: type };
    
        $("#waUploadForm").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function() {
                if (type === 'sec_tree') {
                    $("#watlogo").val('');
                    $("#sec_logo").append(uploadLoading);
                }
                $("." + type).hide();
            },
            uploadProgress: function(e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function(response) {
                if (response && response !== "undefined,") {
                    if (type === 'sec_tree') {
                        $("#watlogo").val(response);
                    }
                    $("." + type).show();
                }
                $(".i_upload_progress").remove();
            } 
        });
     
        $("#waUploadForm").trigger("submit");
    });
    /*Upload Verification Files*/
    $(document).on("change", "#gift_image", function(e) {
        e.preventDefault();
    
        var values = $("#giftFile").val();
        var id = $(this).attr("data-id");
        var type = $(this).attr("data-type");
        var data = { f: id, c: type };
    
        $("#giftImageUploadForm").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function() {
                $("#giftFile").val('');
                $("#sec_logo").append(uploadLoading);
                $("." + type).hide();
            },
            uploadProgress: function(e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function(response) {
                if (response && response !== "undefined,") {
                    $("#giftFile").val(response);
                    $("." + type).show();
                }
                $(".i_upload_progress").remove();
            } 
        });
     
        $("#giftImageUploadForm").trigger("submit");
    });

    $(document).on('submit', "#editLivePointPackage", function(e) {
        e.preventDefault();
        var editLivePointPackage = $("#editLivePointPackage");
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: editLivePointPackage.serialize(),
            beforeSend: function() {
                $(".pk_wraning").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                editLivePointPackage.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    editLivePointPackage.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    window.location.href = siteurlRedirect + 'manage_point_packages_live';
                } else if (data == '404') {
                    $(".pk_wraning").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Call Delete Post PopUpBox*/
    $(document).on("click", ".delete_live_plan", function() {
        var type = 'ddelLivePlan';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
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
                }
            }
        });
    });
    /*Call Delete Post PopUpBox*/
    $(document).on("click", ".delete_frame_plan", function() {
        var type = 'ddelFramePlan';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
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
                }
            }
        });
    });
    /*Delete Plan */
    $(document).on("click", ".del__live_plan", function() {
        var type = 'deleteThisLivePlan';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    location.reload();
                } else {
                    $(".warning_").show();
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Delete Plan */
    $(document).on("click", ".del__frame_plan", function() {
        var type = 'deleteThisFramePlan';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    location.reload();
                } else {
                    $(".warning_").show();
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Upload Verification Files*/
    $(document).on("change", "#gift_animation_image", function(e) {
        e.preventDefault();
    
        var values = $("#GiftAnimationFile").val();
        var id = $(this).attr("data-id");
        var type = $(this).attr("data-type");
        var data = { f: id, c: type };
    
        $("#giftAnimationImageUploadForm").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function() {
                $("#GiftAnimationFilea").val('');
                $("#sec_logo").append(uploadLoading);
                $("." + type).hide();
            },
            uploadProgress: function(e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function(response) {
                if (response && response !== "undefined,") {
                    $("#GiftAnimationFilea").val(response);
                    $("." + type).show();
                }
                $(".i_upload_progress").remove();
            } 
        });
     
        $("#giftAnimationImageUploadForm").trigger("submit");
    });
    $(document).on('submit', '#affilateSet', function(e) {
        e.preventDefault();
        var businessInformation = $('#affilateSet');
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: businessInformation.serialize(),
            beforeSend: function() {
                $(".warning_one , .successNot").hide();
                $("#business_conf").append(plreLoadingAnimationPlus);
                businessInformation.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    businessInformation.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    $(".successNot").show();
                } else if (data == '1') {
                    $(".warning_one").show();
                } else if (data == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Change Module Statuses*/
    $(document).on("change", ".chmdAutoApprovePost", function() {
        var type = $(this).attr("id");
        var value = $(this).val();
        var data = 'f=' + type + '&mod=' + value;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    if (value == 'yes') {
                        $("#" + type).val('no');
                        $("." + type).val('no');
                    } else {
                        $("." + type).val('yes');
                        $("#" + type).val('yes');
                    }
                    $("." + type).show();
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
                setTimeout(() => {
                    $("." + type).hide();
                }, 5000);
            }
        });
    });
    /*Change Module Statuses*/
    $(document).on("change", ".chmdCrAc", function() {
        var type = 'becomecreatortypestatus';
        var value = $(this).val();
        var data = 'f=' + type + '&mod=' + value;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
                $(".success_tick").hide();
            },
            success: function(response) {
                if (response == '200') {
                    $("." + value).show();
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
                setTimeout(() => {
                    $("." + type).hide();
                }, 5000);
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
            url: siteurl + 'request/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                /*Do Something*/
            },
            success: function(response) {
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 100);
                if (response == '200') {
                    $(".body_" + ID).fadeOut();
                } else {
                    $(".warning_").show();
                }
            }
        });
    });
    /*Call Delete User PopUp*/
    $(document).on("click", ".del_stor_bg", function() {
        var type = 'deleteStoryBg';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
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
                }
            }
        });
    });
    $(document).on("click", ".del_story_audio", function() {
        var type = 'deleteStoryAudio';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
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
                }
            }
        });
    });
    $(document).on("click", ".edit_story_audio", function() {
        var type = 'editStoryAudio';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
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
                }
            }
        });
    });
    /*Delete Sticker*/
    $(document).on("click", ".delete_storybg", function() {
        var type = 'deleteStoryBg';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 200);
                if (response == '200') {
                    location.reload();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on("click", ".delete_story_audio", function() {
        var type = 'deleteStoryAudio';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 200);
                if (response == '200') {
                    location.reload();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Change Module Statuses*/
    $(document).on("change", ".chmdMod", function() {
        var type = $(this).attr("id");
        var value = $(this).val();
        var data = 'f=' + type + '&mod=' + value;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    if (value == 'yes') {
                        $("#" + type).val('no');
                        $("." + type).val('no');
                    } else {
                        $("." + type).val('yes');
                        $("#" + type).val('yes');
                    }
                    $("." + type).show();
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
                setTimeout(() => {
                    $("." + type).hide();
                }, 5000);
            }
        });
    });
    /*Call Delete User Verification Request PopUp*/
    $(document).on("click", ".addNewAnnouncement", function() {
        var type = 'addNewAnnouncement';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&sid=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
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
                }
            }
        });
    });
    /*Change Module Statuses*/
    $(document).on("change", ".chmdAnnouncementStatus", function() {
        var type = $(this).attr("id");
        var value = $(this).val();
        if (value == 'yes') {
            $("#" + type).val('no');
            $("." + type).val('no');
        } else {
            $("." + type).val('yes');
            $("#" + type).val('yes');
        }
        $("." + type).show();
    });
    /*Change Module Statuses*/
    $(document).on("change", ".upAnnon", function() {
        var type = $(this).attr("data-type");
        var ID = $(this).attr("data-id");
        var value = $(this).val();
        var data = 'f=' + type + '&mod=' + value + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    if (value == 'yes') {
                        $("#" + type + ID).val('no');
                    } else {
                        $("#" + type + ID).val('yes');
                    }
                    $(".upAnnon" + ID).show();
                    setTimeout(() => {
                        $(".upAnnon" + ID).hide();
                    }, 5000);
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Call Delete User PopUp*/
    $(document).on("click", ".del_annon", function() {
        var type = 'deleteAnnouncement';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
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
                }
            }
        });
    });
    /*Delete Sticker*/
    $(document).on("click", ".delete_announce", function() {
        var type = 'deleteAnnouncement';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 200);
                if (response == '200') {
                    location.reload();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Call Delete User Verification Request PopUp*/
    $(document).on("click", ".edAnnon", function() {
        var type = 'editAnnouncement';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&sid=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
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
                }
            }
        });
    });
    /*Change Module Statuses*/
    $(document).on("change", ".upSocial", function() {
        var type = $(this).attr("data-type");
        var ID = $(this).attr("data-id");
        var value = $(this).val();
        var data = 'f=' + type + '&mod=' + value + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    if (value == 'yes') {
                        $("#" + type + ID).val('no');
                    } else {
                        $("#" + type + ID).val('yes');
                    }
                    $(".upSocial" + ID).show();
                    setTimeout(() => {
                        $(".upSocial" + ID).hide();
                    }, 5000);
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Delete Question*/
    $(document).on("click", ".delete_ssite", function() {
        var type = 'deleteSocialSit';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    location.reload();
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Delete Question*/
    $(document).on("click", ".delete_sswite", function() {
        var type = 'deleteSocialSitW';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    location.reload();
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Change Module Statuses*/
   $(document).on("change", ".chmdModTwo", function() {
    var type = 'subCatMod';
    var value = $(this).val();
    var ID = $(this).attr("data-id");
    var data = 'f=' + type + '&mod=' + value + '&sID='+ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf"+ID).append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    if (value == '1') {
                        $(".fig_sec_" + ID).val('0');
                    } else {
                        $(".fig_sec_" + ID).val('1');
                    }
                    $(".sucss_"+ID).show();
                    setTimeout(() => {
                        $(".sucss_"+ID).hide();
                    }, 5000);
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();

            }
        });
    });
    $(document).on("click",".sceEd", function(){
        var ID = $(this).attr("id");
        var val = $("#sub_va_"+ID).val();
        var type = 'upSubKey';
        var data = 'f='+type+'&skey='+val+'&sid='+ID;
        $.ajax({
         type: 'POST',
         url: siteurl + 'request/request.php',
         data: data,
             beforeSend: function() {
                $("#general_conf"+ID).append(plreLoadingAnimationPlus);
             },
             success: function(response) {
                if (response == '200') {
                    $(".sucss_"+ID).show();
                    setTimeout(() => {
                        $(".sucss_"+ID).hide();
                    }, 5000);
                } else if (response == '404') {
                    $(".warning_").show();
                }
                $(".loaderWrapper").remove();
             }
         });
    });
    $(document).on("click", ".scneEd", function(){
        var ID = $(this).attr("data-c");
        var newKey = $("#n_"+ ID).val();
        var addToID = $(this).attr("id");
        var data = 'f=addNewSubCat'+'&nkey='+newKey+'&addTo='+addToID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
                beforeSend: function() {
                    $("#general_conf"+ID).append(plreLoadingAnimationPlus);
                },
                success: function(response) {
                    $(".a_n_"+ID).before(response);
                    $(".n_s_c_"+ID).hide(); 
                }
            });
    });
    /*Call Delete Post PopUpBox*/
    $(document).on("click", ".sbc_delete", function() {
        var type = 'delSubCat';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
            data: data,
            cache: false, 
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                }
            }
        });
    });
    /*Call Delete Post PopUpBox*/
    $(document).on("click", ".delete_subc", function() {
        var type = 'delSubCat';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $("#general_conf"+ID).append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response != '404') {
                   $("#general_conf"+ID).remove();
                }
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                    $(".loaderWrapper").remove();
                }, 200);
            }
        });
    });
    /*Change Module Statuses*/
    $(document).on("change", ".cmdCatMod", function() {
        var type = 'catModStatus';
        var value = $(this).val();
        var qID = $(this).attr("data-id");
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&mod=' + value + '&Cid=' + qID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#gen_"+qID).append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    if (value == '1') {
                        $(".ca_" + ID).val('0');
                    } else {
                        $(".ca_" + ID).val('1');
                    }
                    $(".mysuc_"+qID).show();
                    setTimeout(() => {
                        $(".mysuc_"+qID).hide();
                    }, 5000);
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();

            }
        });
    });
    $(document).on("click",".svcEdt", function(){
        var ID = $(this).attr("id");
        var val = $("#cat_va_"+ID).val();
        var type = 'upCatKey';
        var data = 'f='+type+'&ckey='+val+'&cid='+ID;
        $.ajax({
         type: 'POST',
         url: siteurl + 'request/request.php',
         data: data,
             beforeSend: function() {
                $("#gen_"+ID).append(plreLoadingAnimationPlus);
             },
             success: function(response) {
                if (response == '200') {
                    $(".mysuc_"+ID).show();
                    location.reload();
                    setTimeout(() => {
                        $(".mysuc_"+ID).hide();
                    }, 5000);
                } else if (response == '404') {
                    $(".warning_").show();
                }
                $(".loaderWrapper").remove();
             }
         });
    });
    /*Call Delete Post PopUpBox*/
    $(document).on("click", ".del_this_cat", function() {
        var type = 'delCatt';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
            data: data,
            cache: false, 
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                }
            }
        });
    });
    /*Call Delete Post PopUpBox*/
    $(document).on("click", ".delete_tcat", function() {
        var type = 'delCatt';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $("#gen_"+ID).append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response != '404') {
                   $("#gen_"+ID).remove();
                   location.reload();
                }
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                    $(".loaderWrapper").remove();
                }, 200);
            }
        });
    });
    $(document).on("click",".addNewPCat", function(){
        var nKey = $(".newCla").val();
        var data = 'f=cNewCatP'+'&ky='+nKey;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/request.php',
            data: data,
            cache: false,
            beforeSend: function() {
                $(".papk_wraning , .warning_one").hide();
                $(".general_top").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    location.reload();
                }else if(response == '1'){
                    $(".papk_wraning").show();
                }else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Change Module Statuses*/
    $(document).on("change", ".uPBoost", function() {
        var type = $(this).attr("data-type");
        var ID = $(this).attr("data-id");
        var value = $(this).val();
        var data = 'f=' + type + '&mod=' + value + '&id=' + ID;
        var csrf = $('input[name=csrf_token]').first().val();
        if (csrf) {
            data += '&csrf_token=' + encodeURIComponent(csrf);
        }
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    if (value == '1') {
                        $("#" + type + ID).val('0');
                    } else {
                        $("#" + type + ID).val('1');
                    }
                    $(".uPBoost" + ID).show();
                    setTimeout(() => {
                        $(".uPBoost" + ID).hide();
                    }, 5000);
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Call Delete Product PopUP*/
    $(document).on("click", ".del_BoostPopUP", function() {
        var type = 'deleteBoostedPost';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
            data: data,
            cache: false, 
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                }
            }
        });
    });
    $(document).on("click", ".delete_boost", function() {
        var type = 'deleteBoostedPost';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        var csrf = $('input[name=csrf_token]').first().val();
        if (csrf) {
            data += '&csrf_token=' + encodeURIComponent(csrf);
        }
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                $(".i_modal_in_in").addClass("i_modal_in_in_out");
                setTimeout(() => {
                    $(".i_modal_bg_in").remove();
                }, 200);
                if (response == '200') {
                    location.reload();
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
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
            url: siteurl + 'request/popup.php',
            data: data,
            cache: false, 
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                }
            }
        });
    });
    $(document).on("change", ".pstatBoost", function() {
        var value = $(this).val();
        if (value == '1') {
            $("#plnststs").val('0');
        } else {
            $("#plnststs").val('1');
        }
    });
    /*Change Module Statuses*/
    $(document).on("change", ".pstatBoost", function() {
        var type = $(this).attr("data-type");
        var ID = $(this).attr("data-id");
        var value = $(this).val();
        var data = 'f=' + type + '&mod=' + value + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    if (value == '1') {
                        $("#" + type + ID).val('0');
                    } else {
                        $("#" + type + ID).val('1');
                    }
                    $("." + type).show();
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
                setTimeout(() => {
                    $("." + type).hide();
                }, 5000);
            }
        });
    });
     /*Call Delete Post PopUpBox*/
     $(document).on("click", ".delete_boost_plan", function() {
        var type = 'ddelBoostPlan';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
            data: data,
            cache: false, 
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                }
            }
        });
    });
    /*Delete Plan */
    $(document).on("click", ".del__plan_boost", function() {
        var type = 'deleteThisBoostPlan';
        var ID = $(this).attr("id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    location.reload();
                } else {
                    $(".warning_").show();
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on('submit', "#editBoostPackage", function(e) {
        e.preventDefault();
        var editBoostPackage = $("#editBoostPackage");
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: editBoostPackage.serialize(),
            beforeSend: function() {
                $(".pk_wraning").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                editBoostPackage.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    editBoostPackage.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    window.location.href = siteurlRedirect + 'boost_package_settings';
                } else if (data == '404') {
                    $(".pk_wraning").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Check Payment*/
    $(document).on("click",".check_payment",function(){
        var type = 'getPaymentDetails';
        var paymentID = $(this).attr('id');
        var data = 'f=' + type + '&pyID=' + paymentID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
            data: data,
            cache: false, 
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_in").addClass('i_modal_display_in');
                    }, 200);
                }
            }
        });
    });
    /*Approve Bank Payment*/
    $(document).on("click", ".approve_bank_payment", function() {
        var type = 'approveBankPayment';
        var payerUID = $(this).attr("id");
        var planID = $(this).attr("data-id");
        var imID = $(this).attr("data-imd");
        var paymentID = $(this).attr('data-pid');
        var data = 'f=' + type + '&payerid=' + payerUID + '&planID='+planID + '&imID=' +imID+'&paymentID='+paymentID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $(".progme").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    location.reload();
                } else {
                    $(".warning_").show();
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Decline Bank Payment*/
    $(document).on("click", ".decline_bank_payment", function() {
        var type = 'declineBankPayment';
        var payerUID = $(this).attr("id");
        var planID = $(this).attr("data-id");
        var imID = $(this).attr("data-imd");
        var paymentID = $(this).attr('data-pid');
        var data = 'f=' + type + '&payerid=' + payerUID + '&planID='+planID + '&imID=' +imID+'&paymentID='+paymentID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $(".progme").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    location.reload();
                } else {
                    $(".warning_").show();
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on('submit', "#updateBankPaymentGataway", function(e) {
        e.preventDefault();
        var updateBankPGateway = $("#updateBankPaymentGataway");
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: updateBankPGateway.serialize(),
            beforeSend: function() {
                $(".successNot , .warning_").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                updateBankPGateway.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    updateBankPGateway.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    $(".successNot").show();
                } else if (data == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Upload Verification Files*/
    $(document).on("change", "#frame_image", function(e) {
        e.preventDefault();
    
        var values = $("#frameFile").val();
        var id = $(this).attr("data-id");
        var type = $(this).attr("data-type");
        var data = { f: id, c: type };
    
        $("#frameImageUploadForm").ajaxForm({
            type: "POST",
            data: data,
            delegation: true,
            cache: false,
            beforeSubmit: function() {
                $("#frameFile").val('');
                $("#sec_logo").append(uploadLoading);
                $("." + type).hide();
            },
            uploadProgress: function(e, position, total, percentageComplete) {
                $('.i_upload_progress').width(percentageComplete + '%');
            },
            success: function(response) {
                if (response && response !== "undefined,") {
                    $("#frameFile").val(response);
                    $("." + type).show();
                }
                $(".i_upload_progress").remove();
            } 
        });
     
        $("#frameImageUploadForm").trigger("submit");
    });
    $(document).on('submit', "#editFramePointPackage", function(e) {
        e.preventDefault();
        var editLivePointPackage = $("#editFramePointPackage");
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: editLivePointPackage.serialize(),
            beforeSend: function() {
                $(".pk_wraning").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                editLivePointPackage.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    editLivePointPackage.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    window.location.href = siteurlRedirect + 'manage_frame_packages';
                } else if (data == '404') {
                    $(".pk_wraning").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    }); 
    /*Call Colors*/
    $(document).on("click", ".call_colors", function() {
        var type = 'callColors';
        var ID = $(this).attr("data-id");
        var data = 'f=' + type + '&id=' + ID;
        $.ajax({
            type: "POST",
            url: siteurl + 'request/popup.php',
            data: data,
            cache: false,
            beforeSend: function() {
               $(".i_modal_display_in_picker").off("click"); 
               $(".i_modal_display_in_picker").remove(); 
               $("." + ID).append(plreLoadingAnimationPlus); 
            },
            success: function(response) {
                if (response != '404') {
                    $("body").append(response);
                    setTimeout(() => {
                        $(".i_modal_bg_colorpicker_in").addClass('i_modal_display_in_picker');
                        $(".i_modal_display_in_picker").on("click");
                    }, 200);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    
    $(document).on("click", ".ttcolor", function() {
        var colorCode = $(this).css("background-color");
        colorCode = rgbToHex(colorCode); // RGB rengi HEX'e çevir
        var colorFor = $(this).attr("data-id");
    
        // Rengi header_top_color divine uygula
        $("."+colorFor).css("background-color", colorCode);
    
        // Rengi input elementinin değerine ata
        $('input[name="'+colorFor+'"]').val(colorCode);
    });
    
    // RGB rengi HEX'e çeviren fonksiyon
    function rgbToHex(rgb) {
        var hex = rgb.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
        return "#" + ("0" + parseInt(hex[1], 10).toString(16)).slice(-2) +
                     ("0" + parseInt(hex[2], 10).toString(16)).slice(-2) +
                     ("0" + parseInt(hex[3], 10).toString(16)).slice(-2);
    }

/*Change Module Statuses*/
    $(document).on("change", ".chmdPostSub", function() {
        var type = $(this).attr("data-id");
        var value = $(this).val();
        var data = 'f=' + type + '&mod=' + value;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) { 
                if (response == '200') { 
                    $("." + type).show();
                } else if (response == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
                setTimeout(() => {
                    $("." + type).hide();
                }, 5000);
            }
        });
    });
})(jQuery);

$(document).ready(function() {
    var preLoadingAnimation = '<div class="i_loading product_page_loading"><div class="dot-pulse"></div></div>';
    var plreLoadingAnimationPlus = '<div class="loaderWrapper"><div class="loaderContainer"><div class="loader">' + preLoadingAnimation + '</div></div></div>';
    $("body").on('submit', "#createNewPage", function(e) {
        e.preventDefault();
        var createNewPage = $("#createNewPage");
         
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: createNewPage.serialize(),
            beforeSend: function() {
                $(".warning_one , .warning_two , .warning_").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                createNewPage.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    createNewPage.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    window.location.href = siteurlRedirect + 'pages';
                } else if (data == '1') {
                    $(".warning_one").show();
                } else if (data == '2') {
                    $(".warning_two").show();
                } else if (data == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $("body").on('submit', "#editPage", function(e) {
        e.preventDefault();
        var editPage = $("#editPage");
         
        jQuery.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: editPage.serialize(),
            beforeSend: function() {
                $(".successNot , .warning_one , .warning_two , .warning_").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                editPage.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    editPage.find(':input[type=submit]').prop('disabled', false);
                }, 3000);
                if (data == '200') {
                    $(".successNot").show();
                } else if (data == '1') {
                    $(".warning_one").show();
                } else if (data == '2') {
                    $(".warning_two").show();
                } else if (data == '404') {
                    $(".warning_").show();
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + data + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
    $("body").on('submit', "#blogPostForm", function(e) {
        e.preventDefault();
        var $form = $(this);
        var formData = new FormData(this);
        $.ajax({
            type: "POST",
            url: siteurl + "request/request.php",
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                $(".warning_one , .warning_two , .warning_ , .successNot").hide();
                $("#general_conf").append(plreLoadingAnimationPlus);
                $form.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                setTimeout(() => {
                    $form.find(':input[type=submit]').prop('disabled', false);
                }, 1500);
                data = $.trim(data);
                if (data === '200') {
                    $(".successNot").show();
                    setTimeout(() => {
                        window.location.href = siteurlRedirect + 'blog_posts';
                    }, 800);
                } else if (data === '1') {
                    $(".warning_one").show();
                } else if (data === '2') {
                    $(".warning_two").show();
                } else {
                    $(".warning_").show().text(data);
                }
                $(".loaderWrapper").remove();
            },
            error: function() {
                $form.find(':input[type=submit]').prop('disabled', false);
                $(".warning_").show();
                $(".loaderWrapper").remove();
            }
        });
    });
    function blogNotice(message, onClose) {
        if (typeof adsNotice === 'function') {
            adsNotice(message, onClose);
            return;
        }
        $(".warning_").text(message).show();
        if (typeof onClose === 'function') { onClose(); }
    }
    function blogConfirm(message, onConfirm) {
        $(".blog_modal_overlay, .ads_modal_overlay").remove();
        var modal = '<div class="blog_modal_overlay"><div class="blog_modal_box">';
        modal += '<div class="blog_modal_title">' + message + '</div>';
        modal += '<div class="blog_modal_actions"><button type="button" class="i_nex_btn_btn blog_modal_yes">'+(typeof LangYes !== 'undefined' ? LangYes : 'Yes')+'</button>';
        modal += '<button type="button" class="ghost_btn blog_modal_no">'+(typeof LangNo !== 'undefined' ? LangNo : 'Cancel')+'</button></div>';
        modal += '</div></div>';
        $("body").append(modal);
        $(document).one('click', '.blog_modal_yes', function() {
            $(".blog_modal_overlay").remove();
            if (typeof onConfirm === 'function') { onConfirm(); }
        });
        $(document).one('click', '.blog_modal_no', function() {
            $(".blog_modal_overlay").remove();
        });
        $(document).one('click', '.blog_modal_overlay', function(e){
            if (e.target.classList.contains('blog_modal_overlay')) {
                $(".blog_modal_overlay").remove();
            }
        });
    }
    $(document).on('click', ".deleteBlog", function() {
        var blogId = $(this).attr("data-id");
        var csrf = $(this).data('csrf') || $('input[name=csrf_token]').first().val() || (typeof window.csrfToken !== 'undefined' ? window.csrfToken : '');
        var failMsg = ($(".warning_").length ? $.trim($(".warning_").text()) : '') || 'Something went wrong, please try again later!';
        if (!blogId) { return; }
        if (!csrf) {
            blogNotice(failMsg);
            return;
        }
        blogConfirm('Delete this blog post?', function(){
            $.post(siteurl + "request/request.php", {f:'deleteBlogPost', id: blogId, csrf_token: csrf}, function(resp){
                resp = $.trim(resp);
                if (resp === '200') {
                    blogNotice('Blog post deleted.', function(){ window.location.reload(); });
                } else {
                    $(".warning_").hide();
                    blogNotice(resp || failMsg);
                }
            }).fail(function(){
                $(".warning_").hide();
                blogNotice(failMsg);
            });
        });
    });
    $(document).on("click", ".delete_admin_chat", function() {
        var chatId = $(this).attr("data-chat-id");
        var csrf = $("#adminChatsCsrf").val() || "";
        if (!chatId || !csrf) {
            return;
        }
        var deleteConversationConfirmText = $("#adminDeleteChatConfirm").val() || "";
        if (!window.confirm(deleteConversationConfirmText)) {
            return;
        }
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: {
                f: 'adminDeleteConversation',
                chat_id: chatId,
                csrf_token: csrf
            },
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                response = $.trim(response);
                if (response === '200') {
                    window.location.reload();
                    return;
                }
                $("body").append('<div class=\"nnauthority\"><div class=\"no_permis flex_ c3 border_one tabing\">' + response + '</div></div>');
                setTimeout(function() {
                    $(".nnauthority").remove();
                }, 5000);
            },
            complete: function() {
                $(".loaderWrapper").remove();
            }
        });
    });
    $(document).on("click", ".delete_admin_chat_message", function() {
        var chatId = $(this).attr("data-chat-id");
        var messageId = $(this).attr("data-message-id");
        var csrf = $("#adminChatsCsrf").val() || "";
        if (!chatId || !messageId || !csrf) {
            return;
        }
        var deleteMessageConfirmText = $("#adminDeleteChatMessageConfirm").val() || "";
        if (!window.confirm(deleteMessageConfirmText)) {
            return;
        }
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: {
                f: 'adminDeleteConversationMessage',
                chat_id: chatId,
                message_id: messageId,
                csrf_token: csrf
            },
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                response = $.trim(response);
                if (response === '200') {
                    window.location.reload();
                    return;
                }
                $("body").append('<div class=\"nnauthority\"><div class=\"no_permis flex_ c3 border_one tabing\">' + response + '</div></div>');
                setTimeout(function() {
                    $(".nnauthority").remove();
                }, 5000);
            },
            complete: function() {
                $(".loaderWrapper").remove();
            }
        });
    });
    /*Change Module Statuses*/
    $(document).on("change", ".upwSocial", function() {
        var type = $(this).attr("data-type");
        var ID = $(this).attr("data-id");
        var value = $(this).val();
        var data = 'f=' + type + '&mod=' + value + '&id=' + ID;
        $.ajax({
            type: 'POST',
            url: siteurl + 'request/request.php',
            data: data,
            beforeSend: function() {
                $("#general_conf").append(plreLoadingAnimationPlus);
            },
            success: function(response) {
                if (response == '200') {
                    if (value == 'yes') {
                        $("#" + type + ID).val('no');
                    } else {
                        $("#" + type + ID).val('yes');
                    }
                    $(".upwSocial" + ID).show();
                    setTimeout(() => {
                        $(".upwSocial" + ID).hide();
                    }, 5000);
                } else {
                    $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + response + '</div></div>');
                    setTimeout(() => {
                        $(".nnauthority").remove();
                    }, 5000);
                }
                $(".loaderWrapper").remove();
            }
        });
    });
});
