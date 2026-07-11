<?php

namespace DarkOak\Services\Notifications;

use DarkOak\Models\PushSubscription;
use DarkOak\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class PushNotificationService
{
    /**
     * WebPush instance.
     */
    protected ?WebPush $webPush = null;

    /**
     * VAPID configuration.
     */
    protected array $vapidConfig = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->vapidConfig = [
            'VAPID' => [
                'subject' => config('app.url', 'https://darkoaktyl.local'),
                'publicKey' => config('services.vapid.public_key'),
                'privateKey' => config('services.vapid.private_key'),
            ],
        ];

        if ($this->hasVapidKeys()) {
            $this->webPush = new WebPush($this->vapidConfig);
            $this->webPush->setAutomaticPadding(3052);
        }
    }

    /**
     * Check if VAPID keys are configured.
     */
    public function hasVapidKeys(): bool
    {
        return !empty(config('services.vapid.public_key'))
            && !empty(config('services.vapid.private_key'));
    }

    /**
     * Subscribe a user to push notifications.
     */
    public function subscribe(User $user, array $subscriptionData): ?PushSubscription
    {
        $endpoint = $subscriptionData['endpoint'];
        $keys = $subscriptionData['keys'] ?? $subscriptionData;

        // Check if subscription already exists
        $existing = PushSubscription::where('endpoint', $endpoint)->first();
        if ($existing) {
            // Update the existing subscription with new keys
            $existing->update([
                'public_key' => $keys['p256dh'] ?? $keys['publicKey'] ?? null,
                'auth_token' => $keys['auth'] ?? $keys['authToken'] ?? null,
                'user_id' => $user->id,
                'last_used_at' => now(),
            ]);

            return $existing;
        }

        // Create new subscription
        return PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => $endpoint,
            'public_key' => $keys['p256dh'] ?? $keys['publicKey'] ?? null,
            'auth_token' => $keys['auth'] ?? $keys['authToken'] ?? null,
            'preferences' => $user->notification_settings ?? null,
            'last_used_at' => now(),
        ]);
    }

    /**
     * Unsubscribe a user from push notifications.
     */
    public function unsubscribe(User $user, string $endpoint): bool
    {
        $deleted = PushSubscription::where('user_id', $user->id)
            ->where('endpoint', $endpoint)
            ->delete();

        return $deleted > 0;
    }

    /**
     * Send a push notification to a specific user.
     */
    public function sendToUser(User $user, string $title, string $body, array $data = [], ?string $event = null): int
    {
        if (!$this->hasVapidKeys()) {
            Log::warning('Push notifications skipped: VAPID keys not configured');
            return 0;
        }

        $subscriptions = PushSubscription::forUser($user->id);

        if ($event) {
            $subscriptions = $subscriptions->forEvent($event);
        }

        $subscriptions = $subscriptions->get();

        if ($subscriptions->isEmpty()) {
            return 0;
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'icon' => '/favicon.ico',
            'badge' => '/favicon.ico',
            'tag' => $data['tag'] ?? uniqid('push_', true),
            'data' => $data,
            'requireInteraction' => $data['requireInteraction'] ?? false,
            'actions' => $data['actions'] ?? [],
        ]);

        $successCount = 0;
        $invalidEndpoints = [];

        foreach ($subscriptions as $subscription) {
            $pushSubscription = Subscription::create([
                'endpoint' => $subscription->endpoint,
                'publicKey' => $subscription->public_key,
                'authToken' => $subscription->auth_token,
            ]);

            $report = $this->webPush->sendOneNotification(
                $pushSubscription,
                $payload
            );

            if ($report->isSuccess()) {
                $successCount++;
                $subscription->touchLastUsed();
            } else {
                $reason = $report->getReason();
                Log::warning('Push notification failed', [
                    'endpoint' => substr($subscription->endpoint, 0, 100),
                    'reason' => $reason,
                ]);

                // Mark for removal if endpoint is invalid
                if ($this->isInvalidEndpointError($reason)) {
                    $invalidEndpoints[] = $subscription->id;
                }
            }
        }

        // Clean up invalid subscriptions
        if (!empty($invalidEndpoints)) {
            PushSubscription::whereIn('id', $invalidEndpoints)->delete();
        }

        return $successCount;
    }

    /**
     * Send a push notification to multiple users.
     */
    public function sendToUsers(array $userIds, string $title, string $body, array $data = [], ?string $event = null): int
    {
        if (!$this->hasVapidKeys()) {
            return 0;
        }

        $totalSent = 0;

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user) {
                $totalSent += $this->sendToUser($user, $title, $body, $data, $event);
            }
        }

        return $totalSent;
    }

    /**
     * Send a push notification for a specific event.
     */
    public function sendForEvent(string $event, string $title, string $body, array $data = []): int
    {
        if (!$this->hasVapidKeys()) {
            return 0;
        }

        // Get all subscriptions that listen to this event
        $subscriptions = PushSubscription::forEvent($event)
            ->with('user')
            ->get()
            ->groupBy('user_id');

        if ($subscriptions->isEmpty()) {
            return 0;
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'icon' => '/favicon.ico',
            'badge' => '/favicon.ico',
            'tag' => $data['tag'] ?? uniqid('push_', true),
            'data' => array_merge($data, ['event' => $event]),
            'requireInteraction' => $data['requireInteraction'] ?? false,
            'actions' => $data['actions'] ?? [],
        ]);

        $successCount = 0;
        $invalidEndpoints = [];

        foreach ($subscriptions as $userId => $userSubscriptions) {
            foreach ($userSubscriptions as $subscription) {
                $pushSubscription = Subscription::create([
                    'endpoint' => $subscription->endpoint,
                    'publicKey' => $subscription->public_key,
                    'authToken' => $subscription->auth_token,
                ]);

                $report = $this->webPush->sendOneNotification(
                    $pushSubscription,
                    $payload
                );

                if ($report->isSuccess()) {
                    $successCount++;
                    $subscription->touchLastUsed();
                } else {
                    $reason = $report->getReason();
                    if ($this->isInvalidEndpointError($reason)) {
                        $invalidEndpoints[] = $subscription->id;
                    }
                }
            }
        }

        // Clean up invalid subscriptions
        if (!empty($invalidEndpoints)) {
            PushSubscription::whereIn('id', $invalidEndpoints)->delete();
        }

        return $successCount;
    }

    /**
     * Update user notification preferences.
     */
    public function updateUserPreferences(User $user, array $preferences): bool
    {
        $user->notification_settings = json_encode($preferences);
        $user->save();

        // Update all user's subscriptions
        PushSubscription::where('user_id', $user->id)
            ->update(['preferences' => $preferences]);

        return true;
    }

    /**
     * Get user notification preferences.
     */
    public function getUserPreferences(User $user): array
    {
        return $user->notification_settings ?? [];
    }

    /**
     * Get VAPID public key for client-side use.
     */
    public function getVapidPublicKey(): ?string
    {
        return config('services.vapid.public_key');
    }

    /**
     * Clean up stale subscriptions.
     */
    public function cleanupStaleSubscriptions(int $days = 30): int
    {
        $deleted = PushSubscription::stale($days)->delete();

        Log::info('Cleaned up stale push subscriptions', [
            'deleted' => $deleted,
            'days' => $days,
        ]);

        return $deleted;
    }

    /**
     * Check if an error indicates an invalid endpoint.
     */
    protected function isInvalidEndpointError(?string $reason): bool
    {
        if (!$reason) {
            return false;
        }

        $invalidReasons = [
            'Expired subscription',
            'Not Found',
            'Gone',
            'Unauthorized registration',
            'Invalid push subscription',
        ];

        foreach ($invalidReasons as $invalidReason) {
            if (stripos($reason, $invalidReason) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get notification statistics for a user.
     */
    public function getUserStats(User $user): array
    {
        $subscriptions = PushSubscription::where('user_id', $user->id);

        return [
            'subscriptions_count' => $subscriptions->count(),
            'active_subscriptions' => $subscriptions->clone()->where('last_used_at', '>=', now()->subDays(7))->count(),
            'preferences' => $this->getUserPreferences($user),
            'vapid_public_key' => $this->getVapidPublicKey(),
            'notifications_enabled' => $this->hasVapidKeys(),
        ];
    }
}
