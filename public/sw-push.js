// Service Worker para Web Push - JEMS
// Maneja notificaciones push recibidas y eventos de click

self.addEventListener('install', function (event) {
    console.log('[SW] Installing');
    self.skipWaiting();
});

self.addEventListener('activate', function (event) {
    console.log('[SW] Activating');
    event.waitUntil(self.clients.claim());
});

// Notificación push recibida
self.addEventListener('push', function (event) {
    console.log('[SW] Push recibido');

    let payload = {
        title: 'JEMS - Nueva actividad',
        body: 'Hay un nuevo evento publicado. Tocá para ver los detalles.',
        icon: '/logonegro.png',
        badge: '/favicon.ico',
        url: '/',
    };

    if (event.data) {
        try {
            const data = event.data.json();
            payload = Object.assign(payload, data);
        } catch (e) {
            payload.body = event.data.text();
        }
    }

    const options = {
        body: payload.body,
        icon: payload.icon,
        badge: payload.badge,
        data: { url: payload.url },
        requireInteraction: false,
        tag: 'jems-activity-' + Date.now(),
        renotify: true,
    };

    event.waitUntil(
        self.registration.showNotification(payload.title, options)
    );
});

// Click en la notificación → abre el sitio
self.addEventListener('notificationclick', function (event) {
    console.log('[SW] Notification clicked');
    event.notification.close();

    const url = (event.notification.data && event.notification.data.url) || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (windowClients) {
            // Si ya hay una ventana del sitio, enfocarla
            for (let i = 0; i < windowClients.length; i++) {
                const client = windowClients[i];
                if (client.url.includes(self.location.origin) && 'focus' in client) {
                    client.navigate(url);
                    return client.focus();
                }
            }
            // Si no hay ventana, abrir nueva
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});