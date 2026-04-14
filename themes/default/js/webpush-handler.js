(function () {
  'use strict';

  const cfg = window.webPushConfig || {};
  const formUrl = cfg.requestUrl || '';
  const workerUrl = cfg.workerUrl || '';
  const workerScope = cfg.workerScope || '/webpush/';
  const vapidPublicKey = cfg.vapidPublic || '';
  const globalEnabled = !!cfg.enabled;
  let userEnabled = !!cfg.userEnabled;
  const csrfToken = cfg.csrfToken || window.csrfToken || '';

  const labels = cfg.labels || {};

  function label(key, fallback) {
    return labels[key] || fallback;
  }

  function base64UrlToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
      outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
  }

  function arrayBufferToBase64Url(buffer) {
    if (!buffer) {
      return '';
    }
    let binary = '';
    const bytes = new Uint8Array(buffer);
    const len = bytes.byteLength;
    for (let i = 0; i < len; i += 1) {
      binary += String.fromCharCode(bytes[i]);
    }
    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
  }

  function postData(payload) {
    if (!formUrl) {
      return Promise.reject(new Error('missing_request_url'));
    }
    const body = new URLSearchParams(payload || {});
    if (csrfToken) {
      body.set('csrf_token', csrfToken);
    }
    return fetch(formUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-CSRF-TOKEN': csrfToken
      },
      body: body.toString()
    }).then((response) => response.json());
  }

  function isSecurePushContext() {
    const host = window.location.hostname;
    if (window.location.protocol === 'https:') {
      return true;
    }
    return host === 'localhost' || host === '127.0.0.1' || host === '::1';
  }

  function pushSupported() {
    return (
      'serviceWorker' in navigator &&
      'PushManager' in window &&
      'Notification' in window
    );
  }

  function updateUiState() {
    const toggle = document.getElementById('web_push_enabled_toggle');
    const statusNode = document.getElementById('web_push_status_text');
    const msgNode = document.getElementById('web_push_runtime_msg');
    if (!toggle) {
      return;
    }

    toggle.checked = !!userEnabled;

    if (!pushSupported()) {
      toggle.disabled = true;
      if (statusNode) {
        statusNode.textContent = label('notSupported', 'Your browser does not support push notifications.');
      }
      if (msgNode) {
        msgNode.textContent = label('notSupported', 'Your browser does not support push notifications.');
      }
      return;
    }

    if (!isSecurePushContext()) {
      toggle.disabled = true;
      if (statusNode) {
        statusNode.textContent = label('httpsRequired', 'Push notifications require HTTPS.');
      }
      if (msgNode) {
        msgNode.textContent = label('httpsRequired', 'Push notifications require HTTPS.');
      }
      return;
    }

    if (!globalEnabled || !vapidPublicKey) {
      toggle.disabled = true;
      if (statusNode) {
        statusNode.textContent = label('disabled', 'Browser push notifications are disabled for this account.');
      }
      if (msgNode) {
        msgNode.textContent = label('disabled', 'Browser push notifications are disabled for this account.');
      }
      return;
    }

    toggle.disabled = false;
    if (Notification.permission === 'denied') {
      if (statusNode) {
        statusNode.textContent = label('denied', 'Notification permission is blocked in your browser.');
      }
      if (msgNode) {
        msgNode.textContent = label('denied', 'Notification permission is blocked in your browser.');
      }
      return;
    }

    if (statusNode) {
      statusNode.textContent = userEnabled
        ? label('enabled', 'Browser push notifications are enabled for this account.')
        : label('disabled', 'Browser push notifications are disabled for this account.');
    }
    if (msgNode) {
      msgNode.textContent = '';
    }
  }

  function registerWorker() {
    return navigator.serviceWorker.register(workerUrl, { scope: workerScope });
  }

  function syncSubscriptionWithServer(subscription) {
    if (!subscription) {
      return Promise.resolve(false);
    }

    const payload = {
      f: 'webpush_subscribe',
      endpoint: subscription.endpoint || '',
      p256dh: arrayBufferToBase64Url(subscription.getKey('p256dh')),
      auth: arrayBufferToBase64Url(subscription.getKey('auth')),
      content_encoding: 'aes128gcm'
    };

    return postData(payload).then((data) => {
      return data && data.status === 'ok';
    });
  }

  function ensureSubscription() {
    if (!globalEnabled || !vapidPublicKey) {
      return Promise.resolve(false);
    }

    return registerWorker()
      .then((registration) => {
        return registration.pushManager.getSubscription().then((existingSub) => {
          if (existingSub) {
            return syncSubscriptionWithServer(existingSub);
          }
          return registration.pushManager
            .subscribe({
              userVisibleOnly: true,
              applicationServerKey: base64UrlToUint8Array(vapidPublicKey)
            })
            .then((newSubscription) => syncSubscriptionWithServer(newSubscription));
        });
      });
  }

  function removeSubscription() {
    return registerWorker()
      .then((registration) => registration.pushManager.getSubscription())
      .then((subscription) => {
        if (!subscription) {
          return postData({ f: 'webpush_unsubscribe' }).then(() => true);
        }

        const endpoint = subscription.endpoint || '';
        return subscription.unsubscribe().catch(() => false).then(() => {
          return postData({
            f: 'webpush_unsubscribe',
            endpoint: endpoint
          }).then(() => true);
        });
      });
  }

  function handleToggleChange(toggle) {
    if (!toggle) {
      return;
    }

    const requestedEnabled = !!toggle.checked;
    toggle.disabled = true;

    const rollback = () => {
      toggle.checked = !requestedEnabled;
      toggle.disabled = false;
      updateUiState();
    };

    if (!requestedEnabled) {
      removeSubscription()
        .then(() => postData({ f: 'webpush_toggle', enabled: '0' }))
        .then((data) => {
          if (!data || data.status !== 'ok') {
            throw new Error('toggle_off_failed');
          }
          userEnabled = false;
          toggle.disabled = false;
          updateUiState();
        })
        .catch(() => rollback());
      return;
    }

    if (Notification.permission === 'denied') {
      rollback();
      return;
    }

    const permissionPromise = Notification.permission === 'granted'
      ? Promise.resolve('granted')
      : Notification.requestPermission();

    permissionPromise
      .then((permission) => {
        if (permission !== 'granted') {
          throw new Error('permission_denied');
        }
        return ensureSubscription();
      })
      .then(() => postData({ f: 'webpush_toggle', enabled: '1' }))
      .then((data) => {
        if (!data || data.status !== 'ok') {
          throw new Error('toggle_on_failed');
        }
        userEnabled = true;
        toggle.disabled = false;
        updateUiState();
      })
      .catch(() => rollback());
  }

  function bindPreferenceToggle() {
    const toggle = document.getElementById('web_push_enabled_toggle');
    if (!toggle) {
      return;
    }

    toggle.addEventListener('change', function () {
      handleToggleChange(toggle);
    });
  }

  function bootstrapAutoSync() {
    if (!pushSupported() || !isSecurePushContext() || !globalEnabled || !userEnabled || !vapidPublicKey) {
      return;
    }
    if (Notification.permission === 'granted') {
      ensureSubscription().catch(function () {});
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    updateUiState();
    bindPreferenceToggle();
    bootstrapAutoSync();
  });
})();
