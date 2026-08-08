/**
 * Push Notification Service Worker
 *
 * Handles background push notifications, notification clicks, and subscription management.
 */

// Version for cache busting
const SW_VERSION = '1.0.0';

// Cache name
const CACHE_NAME = `darkoaktyl-push-v${SW_VERSION}`;

// Installation event
self.addEventListener('install', (event: ExtendableEvent) => {
    // Skip waiting to activate immediately
    (self as any).skipWaiting();
});

// Activation event
self.addEventListener('activate', (event: ExtendableEvent) => {
    event.waitUntil(
        // Claim clients immediately
        (self as any).clients.claim(),
    );
});

// Push event - handle incoming push notifications
self.addEventListener('push', (event: PushEvent) => {
    if (!event.data) {
        console.warn('[SW] Push event has no data');
        return;
    }

    let data: any = {};
    try {
        data = event.data.json();
    } catch (e) {
        // If JSON parsing fails, use text
        data = {
            title: 'DarkOaktyl Notification',
            body: event.data.text(),
        };
    }

    const title = data.title || 'DarkOaktyl';
    const options: NotificationOptions = {
        body: data.body || '',
        icon: data.icon || '/favicon.ico',
        badge: data.badge || '/favicon.ico',
        tag: data.tag || 'darkoaktyl-push',
        data: data.data || {},
        requireInteraction: data.requireInteraction || false,
        actions: data.actions || [],
        timestamp: Date.now(),
        // Vibration pattern for supported devices
        vibrate: data.vibrate || [200, 100, 200],
    };

    event.waitUntil((self as any).registration.showNotification(title, options));
});

// Notification click event
self.addEventListener('notificationclick', (event: NotificationEvent) => {
    event.notification.close();

    const notificationData = event.notification.data;
    const action = event.action;

    let targetUrl: string;

    // Handle different actions
    if (action) {
        switch (action) {
            case 'view':
                targetUrl = notificationData.url || '/dashboard';
                break;
            case 'dismiss':
                // Just close the notification (already done above)
                return;
            case 'open':
                targetUrl = notificationData.serverUrl || notificationData.url || '/';
                break;
            default:
                targetUrl = notificationData.url || '/';
        }
    } else {
        // Default: open the main app
        targetUrl = notificationData.url || '/';

        // Specific routes based on event type
        if (notificationData.event) {
            const eventType = notificationData.event;
            if (eventType.startsWith('server.')) {
                targetUrl = notificationData.serverUrl || '/dashboard';
            } else if (eventType.startsWith('billing.')) {
                targetUrl = '/account/billing';
            } else if (eventType.startsWith('backup.')) {
                targetUrl = notificationData.backupUrl || '/dashboard';
            }
        }
    }

    event.waitUntil(
        (self as any).clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList: any[]) => {
            // Check if there's already an open window
            for (const client of clientList) {
                if (client.url && client.url.includes(self.location.origin) && 'focus' in client) {
                    // Focus existing window and navigate to target
                    client.focus();
                    if (targetUrl) {
                        client.navigate(targetUrl);
                    }
                    return;
                }
            }

            // If no window is open, open a new one
            if ((self as any).clients.openWindow) {
                return (self as any).clients.openWindow(targetUrl);
            }
        }),
    );
});

// Notification close event (notification dismissed without clicking)
self.addEventListener('notificationclose', (event: NotificationEvent) => {
    // Track notification dismissals if needed
    const notificationData = event.notification.data;
    if (notificationData && notificationData.eventId) {
        // Could send analytics event here
    }
});

// Push subscription change event
self.addEventListener('pushsubscriptionchange', (event: any) => {
    const subscription = event.newSubscription;
    const oldSubscription = event.oldSubscription;

    if (!subscription) {
        console.warn('[SW] Push subscription removed');
        // Notify the main application that subscription was removed
        // The main app should clean up the old subscription on the server
        return;
    }

    // Send the new subscription to the server
    event.waitUntil(
        fetch('/api/client/account/notifications/push/subscribe', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'include',
            body: JSON.stringify({
                endpoint: subscription.endpoint,
                keys: {
                    p256dh: subscription.toJSON().keys.p256dh,
                    auth: subscription.toJSON().keys.auth,
                },
            }),
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to update subscription');
                }
            })
            .catch(error => {
                console.error('[SW] Failed to update subscription:', error);
            }),
    );
});

// Message event - handle messages from the main application
self.addEventListener('message', (event: MessageEvent) => {
    const data = event.data;

    if (!data || typeof data !== 'object') {
        return;
    }

    switch (data.type) {
        case 'SKIP_WAITING':
            // Skip waiting for new service worker
            (self as any).skipWaiting();
            break;

        case 'GET_VERSION':
            // Respond with service worker version
            if (event.source) {
                (event.source as any).postMessage({
                    type: 'VERSION',
                    version: SW_VERSION,
                });
            }
            break;

        case 'PING':
            // Health check response
            if (event.source) {
                (event.source as any).postMessage({
                    type: 'PONG',
                    timestamp: Date.now(),
                });
            }
            break;

        default:
            break;
    }
});

// Sync event for background sync (if supported)
self.addEventListener('sync', (event: any) => {
    if (event.tag === 'push-subscription-sync') {
        event.waitUntil(
            // Re-subscribe or verify subscription
            (self as any).registration.pushManager
                .getSubscription()
                .then((subscription: PushSubscription | null) => {
                    if (!subscription) {
                        return;
                    }

                    // Verify subscription with server
                    return fetch('/api/client/account/notifications/push/config', {
                        method: 'GET',
                        credentials: 'include',
                    });
                })
                .catch((error: any) => {
                    console.error('[SW] Sync failed:', error);
                }),
        );
    }
});

// Periodic background sync (if supported)
if ('periodicSync' in (self as any).registration) {
    (self as any).registration.periodicSync
        .register({
            tag: 'push-health-check',
            minInterval: 24 * 60 * 60 * 1000, // 24 hours
        })
        .catch((error: any) => {
            // Periodic sync not supported in all browsers
        });
}

// Export empty object for TypeScript module
export {};
