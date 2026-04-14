(function ($) {
    "use strict";

    function initAgencyDirectoryFilters() {
        const $wrapper = $(".agencies_wrapper");
        if (!$wrapper.length) {
            return;
        }
        const $grid = $wrapper.find(".agencies_grid");
        const $cards = $grid.find("[data-agency-card]");
        const $searchInput = $wrapper.find(".agencies_search_input");
        const $sortSelect = $wrapper.find(".agencies_sort_select");
        const $emptyState = $grid.find(".agency_empty_state");
        let filterTimer = null;

        function normalize(value) {
            return String(value || "").toLowerCase();
        }

        function sortCards() {
            if (!$cards.length) {
                return;
            }
            const sortValue = $sortSelect.length ? String($sortSelect.val() || "new") : "new";
            const cards = $cards.get();
            cards.sort(function (a, b) {
                const $a = $(a);
                const $b = $(b);
                const createdA = parseInt($a.data("created"), 10) || 0;
                const createdB = parseInt($b.data("created"), 10) || 0;
                const feeA = parseFloat($a.data("fee")) || 0;
                const feeB = parseFloat($b.data("fee")) || 0;
                const titleA = normalize($a.data("title"));
                const titleB = normalize($b.data("title"));
                if (sortValue === "name") {
                    return titleA.localeCompare(titleB);
                }
                if (sortValue === "fee_low") {
                    return feeA - feeB;
                }
                if (sortValue === "fee_high") {
                    return feeB - feeA;
                }
                return createdB - createdA;
            });
            const $empty = $grid.find(".agency_empty_state");
            $empty.before(cards);
        }

        function filterCards() {
            const query = normalize($searchInput.val());
            let visibleCount = 0;

            $cards.each(function () {
                const $card = $(this);
                const title = normalize($card.data("title"));
                const owner = normalize($card.data("owner"));
                const matches = !query || title.indexOf(query) !== -1 || owner.indexOf(query) !== -1;
                $card.toggleClass("is-hidden", !matches);
                if (matches) {
                    visibleCount += 1;
                }
            });

            if ($emptyState.length) {
                $emptyState.toggleClass("is-hidden", visibleCount > 0);
            }
        }

        function scheduleFilter() {
            if (filterTimer) {
                clearTimeout(filterTimer);
            }
            filterTimer = setTimeout(function () {
                sortCards();
                filterCards();
            }, 120);
        }

        if ($searchInput.length) {
            $searchInput.on("input", scheduleFilter);
        }
        if ($sortSelect.length) {
            $sortSelect.on("change", scheduleFilter);
        }

        sortCards();
        filterCards();
    }

    $(initAgencyDirectoryFilters);
})(jQuery);
