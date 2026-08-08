import { useState, useEffect, useCallback } from 'react';
import { Actions, useStoreActions, useStoreState, State } from 'easy-peasy';
import { ApplicationStore } from '@/state';
import { Button } from '@/elements/button';
import ContentBox from '@/elements/ContentBox';
import SpinnerOverlay from '@/elements/SpinnerOverlay';
import tw from 'twin.macro';
import http from '@/api/http';
import { httpErrorToHuman } from '@/api/http';
import Switch from '@/elements/Switch';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faBell, faServer, faCreditCard, faShieldAlt, faRobot } from '@fortawesome/free-solid-svg-icons';

interface EventConfig {
    events: string[];
    labels: Record<string, string>;
    categories: Record<string, string[]>;
    defaults: Record<string, boolean>;
}

interface Subscription {
    id: string;
    endpoint: string;
    preferences: Record<string, boolean>;
    last_used_at: string | null;
    created_at: string;
}

const categoryIcons: Record<string, any> = {
    server: faServer,
    billing: faCreditCard,
    security: faShieldAlt,
    automation: faRobot,
};

const categoryLabels: Record<string, string> = {
    server: 'Server Notifications',
    billing: 'Billing & Payments',
    security: 'Security Alerts',
    automation: 'Automation Events',
};

