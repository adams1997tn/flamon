(() => {
    "use strict";
    const configEl = document.getElementById("obsOverlayConfig");
    if (!configEl) {
        return;
    }
    let payload = null;
    try {
        payload = JSON.parse(configEl.textContent || "{}");
    } catch (err) {
        payload = null;
    }
    if (!payload || typeof payload !== "object") {
        return;
    }

    const config = payload.config && typeof payload.config === "object" ? payload.config : {};
    const layoutConfig = payload.layout && typeof payload.layout === "object" ? payload.layout : {};
    const stylesConfig = payload.styles && typeof payload.styles === "object" ? payload.styles : {};
    const settingsConfig = payload.settings && typeof payload.settings === "object" ? payload.settings : {};
    const alertTemplates = payload.templates && typeof payload.templates === "object" ? payload.templates : {};
    const currencyConfig = payload.currency && typeof payload.currency === "object" ? payload.currency : {};
    const anonymousLabel = typeof payload.anonymousLabel === "string" ? payload.anonymousLabel : "Anonymous";
    let lastAlertId = Number(payload.initialLastAlertId) || 0;

    const donationEl = document.getElementById("obsDonationValue");
    const milestoneValueEl = document.getElementById("obsMilestoneValue");
    const milestoneBarEl = document.getElementById("obsMilestoneBar");
    const milestoneTitleEl = document.getElementById("obsMilestoneTitle");
    const alertsEl = document.getElementById("obsAlerts");
    const notificationListEl = document.getElementById("obsNotificationList");
    const leaderboardListEl = document.getElementById("obsLeaderboardList");
    const leaderboardLabelEl = document.getElementById("obsLeaderboardLabel");
    const targetGoalLabelEl = document.getElementById("obsTargetGoalLabel");
    const targetGoalValueEl = document.getElementById("obsTargetGoalValue");
    const targetGoalBarEl = document.getElementById("obsTargetGoalBar");
    const lastSupporterLabelEl = document.getElementById("obsLastSupporterLabel");
    const lastSupporterValueEl = document.getElementById("obsLastSupporterValue");
    const runningTextValueEl = document.getElementById("obsRunningTextValue");
    const liveDurationValueEl = document.getElementById("obsLiveDurationValue");
    const ctaButton = document.getElementById("obsCtaButton");
    const seenLiveIds = new Set();
    const overlayEl = document.querySelector(".obs-overlay");
    const widgetEls = {
        donation_total: document.querySelector('[data-obs-widget="donation_total"]'),
        alerts: document.querySelector('[data-obs-widget="alerts"]'),
        milestone: document.querySelector('[data-obs-widget="milestone"]'),
        cta: document.querySelector('[data-obs-widget="cta"]'),
        watermark: document.querySelector('[data-obs-widget="watermark"]'),
        notification_box: document.querySelector('[data-obs-widget="notification_box"]'),
        leaderboard: document.querySelector('[data-obs-widget="leaderboard"]'),
        target_goal: document.querySelector('[data-obs-widget="target_goal"]'),
        last_supporter: document.querySelector('[data-obs-widget="last_supporter"]'),
        running_text: document.querySelector('[data-obs-widget="running_text"]'),
        live_duration: document.querySelector('[data-obs-widget="live_duration"]')
    };
    let testModeEnabled = false;
    let runningTextCache = "";
    let liveRemaining = 0;
    let liveTickerTimer = null;

    function clampNumber(value, min, max) {
        const num = Number(value);
        if (!Number.isFinite(num)) {
            return min;
        }
        return Math.min(Math.max(num, min), max);
    }

    function normalizeHexColor(value) {
        const color = String(value || "").trim().toUpperCase();
        if (!/^#[0-9A-F]{6}$/.test(color)) {
            return "";
        }
        return color;
    }

    function hexToRgba(hex, opacity) {
        const clean = normalizeHexColor(hex);
        if (!clean) {
            return "";
        }
        const intVal = parseInt(clean.slice(1), 16);
        const r = (intVal >> 16) & 255;
        const g = (intVal >> 8) & 255;
        const b = intVal & 255;
        const alpha = clampNumber(opacity, 0, 100) / 100;
        return "rgba(" + r + ", " + g + ", " + b + ", " + alpha + ")";
    }

    function stripTags(value) {
        return String(value || "").replace(/<[^>]*>/g, "");
    }

    function normalizeTypes(value) {
        const allowed = ["tips", "live_gift"];
        const types = [];
        if (Array.isArray(value)) {
            value.forEach((entry) => {
                if (allowed.indexOf(entry) !== -1) {
                    types.push(entry);
                }
            });
        }
        return types.length ? Array.from(new Set(types)) : allowed;
    }

    function normalizeSettings(raw) {
        const source = raw && typeof raw === "object" ? raw : {};
        const hasKey = (key) => Object.prototype.hasOwnProperty.call(source, key);
        const tiersRaw = hasKey("notification_tiers") && Array.isArray(source.notification_tiers)
            ? source.notification_tiers
            : [];
        const tiers = tiersRaw.map((tier) => {
            const minAmount = clampNumber(tier && tier.min_amount, 0, 999999);
            const label = stripTags(tier && tier.label ? tier.label : "").slice(0, 60);
            const classKey = stripTags(tier && tier.class_key ? tier.class_key : "").replace(/[^A-Za-z0-9_-]/g, "").slice(0, 40);
            return { min_amount: minAmount, label: label, class_key: classKey };
        }).filter((tier) => tier.label || tier.class_key || tier.min_amount > 0);
        tiers.sort((a, b) => a.min_amount - b.min_amount);

        const leaderboardRaw = hasKey("leaderboard") && source.leaderboard && typeof source.leaderboard === "object"
            ? source.leaderboard
            : null;
        const targetGoalRaw = hasKey("target_goal") && source.target_goal && typeof source.target_goal === "object"
            ? source.target_goal
            : null;
        const lastSupporterRaw = hasKey("last_supporter") && source.last_supporter && typeof source.last_supporter === "object"
            ? source.last_supporter
            : null;
        const runningTextRaw = hasKey("running_text") && source.running_text && typeof source.running_text === "object"
            ? source.running_text
            : null;
        const liveExtenderRaw = hasKey("live_extender") && source.live_extender && typeof source.live_extender === "object"
            ? source.live_extender
            : null;

        return {
            notification: {
                enabled: hasKey("notification_tiers"),
                tiers: tiers
            },
            leaderboard: {
                enabled: !!leaderboardRaw,
                limit: leaderboardRaw ? clampNumber(leaderboardRaw.limit, 1, 10) : 5,
                mode: leaderboardRaw && ["last24h", "alltime", "session"].indexOf(leaderboardRaw.mode) !== -1 ? leaderboardRaw.mode : "last24h",
                include_types: leaderboardRaw ? normalizeTypes(leaderboardRaw.include_types) : ["tips", "live_gift"]
            },
            target_goal: {
                enabled: !!targetGoalRaw,
                title: targetGoalRaw ? stripTags(targetGoalRaw.title || "").slice(0, 140) : "",
                goal_amount: targetGoalRaw ? clampNumber(targetGoalRaw.goal_amount, 0, 999999) : 0,
                mode: targetGoalRaw && ["last24h", "alltime", "session"].indexOf(targetGoalRaw.mode) !== -1 ? targetGoalRaw.mode : "last24h",
                include_types: targetGoalRaw ? normalizeTypes(targetGoalRaw.include_types) : ["tips", "live_gift"]
            },
            last_supporter: {
                enabled: !!lastSupporterRaw,
                label: lastSupporterRaw ? stripTags(lastSupporterRaw.label || "").slice(0, 120) : "",
                show_amount: lastSupporterRaw ? !!lastSupporterRaw.show_amount : false,
                mode: lastSupporterRaw && ["last24h", "alltime", "session"].indexOf(lastSupporterRaw.mode) !== -1 ? lastSupporterRaw.mode : "last24h",
                include_types: lastSupporterRaw ? normalizeTypes(lastSupporterRaw.include_types) : ["tips", "live_gift"]
            },
            running_text: {
                enabled: !!runningTextRaw,
                mode: runningTextRaw && ["custom", "recent", "leaderboard"].indexOf(runningTextRaw.mode) !== -1 ? runningTextRaw.mode : "custom",
                template: runningTextRaw ? stripTags(runningTextRaw.template || "").slice(0, 200) : "",
                custom_text: runningTextRaw ? stripTags(runningTextRaw.custom_text || "").slice(0, 220) : "",
                speed: runningTextRaw ? clampNumber(runningTextRaw.speed, 5, 120) : 30
            },
            live_extender: {
                enabled: !!liveExtenderRaw
            }
        };
    }

    const isPreview = document.body.classList.contains("obs-overlay-preview");
    const settings = normalizeSettings(settingsConfig || {});
    const notificationEnabled = !!(config.widgets && config.widgets.notification_box && settings.notification.enabled);
    const alertsEnabled = !!(config.widgets && config.widgets.alerts);
    const leaderboardEnabled = !!(config.widgets && config.widgets.leaderboard && settings.leaderboard.enabled);
    const targetGoalEnabled = !!(config.widgets && config.widgets.target_goal && settings.target_goal.enabled);
    const lastSupporterEnabled = !!(config.widgets && config.widgets.last_supporter && settings.last_supporter.enabled);
    const runningTextEnabled = !!(config.widgets && config.widgets.running_text && settings.running_text.enabled);
    const liveDurationEnabled = !!(config.widgets && config.widgets.live_duration);

    if (widgetEls.notification_box) {
        widgetEls.notification_box.classList.toggle("obs-hidden", !notificationEnabled);
    }
    if (widgetEls.leaderboard) {
        widgetEls.leaderboard.classList.toggle("obs-hidden", !leaderboardEnabled);
    }
    if (widgetEls.target_goal) {
        widgetEls.target_goal.classList.toggle("obs-hidden", !targetGoalEnabled);
    }
    if (widgetEls.last_supporter) {
        widgetEls.last_supporter.classList.toggle("obs-hidden", !lastSupporterEnabled);
    }
    if (widgetEls.running_text) {
        widgetEls.running_text.classList.toggle("obs-hidden", !runningTextEnabled);
    }
    if (widgetEls.live_duration) {
        widgetEls.live_duration.classList.toggle("obs-hidden", !liveDurationEnabled);
    }

    const pollAlertsEnabled = notificationEnabled || alertsEnabled;
    const pollStateEnabled = !!config.stateEnabled;

    function applyLayoutConfig() {
        if (!overlayEl || !layoutConfig || !Object.keys(layoutConfig).length) {
            return;
        }
        const baseWidth = Number(config.layoutBase && config.layoutBase.width) || 1920;
        const baseHeight = Number(config.layoutBase && config.layoutBase.height) || 1080;
        const scaleX = overlayEl.clientWidth / baseWidth;
        const scaleY = overlayEl.clientHeight / baseHeight;
        const minPreviewScale = isPreview ? 0.4 : 0;
        const layoutScale = Math.max(Math.min(scaleX, scaleY), minPreviewScale);
        Object.keys(layoutConfig).forEach((key) => {
            const entry = layoutConfig[key];
            const el = widgetEls[key];
            if (!el || !entry || typeof entry !== "object") {
                return;
            }
            const x = Number(entry.x);
            const y = Number(entry.y);
            if (!Number.isFinite(x) || !Number.isFinite(y)) {
                return;
            }
            const widgetScale = clampNumber(entry.scale, 0.5, 2.0);
            const anchor = String(entry.anchor || "tl");
            const elWidth = el.offsetWidth || 0;
            const elHeight = el.offsetHeight || 0;
            const scaledWidth = elWidth * widgetScale * layoutScale;
            const scaledHeight = elHeight * widgetScale * layoutScale;
            let nextX = x * scaleX;
            let nextY = y * scaleY;
            if (anchor.indexOf("r") !== -1) {
                nextX -= scaledWidth;
            }
            if (anchor.indexOf("b") !== -1) {
                nextY -= scaledHeight;
            }
            el.style.position = "absolute";
            el.style.top = "0px";
            el.style.left = "0px";
            el.style.right = "auto";
            el.style.bottom = "auto";
            el.style.transformOrigin = "top left";
            el.style.transform = "translate(" + nextX + "px, " + nextY + "px) scale(" + (widgetScale * layoutScale) + ")";
            if (Number.isFinite(Number(entry.zIndex))) {
                el.style.zIndex = Number(entry.zIndex);
            }
        });
    }

    function applyWidgetStyle(key, entry, targetEl) {
        if (!entry || typeof entry !== "object" || !targetEl) {
            return;
        }
        const textColor = normalizeHexColor(entry.textColor);
        if (textColor) {
            targetEl.style.color = textColor;
        }
        const fontSize = Number(entry.fontSize);
        if (Number.isFinite(fontSize)) {
            targetEl.style.fontSize = fontSize + "px";
        }
        const borderRadius = Number(entry.borderRadius);
        if (Number.isFinite(borderRadius)) {
            targetEl.style.borderRadius = borderRadius + "px";
        }
        const textAlign = String(entry.textAlign || "");
        if (["left", "center", "right"].indexOf(textAlign) !== -1) {
            targetEl.style.textAlign = textAlign;
        }
        const bgOpacity = typeof entry.bgOpacity !== "undefined" ? entry.bgOpacity : "";
        const bgColor = normalizeHexColor(entry.bgColor || "");
        const baseColor = bgColor || "#0C0E14";
        if (bgColor || bgOpacity !== "") {
            const rgba = hexToRgba(baseColor, bgOpacity === "" ? 100 : bgOpacity);
            if (rgba) {
                targetEl.style.backgroundColor = rgba;
                targetEl.style.backgroundImage = "none";
            }
        }
    }

    function applyStylesConfig() {
        if (!stylesConfig || !Object.keys(stylesConfig).length) {
            return;
        }
        Object.keys(stylesConfig).forEach((key) => {
            const entry = stylesConfig[key];
            if (key === "cta" && ctaButton) {
                applyWidgetStyle(key, entry, ctaButton);
                return;
            }
            if (key === "alerts" && alertsEl) {
                applyWidgetStyle(key, entry, alertsEl);
                return;
            }
            applyWidgetStyle(key, entry, widgetEls[key]);
        });
    }

    function applyAlertStyle(el) {
        if (!stylesConfig || !stylesConfig.alerts) {
            return;
        }
        applyWidgetStyle("alerts", stylesConfig.alerts, el);
    }

    function formatCurrency(amount, decimalsOverride) {
        const cfg = currencyConfig || {};
        const symbol = typeof cfg.symbol === "string" ? cfg.symbol : "";
        const position = cfg.position === "right" ? "right" : "left";
        const thousandSeparator = typeof cfg.thousandSeparator === "string" ? cfg.thousandSeparator : ",";
        const decimalSeparator = typeof cfg.decimalSeparator === "string" ? cfg.decimalSeparator : ".";
        const parsedDecimals = typeof decimalsOverride === "number" && decimalsOverride >= 0
            ? decimalsOverride
            : parseInt(cfg.decimals, 10);
        const decimals = Number.isFinite(parsedDecimals) ? Math.min(Math.max(parsedDecimals, 0), 4) : 2;
        const number = Number(amount) || 0;
        const fixed = number.toFixed(decimals);
        const parts = fixed.split(".");
        let integerPart = parts[0];
        const decimalPart = decimals > 0 && parts[1] ? decimalSeparator + parts[1] : "";
        if (thousandSeparator) {
            const rgx = /(\d+)(\d{3})/;
            while (rgx.test(integerPart)) {
                integerPart = integerPart.replace(rgx, "$1" + thousandSeparator + "$2");
            }
        }
        const formatted = integerPart + decimalPart;
        if (position === "right") {
            return (formatted + (symbol ? " " + symbol : "")).trim();
        }
        return (symbol + formatted).trim();
    }

    function clearChildren(el) {
        if (!el) {
            return;
        }
        while (el.firstChild) {
            el.removeChild(el.firstChild);
        }
    }

    function formatDuration(totalSeconds) {
        const seconds = Math.max(0, Math.floor(Number(totalSeconds) || 0));
        const hrs = Math.floor(seconds / 3600);
        const mins = Math.floor((seconds % 3600) / 60);
        const secs = seconds % 60;
        if (hrs > 0) {
            return hrs + ":" + String(mins).padStart(2, "0") + ":" + String(secs).padStart(2, "0");
        }
        return mins + ":" + String(secs).padStart(2, "0");
    }

    function getNotificationTier(amount) {
        const tiers = settings.notification && Array.isArray(settings.notification.tiers)
            ? settings.notification.tiers
            : [];
        let matched = null;
        const numericAmount = Number(amount) || 0;
        tiers.forEach((tier) => {
            if (numericAmount >= Number(tier.min_amount) && (!matched || Number(tier.min_amount) >= Number(matched.min_amount))) {
                matched = tier;
            }
        });
        return matched;
    }

    function renderNotification(event) {
        if (!notificationListEl || !event) {
            return;
        }
        const name = stripTags(event.name || "") || anonymousLabel;
        const amount = formatCurrency(event.amount || 0);
        const tier = getNotificationTier(event.amount || 0);
        const item = document.createElement("div");
        item.className = "obs-notification-item";
        if (tier && tier.class_key) {
            item.className += " " + tier.class_key;
        }
        const title = document.createElement("div");
        title.className = "obs-notification-title";
        title.textContent = name;
        const value = document.createElement("div");
        value.className = "obs-notification-amount";
        value.textContent = amount;
        item.appendChild(title);
        item.appendChild(value);
        if (tier && tier.label) {
            const label = document.createElement("div");
            label.className = "obs-notification-label";
            label.textContent = tier.label;
            item.appendChild(label);
        }
        notificationListEl.appendChild(item);
        if (notificationListEl.children.length > 3) {
            notificationListEl.removeChild(notificationListEl.firstElementChild);
        }
        setTimeout(() => {
            item.classList.add("obs-alert-out");
            setTimeout(() => {
                if (item.parentNode) {
                    item.parentNode.removeChild(item);
                }
            }, 500);
        }, 5200);
    }

    function applyLeaderboard(entries) {
        if (!leaderboardListEl) {
            return;
        }
        clearChildren(leaderboardListEl);
        if (!Array.isArray(entries) || !entries.length) {
            return;
        }
        entries.forEach((entry, index) => {
            const row = document.createElement("div");
            row.className = "obs-leaderboard-item";
            const left = document.createElement("span");
            left.className = "obs-leaderboard-name";
            left.textContent = String(index + 1) + ". " + (stripTags(entry.name || "") || anonymousLabel);
            const amount = document.createElement("span");
            amount.className = "obs-leaderboard-amount";
            amount.textContent = formatCurrency(entry.amount || 0);
            row.appendChild(left);
            row.appendChild(amount);
            leaderboardListEl.appendChild(row);
        });
    }

    function applyTargetGoal(data) {
        if (!data || !targetGoalValueEl || !targetGoalBarEl) {
            return;
        }
        const goal = Number(data.goal) || 0;
        const progress = Number(data.progress) || 0;
        const percent = Math.max(0, Math.min(100, Number(data.percent) || 0));
        targetGoalValueEl.textContent = formatCurrency(progress) + " / " + formatCurrency(goal);
        targetGoalBarEl.style.width = percent + "%";
        if (targetGoalLabelEl && data.title) {
            targetGoalLabelEl.textContent = stripTags(data.title || "");
        }
    }

    function applyLastSupporter(data) {
        if (!data || !lastSupporterValueEl) {
            return;
        }
        const name = stripTags(data.name || "") || anonymousLabel;
        const showAmount = !!data.show_amount;
        const amount = showAmount ? " · " + formatCurrency(data.amount || 0) : "";
        lastSupporterValueEl.textContent = name + amount;
        if (lastSupporterLabelEl && data.label) {
            lastSupporterLabelEl.textContent = stripTags(data.label || "");
        }
    }

    function applyRunningText(data) {
        if (!data || !runningTextValueEl) {
            return;
        }
        const text = stripTags(data.text || "");
        if (text === "") {
            runningTextCache = "";
            runningTextValueEl.textContent = "";
            return;
        }
        if (text === runningTextCache) {
            return;
        }
        runningTextCache = text;
        runningTextValueEl.textContent = text;
        const speed = clampNumber(data.speed, 5, 120);
        runningTextValueEl.style.animationDuration = speed + "s";
        runningTextValueEl.style.animationName = "none";
        void runningTextValueEl.offsetHeight;
        runningTextValueEl.style.animationName = "";
    }

    function syncLiveDuration(data) {
        if (!data || !liveDurationValueEl) {
            return;
        }
        liveRemaining = Math.max(0, Math.floor(Number(data.remaining_seconds) || 0));
        liveDurationValueEl.textContent = formatDuration(liveRemaining);
        if (liveTickerTimer) {
            clearInterval(liveTickerTimer);
        }
        liveTickerTimer = setInterval(() => {
            liveRemaining = Math.max(0, liveRemaining - 1);
            liveDurationValueEl.textContent = formatDuration(liveRemaining);
        }, 1000);
    }

    function applyState(data) {
        if (!data || typeof data !== "object") {
            return;
        }
        if (donationEl && typeof data.donation_total_last24h !== "undefined") {
            donationEl.textContent = formatCurrency(data.donation_total_last24h);
        }
        if (data.milestone && milestoneValueEl && milestoneBarEl) {
            const goal = Number(data.milestone.goal) || 0;
            const progress = Number(data.milestone.progress) || 0;
            const percent = Math.max(0, Math.min(100, Number(data.milestone.percent) || 0));
            milestoneValueEl.textContent = formatCurrency(progress) + " / " + formatCurrency(goal);
            milestoneBarEl.style.width = percent + "%";
            if (milestoneTitleEl && data.milestone.title) {
                milestoneTitleEl.textContent = stripTags(data.milestone.title);
            }
        }
        if (leaderboardEnabled && data.leaderboard && Array.isArray(data.leaderboard.entries)) {
            applyLeaderboard(data.leaderboard.entries);
        }
        if (targetGoalEnabled && data.target_goal) {
            applyTargetGoal(data.target_goal);
        }
        if (lastSupporterEnabled && data.last_supporter) {
            applyLastSupporter(data.last_supporter);
        }
        if (runningTextEnabled && data.running_text) {
            applyRunningText(data.running_text);
        }
        if (liveDurationEnabled && data.live_duration) {
            syncLiveDuration(data.live_duration);
        }
    }

    function buildAlertText(alert) {
        if (!alert || !alert.type) {
            return "";
        }
        let template = alertTemplates[alert.type] || "";
        if (!template) {
            return "";
        }
        if (template.indexOf("{name}") !== -1) {
            const name = alert.from ? String(alert.from) : "";
            template = template.replace("{name}", name);
        }
        return template;
    }

    function renderAlert(alert) {
        const text = buildAlertText(alert);
        if (!text || !alertsEl) {
            return;
        }
        const el = document.createElement("div");
        el.className = "obs-alert";
        el.textContent = text;
        applyAlertStyle(el);
        alertsEl.appendChild(el);
        if (alertsEl.children.length > 3) {
            alertsEl.removeChild(alertsEl.firstElementChild);
        }
        setTimeout(() => {
            el.classList.add("obs-alert-out");
            setTimeout(() => {
                if (el.parentNode) {
                    el.parentNode.removeChild(el);
                }
            }, 500);
        }, 5200);
    }

    async function fetchJson(url) {
        const response = await fetch(url, { cache: "no-store" });
        if (!response.ok) {
            return null;
        }
        return response.json();
    }

    async function pollState() {
        try {
            if (testModeEnabled) {
                return;
            }
            const data = await fetchJson(config.stateUrl);
            if (data && data.ok) {
                applyState(data);
            }
        } catch (err) {
            // Ignore polling errors.
        }
    }

    async function pollAlerts() {
        try {
            if (testModeEnabled) {
                return;
            }
            const url = config.alertsUrl + "?last_id=" + encodeURIComponent(lastAlertId);
            const data = await fetchJson(url);
            if (!data || !data.ok || !Array.isArray(data.events)) {
                return;
            }
            if (typeof data.new_last_id === "number") {
                lastAlertId = Math.max(lastAlertId, data.new_last_id);
            }
            data.events.forEach((event) => {
                if (!event || !event.type) {
                    return;
                }
                if (notificationEnabled) {
                    renderNotification(event);
                    return;
                }
                if (alertsEnabled) {
                    const type = event.type === "tips" || event.type === "live_gift" ? "tip" : event.type;
                    renderAlert({
                        type: type,
                        from: stripTags(event.name || "") || anonymousLabel,
                        time: event.time
                    });
                }
            });
        } catch (err) {
            // Ignore polling errors.
        }
    }

    function updateDonationValue(amount) {
        if (donationEl) {
            donationEl.textContent = formatCurrency(amount);
        }
    }

    if (ctaButton && config.ctaEnabled) {
        ctaButton.addEventListener("click", async function (event) {
            event.preventDefault();
            try {
                const data = await fetchJson(config.clickUrl);
                if (data && data.ok && data.redirect) {
                    const win = window.open(data.redirect, "_blank");
                    if (!win) {
                        window.location.href = data.redirect;
                    }
                }
            } catch (err) {
                // Ignore click errors.
            }
        });
    }

    if (pollStateEnabled) {
        pollState();
        setInterval(pollState, config.pollStateMs);
    }
    if (pollAlertsEnabled) {
        pollAlerts();
        setInterval(pollAlerts, config.pollAlertsMs);
    }
    applyLayoutConfig();
    applyStylesConfig();
    if (Object.keys(layoutConfig || {}).length) {
        window.addEventListener("resize", applyLayoutConfig);
    }

    window.obsOverlayRuntime = {
        applyState: applyState,
        formatCurrency: formatCurrency,
        pollAlerts: pollAlerts,
        pollState: pollState,
        renderAlert: renderAlert,
        setTestMode: function (flag) {
            testModeEnabled = !!flag;
        },
        updateDonationValue: updateDonationValue,
        getConfig: function () {
            return config;
        }
    };
})();
