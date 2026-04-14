(function () {
  'use strict';

  function getUa() {
    return (navigator.userAgent || '').toLowerCase();
  }

  function isIos() {
    const ua = getUa();
    const isAppleMobile = /iphone|ipad|ipod/.test(ua);
    const isIpadOs = navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1;
    return isAppleMobile || isIpadOs;
  }

  function isAndroid() {
    return /android/.test(getUa());
  }

  function isMobileClient() {
    return isIos() || isAndroid();
  }

  function isIosSafari() {
    if (!isIos()) {
      return false;
    }

    const ua = getUa();
    return /safari/.test(ua) && !/crios|fxios|edgios|opios|mercury|gsa/.test(ua);
  }

  function isStandalone() {
    const standaloneMode = window.matchMedia && window.matchMedia('(display-mode: standalone)').matches;
    const iosStandalone = window.navigator.standalone === true;
    return standaloneMode || iosStandalone;
  }

  function getTexts() {
    const fallback = {
      installButton: 'Install App',
      popupTitle: 'Install App',
      popupDescDefault: 'Add this app to your home screen for faster access and a better full-screen experience.',
      popupLater: 'Maybe later',
      iosHelp: 'Open this site in Safari, tap Share, then tap Add to Home Screen.',
      iosSafariOnly: 'On iPhone and iPad, app installation is available only in Safari.',
      androidUnavailable: 'Install popup is not available yet. Please use browser menu > Add to Home screen.'
    };

    if (!window.pwaInstallTexts || typeof window.pwaInstallTexts !== 'object') {
      return fallback;
    }

    return {
      installButton: window.pwaInstallTexts.installButton || fallback.installButton,
      popupTitle: window.pwaInstallTexts.popupTitle || fallback.popupTitle,
      popupDescDefault: window.pwaInstallTexts.popupDescDefault || fallback.popupDescDefault,
      popupLater: window.pwaInstallTexts.popupLater || fallback.popupLater,
      iosHelp: window.pwaInstallTexts.iosHelp || fallback.iosHelp,
      iosSafariOnly: window.pwaInstallTexts.iosSafariOnly || fallback.iosSafariOnly,
      androidUnavailable: window.pwaInstallTexts.androidUnavailable || fallback.androidUnavailable
    };
  }

  function getPopupElements() {
    return {
      wrapper: document.getElementById('pwaInstallPopup'),
      overlay: document.getElementById('pwaInstallPopupOverlay'),
      close: document.getElementById('pwaInstallPopupClose'),
      desc: document.getElementById('pwaInstallPopupDesc'),
      title: document.getElementById('pwaInstallPopupTitle'),
      later: document.getElementById('pwaInstallPopupLater'),
      action: document.getElementById('pwaInstallPopupAction')
    };
  }

  function setPopupVisible(visible) {
    const popup = getPopupElements().wrapper;
    if (!popup) {
      return;
    }

    popup.classList.toggle('is-visible', visible);
    popup.setAttribute('aria-hidden', visible ? 'false' : 'true');
  }

  function getPopupDescription() {
    const texts = getTexts();
    if (isIos()) {
      return isIosSafari() ? texts.iosHelp : texts.iosSafariOnly;
    }

    return texts.popupDescDefault;
  }

  function openInstallPopup(description) {
    const popup = getPopupElements();
    const texts = getTexts();

    if (!popup.wrapper) {
      return;
    }

    if (popup.title) {
      popup.title.textContent = texts.popupTitle;
    }

    if (popup.desc) {
      popup.desc.textContent = description || getPopupDescription();
    }

    if (popup.later) {
      popup.later.textContent = texts.popupLater;
    }

    if (popup.action) {
      popup.action.textContent = texts.installButton;
    }

    setPopupVisible(true);
  }

  function closeInstallPopup() {
    setPopupVisible(false);
  }

  async function attemptAndroidInstall() {
    const texts = getTexts();

    if (typeof window.promptPWAInstall !== 'function') {
      openInstallPopup(texts.androidUnavailable);
      return false;
    }

    const accepted = await window.promptPWAInstall();
    if (!accepted) {
      openInstallPopup(texts.androidUnavailable);
      return false;
    }

    return true;
  }

  async function onPopupActionClick(event) {
    if (event) {
      event.preventDefault();
    }

    if (isIos()) {
      if (isIosSafari() && typeof navigator.share === 'function') {
        try {
          await navigator.share({
            title: document.title || 'Install App',
            url: window.location.href
          });
        } catch (error) {
          // Share can be cancelled by user; keep install guidance visible.
        }
      }

      openInstallPopup(getPopupDescription());
      return;
    }

    const accepted = await attemptAndroidInstall();
    if (accepted) {
      closeInstallPopup();
    }
  }

  function bindPopupControls() {
    const popup = getPopupElements();
    if (!popup.wrapper || popup.wrapper.getAttribute('data-pwa-popup-bound') === '1') {
      return;
    }

    popup.wrapper.setAttribute('data-pwa-popup-bound', '1');

    if (popup.overlay) {
      popup.overlay.addEventListener('click', closeInstallPopup);
    }

    if (popup.close) {
      popup.close.addEventListener('click', closeInstallPopup);
    }

    if (popup.later) {
      popup.later.addEventListener('click', closeInstallPopup);
    }

    if (popup.action) {
      popup.action.addEventListener('click', onPopupActionClick);
    }
  }

  function maybeAutoShowPopup() {
    if (!isMobileClient() || isStandalone()) {
      return;
    }

    window.setTimeout(function () {
      openInstallPopup();
    }, 5000);
  }

  function initPwaInstallHooks() {
    window.deferredPWAInstall = null;

    window.addEventListener('beforeinstallprompt', function (event) {
      event.preventDefault();
      window.deferredPWAInstall = event;
      window.dispatchEvent(new Event('pwa-install-available'));
    });

    window.promptPWAInstall = async function () {
      try {
        if (!window.deferredPWAInstall) {
          return false;
        }

        const deferredEvent = window.deferredPWAInstall;
        window.deferredPWAInstall = null;

        const result = await deferredEvent.prompt();
        const outcome = result && result.outcome ? result.outcome : 'dismissed';
        window.dispatchEvent(new CustomEvent('pwa-install-outcome', { detail: outcome }));

        return outcome === 'accepted';
      } catch (error) {
        return false;
      }
    };

    bindPopupControls();
    maybeAutoShowPopup();
    window.addEventListener('pwa-install-outcome', function (event) {
      if (event && event.detail === 'accepted') {
        closeInstallPopup();
        return;
      }
    });
    window.addEventListener('appinstalled', function () {
      closeInstallPopup();
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPwaInstallHooks, { once: true });
  } else {
    initPwaInstallHooks();
  }
})();