export default () => {
    const { addFlash, clearFlashes } = useStoreActions((actions: Actions<ApplicationStore>) => actions.flashes);
    const user = useStoreState((state: State<ApplicationStore>) => state.user.data);

    const [isLoading, setIsLoading] = useState(true);
    const [isSubscribing, setIsSubscribing] = useState(false);
    const [eventConfig, setEventConfig] = useState<EventConfig | null>(null);
    const [subscriptions, setSubscriptions] = useState<Subscription[]>([]);
    const [vapidPublicKey, setVapidPublicKey] = useState<string | null>(null);
    const [preferences, setPreferences] = useState<Record<string, boolean>>({});

    // Load initial data
    useEffect(() => {
        loadData();
    }, []);

    const loadData = async () => {
        try {
            setIsLoading(true);
            clearFlashes('notifications');

            const [eventsRes, subsRes, vapidRes] = await Promise.all([
                http.get('/api/client/account/notifications/events'),
                http.get('/api/client/account/notifications'),
                http.get('/api/client/account/notifications/vapid-public-key'),
            ]);

            setEventConfig(eventsRes.data);
            setSubscriptions(subsRes.data.data || []);
            setVapidPublicKey(vapidRes.data.public_key);

            // Initialize preferences from first subscription or defaults
            const subs = subsRes.data.data || [];
            if (subs.length > 0) {
                setPreferences(subs[0].preferences || eventsRes.data.defaults);
            } else {
                setPreferences(eventsRes.data.defaults);
            }
        } catch (error) {
            addFlash({
                key: 'notifications',
                type: 'error',
                title: 'Error',
                message: httpErrorToHuman(error),
            });
        } finally {
            setIsLoading(false);
        }
    };

    // Check if push is supported
    const isPushSupported = typeof window !== 'undefined' && 'PushManager' in window && 'serviceWorker' in navigator;

    // Subscribe to push notifications
    const subscribe = async () => {
        if (!isPushSupported || !vapidPublicKey) {
            addFlash({
                key: 'notifications',
                type: 'error',
                title: 'Not Supported',
                message: 'Push notifications are not supported in your browser.',
            });
            return;
        }

        try {
            setIsSubscribing(true);
            clearFlashes('notifications');

            // Request permission
            const permission = await Notification.requestPermission();
            if (permission !== 'granted') {
                throw new Error('Notification permission denied');
            }

            // Register service worker
            const registration = await navigator.serviceWorker.register('/service-worker.js');
            await navigator.serviceWorker.ready;

            // Subscribe
            const subscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
            });

            // Send to backend
            const subData = subscription.toJSON();
            await http.post('/api/client/account/notifications', {
                endpoint: subData.endpoint,
                keys: subData.keys,
                preferences: preferences,
            });

            addFlash({
                key: 'notifications',
                type: 'success',
                title: 'Success',
                message: 'Push notifications enabled successfully!',
            });

            // Reload data
            await loadData();
        } catch (error: any) {
            addFlash({
                key: 'notifications',
                type: 'error',
                title: 'Error',
                message: error.message || 'Failed to enable notifications',
            });
        } finally {
            setIsSubscribing(false);
        }
    };

    // Unsubscribe from push notifications
    const unsubscribe = async (subscriptionId: string) => {
        try {
            setIsLoading(true);
            clearFlashes('notifications');

            await http.delete(`/api/client/account/notifications/${subscriptionId}`);

            addFlash({
                key: 'notifications',
                type: 'success',
                title: 'Success',
                message: 'Push notifications disabled.',
            });

            await loadData();
        } catch (error) {
            addFlash({
                key: 'notifications',
                type: 'error',
                title: 'Error',
                message: httpErrorToHuman(error),
            });
        } finally {
            setIsLoading(false);
        }
    };

    // Update preferences
    const updatePreferences = async () => {
        if (subscriptions.length === 0) return;

        try {
            setIsLoading(true);
            clearFlashes('notifications');

            await http.put(`/api/client/account/notifications/${subscriptions[0].id}`, {
                preferences: preferences,
            });

            addFlash({
                key: 'notifications',
                type: 'success',
                title: 'Success',
                message: 'Notification preferences updated.',
            });

            await loadData();
        } catch (error) {
            addFlash({
                key: 'notifications',
                type: 'error',
                title: 'Error',
                message: httpErrorToHuman(error),
            });
        } finally {
            setIsLoading(false);
        }
    };

    // Send test notification
    const sendTest = async () => {
        if (subscriptions.length === 0) return;

        try {
            setIsLoading(true);
            clearFlashes('notifications');

            await http.post('/api/client/account/notifications/test', {
                uuid: subscriptions[0].id,
            });

            addFlash({
                key: 'notifications',
                type: 'success',
                title: 'Test Sent',
                message: 'Check your device for the test notification!',
            });
        } catch (error) {
            addFlash({
                key: 'notifications',
                type: 'error',
                title: 'Error',
                message: httpErrorToHuman(error),
            });
        } finally {
            setIsLoading(false);
        }
    };

    // Toggle preference
    const togglePreference = (event: string) => {
        setPreferences(prev => ({
            ...prev,
            [event]: !prev[event],
        }));
    };

    // Toggle entire category
    const toggleCategory = (categoryEvents: string[], enabled: boolean) => {
        const updates: Record<string, boolean> = {};
        categoryEvents.forEach(event => {
            updates[event] = enabled;
        });
        setPreferences(prev => ({ ...prev, ...updates }));
    };

    // URL-safe base64 to Uint8Array
    const urlBase64ToUint8Array = (base64String: string): Uint8Array => {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        return Uint8Array.from([...rawData].map(char => char.charCodeAt(0)));
    };

    const hasSubscription = subscriptions.length > 0;

    return (
        <ContentBox title="Push Notifications" showFlashes="notifications">
            <div css={tw`relative`}>
                <SpinnerOverlay visible={isLoading} />

                {!isPushSupported ? (
                    <div css={tw`text-center py-8`}>
                        <FontAwesomeIcon icon={faBell} css={tw`text-4xl text-gray-400 mb-4`} />
                        <p css={tw`text-gray-500`}>Push notifications are not supported in your browser.</p>
                        <p css={tw`text-sm text-gray-400 mt-2`}>
                            Please use a modern browser like Chrome, Firefox, or Safari.
                        </p>
                    </div>
                ) : !hasSubscription ? (
                    <div css={tw`text-center py-8`}>
                        <FontAwesomeIcon icon={faBell} css={tw`text-4xl text-gray-400 mb-4`} />
                        <p css={tw`text-gray-500 mb-4`}>
                            Get notified about important events on your servers, billing, and more.
                        </p>
                        <Button onClick={subscribe} disabled={isSubscribing || !vapidPublicKey}>
                            {isSubscribing ? 'Enabling...' : 'Enable Push Notifications'}
                        </Button>
                    </div>
                ) : (
                    <div css={tw`space-y-6`}>
                        {/* Status */}
                        <div
                            css={tw`flex items-center justify-between p-4 bg-green-500/10 rounded border border-green-500/20`}
                        >
                            <div>
                                <p css={tw`font-medium text-green-400`}>
                                    <FontAwesomeIcon icon={faBell} css={tw`mr-2`} />
                                    Push Notifications Enabled
                                </p>
                                <p css={tw`text-sm text-gray-400 mt-1`}>
                                    {subscriptions.length} device{subscriptions.length !== 1 ? 's' : ''} subscribed
                                </p>
                            </div>
                            <div css={tw`flex gap-2`}>
                                <Button onClick={sendTest} size="small" variant="secondary">
                                    Send Test
                                </Button>
                                <Button onClick={() => unsubscribe(subscriptions[0].id)} size="small" variant="danger">
                                    Disable
                                </Button>
                            </div>
                        </div>

                        {/* Event Preferences */}
                        {eventConfig && (
                            <div css={tw`space-y-4`}>
                                <h3 css={tw`text-lg font-medium`}>Notification Preferences</h3>

                                {Object.entries(eventConfig.categories).map(([category, events]) => (
                                    <div key={category} css={tw`border rounded p-4`}>
                                        <div css={tw`flex items-center justify-between mb-3`}>
                                            <div css={tw`flex items-center gap-2`}>
                                                <FontAwesomeIcon
                                                    icon={categoryIcons[category] || faBell}
                                                    css={tw`text-gray-400`}
                                                />
                                                <h4 css={tw`font-medium`}>{categoryLabels[category] || category}</h4>
                                            </div>
                                            <div css={tw`flex gap-2`}>
                                                <Button
                                                    size="xsmall"
                                                    variant="secondary"
                                                    onClick={() => toggleCategory(events, true)}
                                                >
                                                    Enable All
                                                </Button>
                                                <Button
                                                    size="xsmall"
                                                    variant="secondary"
                                                    onClick={() => toggleCategory(events, false)}
                                                >
                                                    Disable All
                                                </Button>
                                            </div>
                                        </div>
                                        <div css={tw`space-y-2`}>
                                            {events.map(event => (
                                                <div key={event} css={tw`flex items-center justify-between py-1`}>
                                                    <span css={tw`text-sm text-gray-300`}>
                                                        {eventConfig.labels[event] || event}
                                                    </span>
                                                    <Switch
                                                        name={event}
                                                        checked={preferences[event] ?? true}
                                                        onChange={() => togglePreference(event)}
                                                    />
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                ))}

                                <div css={tw`flex justify-end`}>
                                    <Button onClick={updatePreferences} disabled={isLoading}>
                                        Save Preferences
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </div>
        </ContentBox>
    );
};
