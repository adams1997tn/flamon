(function($) {
    "use strict";

    const loadingHTML = '<div class="loaderWrapper"><div class="loaderContainer"><div class="loader"><div class="i_loading product_page_loading"><div class="dot-pulse"></div></div></div></div></div>';
    const overlayTabStorageKey = 'dizzy_obs_overlay_active_tab';

    function setStoredActiveOverlayId(overlayId) {
        const safeId = String(overlayId || '').trim();
        if (!safeId || !window.sessionStorage) {
            return;
        }
        try {
            window.sessionStorage.setItem(overlayTabStorageKey, safeId);
        } catch (err) {}
    }

    function getStoredActiveOverlayId() {
        if (!window.sessionStorage) {
            return '';
        }
        try {
            return String(window.sessionStorage.getItem(overlayTabStorageKey) || '').trim();
        } catch (err) {
            return '';
        }
    }

    function clearStoredActiveOverlayId() {
        if (!window.sessionStorage) {
            return;
        }
        try {
            window.sessionStorage.removeItem(overlayTabStorageKey);
        } catch (err) {}
    }

    function showNotice($form, message) {
        if (!message) {
            return;
        }
        const $notice = $form.find('.obs_overlay_notice, .obs_overlay_create_notice, .obs_overlay_layout_notice, .obs_overlay_styles_notice');
        if ($notice.length) {
            $notice.text(message).show();
            return;
        }
        $("body").append('<div class="nnauthority"><div class="no_permis flex_ c3 border_one tabing">' + message + '</div></div>');
        setTimeout(() => {
            $(".nnauthority").remove();
        }, 5000);
    }

    function clearNotice($form) {
        const $notice = $form.find('.obs_overlay_notice, .obs_overlay_create_notice, .obs_overlay_layout_notice, .obs_overlay_styles_notice');
        if ($notice.length) {
            $notice.text('').hide();
        }
    }

    function submitForm($form) {
        const overlayIdValue = String($form.find('input[name="obs_overlay_id"]').first().val() || '').trim();
        if (overlayIdValue) {
            setStoredActiveOverlayId(overlayIdValue);
        }
        const formData = new FormData($form[0]);
        $.ajax({
            type: 'POST',
            url: siteurl + 'requests/request.php',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                clearNotice($form);
                $form.append(loadingHTML);
                $form.find(':input[type=submit]').prop('disabled', true);
            },
            success: function(data) {
                $form.find(':input[type=submit]').prop('disabled', false);
                $(".loaderWrapper").remove();
                if (data === '200') {
                    window.location.reload();
                    return;
                }
                showNotice($form, data);
            }
        });
    }

    $(document).on('submit', '#obsOverlayCreateForm', function(e) {
        e.preventDefault();
        clearStoredActiveOverlayId();
        submitForm($(this));
    });

    $(document).on('submit', '.obsOverlayForm', function(e) {
        e.preventDefault();
        submitForm($(this));
    });

    $(document).on('submit', '.obsOverlayRevokeForm', function(e) {
        e.preventDefault();
        const $form = $(this);
        const confirmMsg = $form.data('confirm');
        if (confirmMsg && !window.confirm(confirmMsg)) {
            return;
        }
        submitForm($form);
    });

    function refreshObsOverlayLayoutEditors($scope) {
        const $context = $scope && $scope.length ? $scope : $(document);
        $context.find('.obs-layout-editor').each(function() {
            const refreshFn = $(this).data('obsLayoutRefresh');
            if (typeof refreshFn === 'function') {
                refreshFn();
            }
        });
    }

    function activateOverlayPanel(overlayId, shouldStore) {
        const safeId = String(overlayId || '').trim();
        if (!safeId) {
            return;
        }
        const $tabs = $('.obs-overlay-tab');
        const $panels = $('.obs-overlay-panel');
        if (!$tabs.length || !$panels.length) {
            return;
        }
        const $targetPanel = $panels.filter('[data-overlay-panel="' + safeId + '"]');
        const $targetTab = $tabs.filter('[data-overlay-tab="' + safeId + '"]');
        if (!$targetPanel.length || !$targetTab.length) {
            return;
        }
        $tabs.removeClass('is-active').attr('aria-selected', 'false');
        $panels.removeClass('is-active');
        $targetTab.addClass('is-active').attr('aria-selected', 'true');
        $targetPanel.addClass('is-active');
        enforceObsOverlayVisibleOpenLimit();
        const queueRefresh = window.requestAnimationFrame || function(callback) {
            window.setTimeout(callback, 0);
        };
        queueRefresh(function() {
            refreshObsOverlayLayoutEditors($targetPanel);
        });
        if (shouldStore) {
            setStoredActiveOverlayId(safeId);
        }
    }

    function initOverlayTabs() {
        const $tabs = $('.obs-overlay-tab');
        const $panels = $('.obs-overlay-panel');
        if (!$tabs.length || !$panels.length) {
            return;
        }
        let initialId = getStoredActiveOverlayId();
        if (!initialId || !$tabs.filter('[data-overlay-tab="' + initialId + '"]').length) {
            initialId = String($tabs.first().data('overlay-tab') || '').trim();
        }
        if (initialId) {
            activateOverlayPanel(initialId, false);
        }
        $(document).on('click', '.obs-overlay-tab', function() {
            const overlayId = String($(this).data('overlay-tab') || '').trim();
            if (overlayId) {
                activateOverlayPanel(overlayId, true);
            }
        });
    }

    initOverlayTabs();

    function setObsOverlayCollapseState($item, shouldOpen, animate) {
        const $title = $item.children('.i_settings_item_title').first();
        const $content = $item.children('.i_settings_item_title_for').first();
        if (!$title.length || !$content.length) {
            return;
        }
        $item.toggleClass('obs-collapsible-open', shouldOpen);
        $item.toggleClass('obs-collapsible-closed', !shouldOpen);
        $title.attr('aria-expanded', shouldOpen ? 'true' : 'false');
        if (animate) {
            if (shouldOpen) {
                $content.stop(true, true).slideDown(170, function() {
                    refreshObsOverlayLayoutEditors($item);
                });
            } else {
                $content.stop(true, true).slideUp(170);
            }
            return;
        }
        if (shouldOpen) {
            $content.show();
            refreshObsOverlayLayoutEditors($item);
        } else {
            $content.hide();
        }
    }

    function enforceObsOverlayVisibleOpenLimit() {
        const pageSelector = '.settings_main_wrapper.obs_overlays_page';
        const initialOpenLimit = 6;
        const $visibleItems = $(pageSelector + ' .i_settings_wrapper_item.obs-collapsible-item').filter(function() {
            const $item = $(this);
            const $title = $item.children('.i_settings_item_title').first();
            const $content = $item.children('.i_settings_item_title_for').first();
            if (!$title.length || !$content.length) {
                return false;
            }
            const $panel = $item.closest('.obs-overlay-panel');
            if (!$panel.length) {
                return true;
            }
            return $panel.hasClass('is-active');
        });
        $visibleItems.each(function(index) {
            setObsOverlayCollapseState($(this), index < initialOpenLimit, false);
        });
    }

    function initObsOverlayCollapsibles() {
        const pageSelector = '.settings_main_wrapper.obs_overlays_page';
        const $items = $(pageSelector + ' .i_settings_wrapper_item');
        if (!$items.length) {
            return;
        }
        $items.each(function() {
            const $item = $(this);
            const $title = $item.children('.i_settings_item_title').first();
            const $content = $item.children('.i_settings_item_title_for').first();
            if (!$title.length || !$content.length) {
                return;
            }
            if ($item.hasClass('obs-collapsible-item')) {
                return;
            }
            $item.addClass('obs-collapsible-item');
            $title.addClass('obs-collapsible-title').attr({
                role: 'button',
                tabindex: '0',
                'aria-expanded': 'true'
            });
            $item.addClass('obs-collapsible-open');
            $content.show();
        });
        enforceObsOverlayVisibleOpenLimit();
    }

    function toggleObsOverlayCollapse($title) {
        const $item = $title.closest('.obs-collapsible-item');
        const $content = $item.children('.i_settings_item_title_for').first();
        if (!$item.length || !$content.length) {
            return;
        }
        setObsOverlayCollapseState($item, !$item.hasClass('obs-collapsible-open'), true);
    }

    $(document).on('click', '.settings_main_wrapper.obs_overlays_page .obs-collapsible-title', function() {
        toggleObsOverlayCollapse($(this));
    });

    $(document).on('keydown', '.settings_main_wrapper.obs_overlays_page .obs-collapsible-title', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            toggleObsOverlayCollapse($(this));
        }
    });

    initObsOverlayCollapsibles();

    function normalizeSearchText(value) {
        return String(value || '')
            .toLowerCase()
            .replace(/\s+/g, ' ')
            .trim();
    }

    function collectObsOverlaySearchTargets($page) {
        const activePanelId = String($page.find('.obs-overlay-panel.is-active').first().data('overlay-panel') || '').trim();
        const $tabs = $page.find('.obs-overlay-tab');
        const items = [];
        $page.find('.i_settings_wrapper_item.obs-collapsible-item').each(function(index) {
            const $item = $(this);
            const $title = $item.children('.i_settings_item_title').first();
            if (!$title.length) {
                return;
            }
            const label = String($title.text() || '').replace(/\s+/g, ' ').trim();
            if (!label) {
                return;
            }
            const $panel = $item.closest('.obs-overlay-panel');
            const panelId = $panel.length ? String($panel.data('overlay-panel') || '').trim() : '';
            const $tab = panelId ? $tabs.filter('[data-overlay-tab="' + panelId + '"]').first() : $();
            const context = $tab.length ? String($tab.find('.obs-overlay-tab-label').first().text() || '').replace(/\s+/g, ' ').trim() : '';
            items.push({
                label: label,
                normalizedLabel: normalizeSearchText(label),
                normalizedContext: normalizeSearchText(context),
                panelId: panelId,
                context: context,
                isActivePanel: panelId && panelId === activePanelId,
                order: index,
                $item: $item
            });
        });
        return items;
    }

    function findObsOverlaySearchMatches(query, $page) {
        const normalizedQuery = normalizeSearchText(query);
        if (!normalizedQuery) {
            return [];
        }
        const targets = collectObsOverlaySearchTargets($page);
        return targets
            .map((target) => {
                let score = 0;
                if (target.normalizedLabel.indexOf(normalizedQuery) === 0) {
                    score += 40;
                } else if (target.normalizedLabel.indexOf(normalizedQuery) > 0) {
                    score += 24;
                }
                if (target.normalizedContext.indexOf(normalizedQuery) !== -1) {
                    score += 12;
                }
                if (target.isActivePanel) {
                    score += 18;
                }
                if (target.panelId === '') {
                    score += 10;
                }
                return {
                    target: target,
                    score: score
                };
            })
            .filter((entry) => entry.score > 0)
            .sort((a, b) => {
                if (b.score !== a.score) {
                    return b.score - a.score;
                }
                return a.target.order - b.target.order;
            })
            .slice(0, 8)
            .map((entry) => entry.target);
    }

    function closeObsOverlaySuggestions($suggestions, state) {
        state.matches = [];
        state.activeIndex = -1;
        $suggestions.removeClass('is-visible').empty();
    }

    function openObsOverlaySearchResult(match, $input, $suggestions, state) {
        if (!match || !match.$item || !match.$item.length) {
            closeObsOverlaySuggestions($suggestions, state);
            return;
        }
        if (match.panelId) {
            activateOverlayPanel(match.panelId, true);
        }
        const $targetItem = match.$item;
        if ($targetItem.hasClass('obs-collapsible-closed')) {
            setObsOverlayCollapseState($targetItem, true, true);
        }
        closeObsOverlaySuggestions($suggestions, state);
        $input.val(match.label);
        window.setTimeout(function() {
            const targetNode = $targetItem.get(0);
            if (!targetNode) {
                return;
            }
            targetNode.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
            $targetItem.addClass('obs-search-highlight');
            window.setTimeout(function() {
                $targetItem.removeClass('obs-search-highlight');
            }, 1300);
        }, 70);
    }

    function renderObsOverlaySuggestions(matches, $suggestions, state) {
        state.matches = matches;
        state.activeIndex = -1;
        if (!matches.length) {
            closeObsOverlaySuggestions($suggestions, state);
            return;
        }
        $suggestions.empty().addClass('is-visible');
        matches.forEach((match, index) => {
            const $button = $('<button/>', {
                type: 'button',
                class: 'obs-overlay-search-suggestion',
                'data-index': index
            });
            $('<span/>', {
                class: 'obs-overlay-search-suggestion-title',
                text: match.label
            }).appendTo($button);
            if (match.context) {
                $('<span/>', {
                    class: 'obs-overlay-search-suggestion-meta',
                    text: match.context
                }).appendTo($button);
            }
            $suggestions.append($button);
        });
    }

    function initObsOverlayAutocomplete() {
        const pageSelector = '.settings_main_wrapper.obs_overlays_page';
        const $page = $(pageSelector).first();
        if (!$page.length) {
            return;
        }
        const $input = $page.find('.obs-overlay-search-input').first();
        const $suggestions = $page.find('.obs-overlay-search-suggestions').first();
        if (!$input.length || !$suggestions.length) {
            return;
        }
        const state = {
            matches: [],
            activeIndex: -1
        };

        const moveActiveSuggestion = function(direction) {
            if (!state.matches.length) {
                return;
            }
            if (direction === 'next') {
                state.activeIndex = state.activeIndex >= state.matches.length - 1 ? 0 : state.activeIndex + 1;
            } else {
                state.activeIndex = state.activeIndex <= 0 ? state.matches.length - 1 : state.activeIndex - 1;
            }
            $suggestions.find('.obs-overlay-search-suggestion').removeClass('is-active');
            $suggestions
                .find('.obs-overlay-search-suggestion[data-index="' + state.activeIndex + '"]')
                .addClass('is-active');
        };

        $input.on('input', function() {
            const value = String($(this).val() || '');
            if (!normalizeSearchText(value)) {
                closeObsOverlaySuggestions($suggestions, state);
                return;
            }
            renderObsOverlaySuggestions(findObsOverlaySearchMatches(value, $page), $suggestions, state);
        });

        $input.on('focus', function() {
            const value = String($(this).val() || '');
            if (!normalizeSearchText(value)) {
                return;
            }
            renderObsOverlaySuggestions(findObsOverlaySearchMatches(value, $page), $suggestions, state);
        });

        $input.on('keydown', function(e) {
            if (!$suggestions.hasClass('is-visible')) {
                return;
            }
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                moveActiveSuggestion('next');
                return;
            }
            if (e.key === 'ArrowUp') {
                e.preventDefault();
                moveActiveSuggestion('prev');
                return;
            }
            if (e.key === 'Enter') {
                e.preventDefault();
                const index = state.activeIndex >= 0 ? state.activeIndex : 0;
                const match = state.matches[index];
                if (match) {
                    openObsOverlaySearchResult(match, $input, $suggestions, state);
                }
                return;
            }
            if (e.key === 'Escape') {
                closeObsOverlaySuggestions($suggestions, state);
            }
        });

        $suggestions.on('click', '.obs-overlay-search-suggestion', function() {
            const index = Number($(this).data('index'));
            if (!Number.isInteger(index) || !state.matches[index]) {
                return;
            }
            openObsOverlaySearchResult(state.matches[index], $input, $suggestions, state);
        });

        $(document).on('click', function(e) {
            if ($(e.target).closest('.obs-overlay-search-wrap').length) {
                return;
            }
            closeObsOverlaySuggestions($suggestions, state);
        });

        $(document).on('click', pageSelector + ' .obs-overlay-tab', function() {
            closeObsOverlaySuggestions($suggestions, state);
        });
    }

    initObsOverlayAutocomplete();

    function safeParseJson(value) {
        if (!value) {
            return {};
        }
        try {
            return JSON.parse(value);
        } catch (err) {
            return {};
        }
    }

    function clampNumber(value, min, max) {
        const num = Number(value);
        if (!Number.isFinite(num)) {
            return min;
        }
        return Math.min(Math.max(num, min), max);
    }

    function normalizeHexColor(value) {
        const raw = String(value || '').trim().toUpperCase();
        if (!raw) {
            return '';
        }
        return raw[0] === '#' ? raw : ('#' + raw);
    }

    function setColorPicker($picker, color) {
        let normalized = normalizeHexColor(color);
        const defaultLabel = String($picker.data('default-label') || '');
        const defaultColor = String($picker.data('default-color') || '#FFFFFF');
        $picker.find('.obs-color-option').removeClass('is-selected');
        if (normalized) {
            const $match = $picker.find('.obs-color-option[data-color="' + normalized + '"]');
            if ($match.length) {
                $match.addClass('is-selected');
            } else {
                normalized = '';
                $picker.find('.obs-color-option[data-color=""]').addClass('is-selected');
            }
        } else {
            $picker.find('.obs-color-option[data-color=""]').addClass('is-selected');
        }
        $picker.find('.obs-color-value').val(normalized || '');
        $picker.find('.obs-color-code').text(normalized || defaultLabel);
        $picker.find('.obs-color-swatch').css('--swatch', normalized || defaultColor);
    }

    function initColorPickers($editor) {
        $editor.find('.obs-color-picker').each(function() {
            const $picker = $(this);
            $picker.find('.obs-color-option').each(function() {
                const $option = $(this);
                const optionColor = normalizeHexColor($option.data('color'));
                if (optionColor) {
                    $option.css('--swatch', optionColor);
                }
            });
            const currentValue = $picker.find('.obs-color-value').val();
            setColorPicker($picker, currentValue);
        });
        $editor.on('click', '.obs-color-option', function() {
            const $button = $(this);
            const $picker = $button.closest('.obs-color-picker');
            setColorPicker($picker, $button.data('color'));
        });
    }

    function initRangeInputs($editor) {
        $editor.find('.obs-style-range').each(function() {
            const $range = $(this);
            const $slider = $range.find('.obs-style-range-slider');
            const $value = $range.find('.obs-style-range-value');
            if ($slider.length && $value.length) {
                $value.text($slider.val());
            }
        });
        $editor.on('input change', '.obs-style-range-slider', function() {
            const $slider = $(this);
            const $range = $slider.closest('.obs-style-range');
            $range.find('.obs-style-range-input').val($slider.val());
            $range.find('.obs-style-range-value').text($slider.val());
        });
    }

    function initLayoutEditor($editor) {
        const canvas = $editor.find('.obs-layout-canvas')[0];
        if (!canvas) {
            return;
        }
        const baseWidth = parseInt(canvas.getAttribute('data-width'), 10) || 1920;
        const baseHeight = parseInt(canvas.getAttribute('data-height'), 10) || 1080;
        const defaultLayout = safeParseJson($editor.attr('data-default-layout'));
        const storedLayout = safeParseJson($editor.attr('data-layout'));
        const layout = {};
        Object.keys(defaultLayout || {}).forEach((key) => {
            layout[key] = Object.assign({}, defaultLayout[key]);
        });
        Object.keys(storedLayout || {}).forEach((key) => {
            layout[key] = Object.assign({}, layout[key] || {}, storedLayout[key]);
        });
        Object.keys(storedLayout || {}).forEach((key) => {
            const storedEntry = storedLayout[key];
            if (storedEntry && typeof storedEntry.anchor === 'undefined' && layout[key]) {
                layout[key].anchor = 'tl';
            }
        });

        const widgets = Array.from(canvas.querySelectorAll('.obs-layout-widget'));
        const controlMap = {};
        $editor.find('.obs-layout-control').each(function() {
            const $control = $(this);
            const widgetKey = $control.data('widget');
            if (widgetKey) {
                controlMap[widgetKey] = $control;
            }
        });

        function getScaleRatio() {
            const width = canvas.clientWidth;
            if (!width || width <= 0) {
                return 0;
            }
            return width / baseWidth;
        }

        function updateControl(key) {
            const $control = controlMap[key];
            if (!$control) {
                return;
            }
            const entry = layout[key];
            if (!entry) {
                return;
            }
            $control.find('.obs-layout-scale').val(entry.scale);
            $control.find('.obs-layout-zindex').val(typeof entry.zIndex !== 'undefined' ? entry.zIndex : '');
            $control.find('.obs-layout-x').text(entry.x);
            $control.find('.obs-layout-y').text(entry.y);
        }

        function applyWidgetLayout(el) {
            const key = el.getAttribute('data-widget');
            const entry = layout[key];
            if (!entry) {
                return;
            }
            const ratio = getScaleRatio();
            if (ratio <= 0) {
                return;
            }
            const x = Math.round(entry.x);
            const y = Math.round(entry.y);
            const scale = clampNumber(entry.scale, 0.5, 2.0);
            const anchor = String(entry.anchor || 'tl');
            let nextX = x * ratio;
            let nextY = y * ratio;
            const elWidth = el.offsetWidth || 0;
            const elHeight = el.offsetHeight || 0;
            if (anchor.indexOf('r') !== -1) {
                nextX -= elWidth * scale;
            }
            if (anchor.indexOf('b') !== -1) {
                nextY -= elHeight * scale;
            }
            el.style.transform = 'translate(' + nextX + 'px, ' + nextY + 'px) scale(' + scale + ')';
            if (typeof entry.zIndex !== 'undefined') {
                el.style.zIndex = entry.zIndex;
            }
            updateControl(key);
        }

        function refreshAll() {
            if (getScaleRatio() <= 0) {
                return;
            }
            widgets.forEach((el) => {
                applyWidgetLayout(el);
            });
        }

        $editor.data('obsLayoutRefresh', refreshAll);
        refreshAll();
        $(window).on('resize', function() {
            refreshAll();
        });

        let activeDrag = null;

        function onPointerMove(event) {
            if (!activeDrag) {
                return;
            }
            const ratio = getScaleRatio();
            if (ratio <= 0) {
                return;
            }
            const dx = (event.clientX - activeDrag.startClientX) / ratio;
            const dy = (event.clientY - activeDrag.startClientY) / ratio;
            const nextX = clampNumber(activeDrag.startX + dx, 0, baseWidth);
            const nextY = clampNumber(activeDrag.startY + dy, 0, baseHeight);
            layout[activeDrag.key].x = Math.round(nextX);
            layout[activeDrag.key].y = Math.round(nextY);
            applyWidgetLayout(activeDrag.el);
        }

        function onPointerUp(event) {
            if (!activeDrag) {
                return;
            }
            activeDrag.el.classList.remove('obs-layout-dragging');
            if (activeDrag.pointerId !== null) {
                activeDrag.el.releasePointerCapture(activeDrag.pointerId);
            }
            activeDrag = null;
        }

        widgets.forEach((el) => {
            el.addEventListener('pointerdown', (event) => {
                if (event.button && event.button !== 0) {
                    return;
                }
                const key = el.getAttribute('data-widget');
                if (!layout[key]) {
                    layout[key] = { x: 0, y: 0, scale: 1, zIndex: 1 };
                }
                activeDrag = {
                    el: el,
                    key: key,
                    startX: Number(layout[key].x) || 0,
                    startY: Number(layout[key].y) || 0,
                    startClientX: event.clientX,
                    startClientY: event.clientY,
                    pointerId: event.pointerId || null
                };
                el.classList.add('obs-layout-dragging');
                if (activeDrag.pointerId !== null) {
                    el.setPointerCapture(activeDrag.pointerId);
                }
                event.preventDefault();
            });
        });

        document.addEventListener('pointermove', onPointerMove);
        document.addEventListener('pointerup', onPointerUp);

        $editor.on('input change', '.obs-layout-scale', function() {
            const $input = $(this);
            const $control = $input.closest('.obs-layout-control');
            const key = $control.data('widget');
            if (!key) {
                return;
            }
            const scale = clampNumber($input.val(), 0.5, 2.0);
            layout[key] = layout[key] || { x: 0, y: 0, scale: 1, zIndex: 1 };
            layout[key].scale = Math.round(scale * 100) / 100;
            applyWidgetLayout(canvas.querySelector('[data-widget="' + key + '"]'));
        });

        $editor.on('input change', '.obs-layout-zindex', function() {
            const $input = $(this);
            const $control = $input.closest('.obs-layout-control');
            const key = $control.data('widget');
            if (!key) {
                return;
            }
            const zIndex = clampNumber($input.val(), 0, 999);
            layout[key] = layout[key] || { x: 0, y: 0, scale: 1, zIndex: 1 };
            layout[key].zIndex = Math.round(zIndex);
            applyWidgetLayout(canvas.querySelector('[data-widget="' + key + '"]'));
        });

        $editor.find('.obs-layout-reset').on('click', function() {
            Object.keys(layout).forEach((key) => {
                delete layout[key];
            });
            Object.keys(defaultLayout || {}).forEach((key) => {
                layout[key] = Object.assign({}, defaultLayout[key]);
            });
            refreshAll();
        });

        $editor.find('.obs-layout-save').on('click', function() {
            const payload = {};
            Object.keys(layout).forEach((key) => {
                const entry = layout[key];
                if (!entry) {
                    return;
                }
                payload[key] = {
                    x: Math.round(Number(entry.x) || 0),
                    y: Math.round(Number(entry.y) || 0),
                    scale: Math.round((Number(entry.scale) || 1) * 100) / 100
                };
                if (entry.anchor) {
                    payload[key].anchor = entry.anchor;
                }
                if (typeof entry.zIndex !== 'undefined') {
                    payload[key].zIndex = Math.round(Number(entry.zIndex) || 0);
                }
            });
            const $container = $editor;
            const postData = {
                f: 'obsOverlaySaveLayout',
                overlay_token: $editor.attr('data-overlay-token'),
                csrf_token: $editor.attr('data-csrf'),
                layout_json: JSON.stringify(payload)
            };
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: postData,
                beforeSend: function() {
                    clearNotice($container);
                    $container.append(loadingHTML);
                    $container.find('button').prop('disabled', true);
                },
                success: function(data) {
                    $container.find('button').prop('disabled', false);
                    $container.find('.loaderWrapper').remove();
                    let parsed = null;
                    if (typeof data === 'string') {
                        try {
                            parsed = JSON.parse(data);
                        } catch (err) {
                            parsed = null;
                        }
                    } else if (typeof data === 'object') {
                        parsed = data;
                    }
                    if (parsed && parsed.ok) {
                        showNotice($container, $editor.attr('data-saved-message'));
                        return;
                    }
                    showNotice($container, parsed && parsed.error ? parsed.error : data);
                }
            });
        });
    }

    function initStylesEditor($editor) {
        initColorPickers($editor);
        initRangeInputs($editor);
        $editor.find('.obs-styles-save').on('click', function() {
            const styles = {};
            $editor.find('.obs-style-input').each(function() {
                const $input = $(this);
                const widget = $input.data('widget');
                const styleKey = $input.data('style');
                if (!widget || !styleKey) {
                    return;
                }
                const value = String($input.val() || '').trim();
                if (value === '') {
                    return;
                }
                if (!styles[widget]) {
                    styles[widget] = {};
                }
                styles[widget][styleKey] = value;
            });
            const postData = {
                f: 'obsOverlaySaveStyles',
                overlay_token: $editor.attr('data-overlay-token'),
                csrf_token: $editor.attr('data-csrf'),
                styles_json: JSON.stringify(styles)
            };
            $.ajax({
                type: 'POST',
                url: siteurl + 'requests/request.php',
                data: postData,
                beforeSend: function() {
                    clearNotice($editor);
                    $editor.append(loadingHTML);
                    $editor.find('button').prop('disabled', true);
                },
                success: function(data) {
                    $editor.find('button').prop('disabled', false);
                    $editor.find('.loaderWrapper').remove();
                    let parsed = null;
                    if (typeof data === 'string') {
                        try {
                            parsed = JSON.parse(data);
                        } catch (err) {
                            parsed = null;
                        }
                    } else if (typeof data === 'object') {
                        parsed = data;
                    }
                    if (parsed && parsed.ok) {
                        showNotice($editor, $editor.attr('data-saved-message'));
                        return;
                    }
                    showNotice($editor, parsed && parsed.error ? parsed.error : data);
                }
            });
        });
    }

    $('.obs-layout-editor').each(function() {
        initLayoutEditor($(this));
    });

    $('.obs-styles-editor').each(function() {
        initStylesEditor($(this));
    });
})(jQuery);
