var SW_VERSION = 3;

self.addEventListener('install', function (event) {
    self.skipWaiting(); // take over immediately, don't wait for tabs to close
});

self.addEventListener('activate', function (event) {
    event.waitUntil(clients.claim()); // take control of all open tabs
});

self.addEventListener('push', function (event) {
    let data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch (e) {
        data = { title: 'New Job Alert', body: event.data ? event.data.text() : '' };
    }

    const title   = data.title || 'New Job Alert';
    const options = {
        body:  data.body  || 'A new job has been posted!',
        icon:  data.icon  || '/push-icon.png',
        badge: data.badge || '/push-icon.png',
        data:  { url: data.url || '/' },
        requireInteraction: false,
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    var targetUrl = (event.notification.data && event.notification.data.url)
        ? event.notification.data.url
        : '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (windowClients) {
            // If a tab is already at the exact target URL, just focus it
            for (var i = 0; i < windowClients.length; i++) {
                if (windowClients[i].url === targetUrl) {
                    return windowClients[i].focus();
                }
            }
            // No tab at that URL — open a new one
            return clients.openWindow(targetUrl);
        })
    );
});
