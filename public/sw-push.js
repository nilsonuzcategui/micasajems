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

// Click en la notificación → abre la URL (con soporte de hash)
self.addEventListener('notificationclick', function (event) {
    console.log('[SW] Notification clicked');
    event.notification.close();

    const rawUrl = (event.notification.data && event.notification.data.url) || '/';
    // Si la URL es relativa, le agregamos el origin del SW
    const url = rawUrl.startsWith('http') ? rawUrl : (self.location.origin + rawUrl);

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(async function (windowClients) {
            // Buscar una ventana del mismo origen
            for (let i = 0; i < windowClients.length; i++) {
                const client = windowClients[i];
                if (client.url.includes(self.location.origin) && 'focus' in client) {
                    await client.focus();

                    // Extraer path + hash de la URL objetivo
                    const target = new URL(url);
                    const targetPath = target.pathname + target.search + target.hash;

                    // Comparar contra la URL actual del cliente (sin hash)
                    const current = new URL(client.url);
                    const currentPath = current.pathname + current.search;

                    if (currentPath === targetPath) {
                        // Misma ruta: solo cambió el hash → navegamos igual para que el browser haga scroll,
                        // y le mandamos postMessage por si quiere hacer scrollIntoView suave
                        if (target.hash) {
                            client.postMessage({ type: 'scroll-to', hash: target.hash });
                        }
                        return;
                    }

                    // Ruta distinta: navegar (esto también procesa el hash)
                    return client.navigate(url);
                }
            }

            // No hay ventana abierta → abrir una nueva
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});