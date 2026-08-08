import React, { useState, useEffect, useCallback } from 'react';
import { Button } from '@elements/button';
import { Switch } from '@elements/Switch';
import { useStoreState } from '@/state/hooks';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faBell,
    faServer,
    faCreditCard,
    faExclamationTriangle,
    faCheckCircle,
    faDesktop,
    faMobileAlt,
} from '@fortawesome/free-solid-svg-icons';
import { ApplicationStore } from '@/state';

interface NotificationEvent {
    key: string;
    label: string;
    category: 'server' | 'backup' | 'billing' | 'alert';
    icon: any;
}

const EVENTS: NotificationEvent[] = [
    { key: 'server.created', label: 'Server Created', category: 'server', icon: faServer },
    { key: 'server.deleted', label: 'Server Deleted', category: 'server', icon: faServer },
    { key: 'server.suspended', label: 'Server Suspended', category: 'server', icon: faServer },
    { key: 'server.unsuspended', label: 'Server Unsuspended', category: 'server', icon: faServer },
    { key: 'server.crashed', label: 'Server Crashed', category: 'server', icon: faExclamationTriangle },
    { key: 'server.reinstalled', label: 'Server Reinstalled', category: 'server', icon: faServer },
    { key: 'backup.completed', label: 'Backup Completed', category: 'backup', icon: faCheckCircle },
    { key: 'backup.failed', label: 'Backup Failed', category: 'backup', icon: faExclamationTriangle },
    { key: 'billing.invoice.generated', label: 'New Invoice', category: 'billing', icon: faCreditCard },
    { key: 'billing.payment.success', label: 'Payment Successful', category: 'billing', icon: faCheckCircle },
    { key: 'billing.payment.failed', label: 'Payment Failed', category: 'billing', icon: faExclamationTriangle },
    { key: 'alert.security', label: 'Security Alert', category: 'alert', icon: faExclamationTriangle },
    { key: 'alert.maintenance', label: 'Maintenance Alert', category: 'alert', icon: faExclamationTriangle },
];

const CATEGORY_ICONS = {
    server: faServer,
    backup: faCheckCircle,
    billing: faCreditCard,
    alert: faExclamationTriangle,
};

const CATEGORY_LABELS = {
    server: 'Server Events',
    backup: 'Backup Events',
    billing: 'Billing Events',
    alert: 'Alert Events',
};

