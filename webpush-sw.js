self.addEventListener('push', function (event) {
  var payload = {};
  try {
    payload = event.data ? event.data.json() : {};
  } catch (e) {
    payload = {};
  }

  var title = payload.title || 'New notification';
  var options = {
    body: payload.body || '',
    icon: payload.icon || '',
    badge: payload.badge || '',
    tag: payload.tag || 'dizzy_notification',
    data: {
      url: payload.url || '/notifications'
    }
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
  event.notification.close();
  var destination = '/';
  if (event.notification && event.notification.data && event.notification.data.url) {
    destination = event.notification.data.url;
  }

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (windowClients) {
      for (var i = 0; i < windowClients.length; i += 1) {
        var client = windowClients[i];
        if (client.url === destination && 'focus' in client) {
          return client.focus();
        }
      }
      if (clients.openWindow) {
        return clients.openWindow(destination);
      }
      return null;
    })
  );
});
