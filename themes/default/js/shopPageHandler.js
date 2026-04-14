(function($) {
    "use strict";

    if (window.__shopPageHandlerInitialized) {
        return;
    }
    window.__shopPageHandlerInitialized = true;

    var preLoadingAnimation = '<div class="i_loading"><div class="dot-pulse"></div></div>';
    var scrollLoad = true;
    var lastRequestedId = null;
    var observer = null;
    var sentinel = null;

    function initObserver() {
        sentinel = document.getElementById("marketplaceSentinel");
        if (!sentinel || typeof IntersectionObserver === "undefined") {
            // If we cannot observe, fall back to a single manual attempt.
            showMoreProduct();
            return;
        }
        observer = new IntersectionObserver(handleIntersection, {
            root: null,
            rootMargin: "200px 0px",
            threshold: 0
        });
        observer.observe(sentinel);
    }

    function handleIntersection(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                observer.unobserve(entry.target);
                showMoreProduct();
            }
        });
    }

    function stopObservingIfNeeded() {
        if (observer && sentinel) {
            observer.disconnect();
        }
    }

    function resumeObserving() {
        if (observer && sentinel) {
            observer.observe(sentinel);
        }
    }

    function showMoreProduct() {
        var moreTypeElement = document.getElementById("moreTypeContainer");
        if (!moreTypeElement) {
            return;
        }

        var moreType = moreTypeElement.getAttribute("data-moretype") || '';
        var $container = $('#moreType');
        var $lastItem = $container.children('.mor').last();
        var lastId = $lastItem.attr('data-last');
        var hasReachedEnd = $('.nomore, .nmr, .no_creator_f_wrap').length > 0;

        if (!lastId || !scrollLoad || hasReachedEnd) {
            if (hasReachedEnd) {
                stopObservingIfNeeded();
            }
            return;
        }

        if (lastRequestedId !== null && lastRequestedId === lastId) {
            return;
        }

        scrollLoad = false;
        var loader = $(preLoadingAnimation).addClass('shop_more_loading');
        $container.append(loader);
        var initialCount = $container.children('.mor').length;
        var data = {
            f: 'mrProduct',
            last: lastId,
            ty: moreType
        };

        $.ajax({
            type: "POST",
            url: siteurl + 'requests/request.php',
            data: data,
            cache: false,
            success: function(response) {
                if (response && !$(".nomore")[0]) {
                    var appendedNew = false;
                    var appendedEndMessage = false;
                    var nodes = $.parseHTML(response, document, true) || [];
                    if (!nodes.length) {
                        nodes = [response];
                    }
                    nodes.forEach(function(node) {
                        if (!node) {
                            return;
                        }
                        if (node.nodeType === 3 && !node.textContent.trim()) {
                            return;
                        }
                        var $node = $(node);
                        if ($node.hasClass('nmr') || $node.hasClass('nomore')) {
                            $container.find('.nmr, .nomore').remove();
                            $container.append($node);
                            appendedEndMessage = true;
                            return;
                        }
                        if ($node.hasClass('s_p_product_container') && $node.hasClass('mor')) {
                            var productId = $node.attr('id') || $node.data('last');
                            if (productId && document.getElementById(productId)) {
                                return;
                            }
                            appendedNew = true;
                            $container.append($node);
                        } else {
                            $container.append($node);
                        }
                    });
                    if (appendedEndMessage) {
                        stopObservingIfNeeded();
                        lastRequestedId = lastId;
                        scrollLoad = false;
                        return;
                    }
                    if (appendedNew || $container.children('.mor').length > initialCount) {
                        scrollLoad = true;
                        lastRequestedId = null;
                    } else {
                        lastRequestedId = lastId;
                    }
                } else if ($(".nomore")[0]) {
                    stopObservingIfNeeded();
                }
            },
            complete: function() {
                loader.remove();
                scrollLoad = true;
                if ($('.nomore, .nmr, .no_creator_f_wrap').length === 0) {
                    resumeObserving();
                }
            }
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initObserver);
    } else {
        initObserver();
    }

    $(document).on("click", ".settings_mobile_menu_container", function() {
        if (!$(".settingsMenuDisplay")[0]) {
            $(".i_shopping_menu_wrapper").addClass("settingsMenuDisplay");
        } else {
            $(".i_shopping_menu_wrapper").removeClass("settingsMenuDisplay");
        }
    });

})(jQuery);
