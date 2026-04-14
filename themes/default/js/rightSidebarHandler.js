(function ($) {
  "use strict";

  // Context menu block (for non-creators)
  if (window.userIsLoggedIn && !window.userIsCreator) {
    document.addEventListener("contextmenu", function (event) {
      event.preventDefault();
    });
  }

  /**
   * Right sidebar behavior:
   * Keep sidebar fixed under header, and sync window scroll to sidebar's own
   * internal scroll so it ends exactly at bottom without leaving empty space.
   */
  (function initRightStickyLimiter() {
    var TOP_OFFSET = 88;
    var BOTTOM_GAP = 0;
    var MOBILE_BREAKPOINT = 800;
    var state = {
      inner: null,
      right: null,
      wrapper: null,
      rightTop: 0,
      rightLeft: 0,
      rightWidth: 0,
      lockStart: 0,
      maxScroll: 0,
      locked: false,
      enabled: false,
    };
    var ticking = false;

    function pageOffsetTop(el) {
      var rect = el.getBoundingClientRect();
      return rect.top + (window.pageYOffset || document.documentElement.scrollTop || 0);
    }

    function resetInlineStyles() {
      if (!state.inner || !state.right || !state.wrapper) {
        return;
      }
      state.inner.classList.remove("rs-locked");
      state.inner.style.removeProperty("--rs-lock-top");
      state.inner.style.removeProperty("--rs-lock-left");
      state.inner.style.removeProperty("--rs-lock-width");
      state.inner.style.removeProperty("--rs-shift");
      state.right.style.removeProperty("--rs-space");
      state.wrapper.style.removeProperty("height");
      state.wrapper.style.removeProperty("max-height");
      state.wrapper.style.removeProperty("box-sizing");
      state.wrapper.style.removeProperty("overflow-y");
      state.wrapper.style.removeProperty("overflow-x");
      state.wrapper.style.removeProperty("overscroll-behavior");
      state.wrapper.style.removeProperty("padding-bottom");
      state.wrapper.scrollTop = 0;
      state.locked = false;
    }

    function collect() {
      var right = document.querySelector(".rightSticky");
      var inner = right ? right.querySelector(".i_right_container") : null;
      var wrapper = inner ? inner.querySelector(".leftSidebarWrapper") : null;
      if (!right || !inner || !wrapper) {
        state.inner = null;
        state.right = null;
        state.wrapper = null;
        state.locked = false;
        state.enabled = false;
        return;
      }

      state.inner = inner;
      state.right = right;
      state.wrapper = wrapper;
      resetInlineStyles();

      if (window.innerWidth <= MOBILE_BREAKPOINT) {
        state.enabled = false;
        return;
      }

      var viewportFreeHeight = window.innerHeight - TOP_OFFSET - BOTTOM_GAP;
      if (viewportFreeHeight <= 0) {
        state.enabled = false;
        return;
      }

      wrapper.style.height = viewportFreeHeight + "px";
      wrapper.style.maxHeight = viewportFreeHeight + "px";
      wrapper.style.boxSizing = "border-box";
      wrapper.style.overflowY = "auto";
      wrapper.style.overflowX = "hidden";
      wrapper.style.overscrollBehavior = "contain";
      wrapper.style.paddingBottom = "0px";

      var maxScroll = Math.max(wrapper.scrollHeight - wrapper.clientHeight, 0);
      if (maxScroll <= 0) {
        state.enabled = false;
        return;
      }

      var rightRect = right.getBoundingClientRect();
      state.rightTop = pageOffsetTop(right);
      state.rightLeft = rightRect.left;
      state.rightWidth = rightRect.width;
      state.lockStart = state.rightTop - TOP_OFFSET;
      state.maxScroll = maxScroll;
      state.enabled = true;
      apply();
    }

    function apply() {
      if (!state.enabled || !state.inner || !state.right || !state.wrapper) {
        return;
      }
      var scY = window.pageYOffset || document.documentElement.scrollTop || 0;
      var desired = scY - state.lockStart;

      if (desired <= 0) {
        if (state.locked) {
          state.inner.classList.remove("rs-locked");
          state.inner.style.removeProperty("--rs-lock-top");
          state.inner.style.removeProperty("--rs-lock-left");
          state.inner.style.removeProperty("--rs-lock-width");
          state.right.style.removeProperty("--rs-space");
          state.locked = false;
        }
        state.wrapper.scrollTop = 0;
        return;
      }

      if (!state.locked) {
        state.inner.classList.add("rs-locked");
        state.inner.style.setProperty("--rs-lock-top", TOP_OFFSET + "px");
        state.inner.style.setProperty("--rs-lock-left", state.rightLeft + "px");
        state.inner.style.setProperty("--rs-lock-width", state.rightWidth + "px");
        state.right.style.setProperty("--rs-space", state.inner.getBoundingClientRect().height + "px");
        state.locked = true;
      }

      var targetScroll = Math.min(desired, state.maxScroll);
      state.wrapper.scrollTop = targetScroll;
    }

    function onScroll() {
      if (ticking) {
        return;
      }
      ticking = true;
      requestAnimationFrame(function () {
        apply();
        ticking = false;
      });
    }

    window.addEventListener("resize", collect);
    window.addEventListener("scroll", onScroll, { passive: true });
    window.addEventListener("load", collect);

    collect();
    setTimeout(collect, 600); // allow widgets/images to size
  })();
})(jQuery);
