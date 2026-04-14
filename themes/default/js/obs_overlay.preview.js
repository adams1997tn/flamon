(() => {
    "use strict";
    const runtime = window.obsOverlayRuntime;
    if (!runtime || typeof runtime.getConfig !== "function") {
        return;
    }
    const config = runtime.getConfig() || {};
    if (!config.isCreatorPreview) {
        return;
    }
    const testToggle = document.getElementById("obsTestToggle");
    const testControls = document.getElementById("obsTestControls");
    const testNotification = document.getElementById("obsTestNotification");
    const testDonation = document.getElementById("obsTestDonation");
    const testMilestone = document.getElementById("obsTestMilestone");
    const testDonationName = document.getElementById("obsTestDonationName");
    const testDonationAmount = document.getElementById("obsTestDonationAmount");
    const testMilestoneProgress = document.getElementById("obsTestMilestoneProgress");
    const testMilestoneGoal = document.getElementById("obsTestMilestoneGoal");
    const milestoneTitleEl = document.getElementById("obsMilestoneTitle");

    if (testToggle && testControls) {
        testToggle.addEventListener("change", function () {
            const enabled = testToggle.checked;
            runtime.setTestMode(enabled);
            testControls.classList.toggle("obs-hidden", !enabled);
            if (!enabled && config.stateEnabled) {
                runtime.pollState();
            }
            if (!enabled && config.alertsEnabled) {
                runtime.pollAlerts();
            }
        });
    }

    if (testNotification) {
        testNotification.addEventListener("click", function () {
            runtime.renderAlert({
                type: "follow",
                from: "Test User"
            });
        });
    }

    if (testDonation) {
        testDonation.addEventListener("click", function () {
            const amount = Number(testDonationAmount && testDonationAmount.value) || 25;
            const name = testDonationName && testDonationName.value ? testDonationName.value : "Test User";
            runtime.updateDonationValue(amount);
            runtime.renderAlert({
                type: "tip",
                from: name
            });
        });
    }

    if (testMilestone) {
        testMilestone.addEventListener("click", function () {
            const progress = Number(testMilestoneProgress && testMilestoneProgress.value) || 250;
            const goal = Number(testMilestoneGoal && testMilestoneGoal.value) || 1000;
            const percent = goal > 0 ? Math.max(0, Math.min(100, (progress / goal) * 100)) : 0;
            runtime.applyState({
                milestone: {
                    goal: goal,
                    progress: progress,
                    percent: percent,
                    title: milestoneTitleEl ? milestoneTitleEl.textContent : ""
                }
            });
        });
    }
})();
