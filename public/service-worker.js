// DarkOak Push Notification Service Worker

const CACHE_NAME = 'darkoak-push-v1';

// Install event
self.addEventListener('install', (event: any) => {
    console.log('[DarkOak SW] Installing...');
    self.skipWaiting();
});

// Activate event
self.addEventListener('activate', (event: any) => {
    console.log('[DarkOak SW] Activating...');
    event.waitUntil(self.clients.claim());
});

// Push event - Handle incoming push notifications
self.addEventListener('push', (event: any) => {
    console.log('[DarkOak SW] Push received:', event);

    let data: any = {};
    
    try {
        data = event.data?.json() || {};
    } catch (e) {
        data = {
            title: 'DarkOak Notification',
            body: event.data?.text() || 'You have a new notification',
        };
    }

    const title = data.title || 'DarkOak';
    const options: NotificationOptions = {
        body: data.body || '',
        icon: data.icon || '/favicon.ico',
        badge: data.badge || '/badge-72x72.png',
        tag: data.tag || 'notification',
        requireInteraction: data.requireInteraction ?? true,
        renotify: data.renotify ?? false,
        data: data.data || {},
        actions: data.actions || [],
        vibrate: [200, 100, 200],
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

// Notification click event
self.addEventListener('notificationclick', (event: any) => {
    console.log('[DarkOak SW] Notification clicked:', event);

    event.notification.close();

    const notificationData = event.notification.data || {};
    let url = '/account';

    // Determine target URL based on notification type
    if (notificationData.serverId) {
        url = `/server/${notificationData.serverId}`;
    } else if (notificationData.invoiceId) {
        url = `/account/billing/invoices/${notificationData.invoiceId}`;
    } else if (notificationData.organizationId) {
        url = `/account/organizations/${notificationData.organizationId}`;
    }

    // Handle action clicks
    if (event.action) {
        switch (event.action) {
            case 'view':
                url = notificationData.url || url;
                break;
            case 'dismiss':
                return;
            default:
                break;
        }
    }

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((windowClients: any[]) => {
                // Focus existing tab if open
                for (const client of windowClients) {
                    if (client.url.includes(url) && 'focus' in client) {
                        return client.focus();
                    }
                }
                // Open new window
                if (self.clients.openWindow) {
                    return self.clients.openWindow(url);
                }
            })
    );
});

// Sync event for background sync (if needed)
self.addEventListener('sync', (event: any) => {
    console.log('[DarkOak SW] Sync event:', event.tag);
});

// Message from main thread
self.addEventListener('message', (event: any) => {
    console.log('[DarkOak SW] Message from client:', event.data);
    
    if (event.data === 'skipWaiting') {
        self.skipWaiting();
    }
});

export {};