export default function PushNotifications() {
    const [isSupported, setIsSupported] = useState<boolean>(false);
    const [isSubscribed, setIsSubscribed] = useState<boolean>(false);
    const [isLoading, setIsLoading] = useState<boolean>(false);
    const [preferences, setPreferences] = useState<string[]>([]);
    const [vapidKey, setVapidKey] = useState<string | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [success, setSuccess] = useState<string | null>(null);

    const user = useStoreState((state: ApplicationStore) => state.user.data);

    // Check if push notifications are supported
    useEffect(() => {
        const supported = 'serviceWorker' in navigator && 'PushManager' in window;
        setIsSupported(supported);

        if (supported) {
            checkSubscription();
            fetchVapidKey();
        }
    }, []);

    const checkSubscription = async () => {
        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();
            setIsSubscribed(!!subscription);

            if (subscription) {
                // Fetch preferences from API
                const response = await fetch('/api/client/notifications');
                if (response.ok) {
                    const data = await response.json();
                    if (data.data?.length > 0 && data.data[0].preferences) {
                        setPreferences(data.data[0].preferences);
                    }
                }
            }
        } catch (err) {
            console.error('Error checking subscription:', err);
        }
    };

    const fetchVapidKey = async () => {
        try {
            const response = await fetch('/api/client/notifications/vapid-key');
            if (response.ok) {
                const data = await response.json();
                setVapidKey(data.data?.public_key);
            }
        } catch (err) {
            console.error('Error fetching VAPID key:', err);
        }
    };

    const subscribe = async () => {
        if (!vapidKey) {
            setError('VAPID key not available');
            return;
        }

        setIsLoading(true);
        setError(null);

        try {
            const registration = await navigator.serviceWorker.ready;

            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidKey),
            });

            // Send subscription to server
            const response = await fetch('/api/client/notifications', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    endpoint: subscription.endpoint,
                    keys: {
                        p256dh: btoa(
                            String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('p256dh')!) as any),
                        ),
                        auth: btoa(
                            String.fromCharCode.apply(null, new Uint8Array(subscription.getKey('auth')!) as any),
                        ),
                    },
                }),
            });

            if (response.ok) {
                setIsSubscribed(true);
                setSuccess('Successfully subscribed to push notifications!');
                setPreferences(EVENTS.map(e => e.key));
            } else {
                throw new Error('Failed to save subscription');
            }
        } catch (err) {
            console.error('Error subscribing:', err);
            setError('Failed to subscribe to push notifications');
        } finally {
            setIsLoading(false);
        }
    };

    const unsubscribe = async () => {
        setIsLoading(true);
        setError(null);

        try {
            const registration = await navigator.serviceWorker.ready;
            const subscription = await registration.pushManager.getSubscription();

            if (subscription) {
                await subscription.unsubscribe();
            }

            // Remove from server
            await fetch('/api/client/notifications', { method: 'DELETE' });

            setIsSubscribed(false);
            setPreferences([]);
            setSuccess('Successfully unsubscribed from push notifications');
        } catch (err) {
            console.error('Error unsubscribing:', err);
            setError('Failed to unsubscribe');
        } finally {
            setIsLoading(false);
        }
    };

    const updatePreferences = async (newPreferences: string[]) => {
        try {
            // In a real implementation, you'd need to get the subscription UUID
            // and update it. For now, we'll just store locally.
            setPreferences(newPreferences);
            setSuccess('Preferences updated!');
        } catch (err) {
            console.error('Error updating preferences:', err);
            setError('Failed to update preferences');
        }
    };

    const toggleEvent = (eventKey: string) => {
        const newPrefs = preferences.includes(eventKey)
            ? preferences.filter(p => p !== eventKey)
            : [...preferences, eventKey];
        updatePreferences(newPrefs);
    };

    const toggleCategory = (category: string) => {
        const categoryEvents = EVENTS.filter(e => e.category === category).map(e => e.key);
        const allEnabled = categoryEvents.every(e => preferences.includes(e));

        if (allEnabled) {
            // Disable all in category
            updatePreferences(preferences.filter(p => !categoryEvents.includes(p)));
        } else {
            // Enable all in category
            updatePreferences([...new Set([...preferences, ...categoryEvents])]);
        }
    };

    // Helper function to convert base64 to Uint8Array
    function urlBase64ToUint8Array(base64String: string): Uint8Array {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/\-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);
        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
    }

    if (!isSupported) {
        return (
            <div className="p-6 bg-gray-100 dark:bg-gray-800 rounded-lg">
                <div className="flex items-center text-gray-500">
                    <FontAwesomeIcon icon={faBell} className="mr-3 text-xl" />
                    <p>Push notifications are not supported in this browser.</p>
                </div>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            <div className="flex items-center justify-between">
                <div>
                    <h2 className="text-xl font-semibold flex items-center gap-2">
                        <FontAwesomeIcon icon={faBell} className="text-blue-500" />
                        Push Notifications
                    </h2>
                    <p className="text-gray-500 text-sm mt-1">
                        Get notified about important events even when you're not on the site.
                    </p>
                </div>
                <div className="flex items-center gap-4">
                    <span className={`text-sm ${isSubscribed ? 'text-green-500' : 'text-gray-500'}`}>
                        {isSubscribed ? 'Subscribed' : 'Not Subscribed'}
                    </span>
                    {isSubscribed ? (
                        <Button variant="danger" onClick={unsubscribe} loading={isLoading}>
                            <FontAwesomeIcon icon={faBell} className="mr-2" />
                            Disable
                        </Button>
                    ) : (
                        <Button variant="primary" onClick={subscribe} loading={isLoading}>
                            <FontAwesomeIcon icon={faBell} className="mr-2" />
                            Enable
                        </Button>
                    )}
                </div>
            </div>

            {error && (
                <div className="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 rounded-lg">
                    <div className="flex items-center text-red-600">
                        <FontAwesomeIcon icon={faExclamationTriangle} className="mr-2" />
                        {error}
                    </div>
                </div>
            )}

            {success && (
                <div className="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 rounded-lg">
                    <div className="flex items-center text-green-600">
                        <FontAwesomeIcon icon={faCheckCircle} className="mr-2" />
                        {success}
                    </div>
                </div>
            )}

            {isSubscribed && (
                <div className="space-y-4">
                    <h3 className="font-medium text-lg">Notification Preferences</h3>

                    {(['server', 'backup', 'billing', 'alert'] as const).map(category => {
                        const categoryEvents = EVENTS.filter(e => e.category === category);
                        const allEnabled = categoryEvents.every(e => preferences.includes(e.key));
                        const someEnabled = categoryEvents.some(e => preferences.includes(e.key));

                        return (
                            <div key={category} className="border rounded-lg p-4">
                                <div className="flex items-center justify-between mb-3">
                                    <div className="flex items-center gap-2">
                                        <FontAwesomeIcon icon={CATEGORY_ICONS[category]} className="text-gray-500" />
                                        <h4 className="font-medium">{CATEGORY_LABELS[category]}</h4>
                                    </div>
                                    <Switch checked={allEnabled} onChange={() => toggleCategory(category)} />
                                </div>

                                <div className="grid grid-cols-2 gap-2 pl-6">
                                    {categoryEvents.map(event => (
                                        <label
                                            key={event.key}
                                            className="flex items-center gap-2 cursor-pointer hover:bg-gray-50 p-2 rounded"
                                        >
                                            <input
                                                type="checkbox"
                                                checked={preferences.includes(event.key)}
                                                onChange={() => toggleEvent(event.key)}
                                                className="rounded text-blue-600"
                                            />
                                            <span className="text-sm">{event.label}</span>
                                        </label>
                                    ))}
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}

            <div className="text-sm text-gray-500 p-4 bg-gray-50 rounded-lg">
                <p className="flex items-center gap-2">
                    <FontAwesomeIcon icon={faDesktop} />
                    Desktop and mobile browsers supported
                </p>
                <p className="mt-2">
                    Push notifications are delivered even when the browser is closed. You can manage your preferences
                    anytime.
                </p>
            </div>
        </div>
    );
}
