<?php

namespace DarkOak\Http\Controllers\Api\Client\Notifications;

use DarkOak\Http\Controllers\Api\Client\ClientApiController;
use DarkOak\Http\Requests\Api\Client\ClientApiRequest;
use DarkOak\Services\Notifications\PushNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PushSubscriptionController extends ClientApiController
{
    /**
     * Push notification service instance.
     */
    protected PushNotificationService $pushService;

    /**
     * Constructor.
     */
    public function __construct(PushNotificationService $pushService)
    {
        parent::__construct();
        $this->pushService = $pushService;
    }

    /**
     * Get push notification configuration for the current user.
     */
    public function config(ClientApiRequest $request): array
    {
        $user = $request->user();
        $cacheKey = "client.push-notifications.config.{$user->id}";

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($user) {
            $stats = $this->pushService->getUserStats($user);
            $availableEvents = $this->getAvailableEventsWithLabels();

            return [
                'object' => 'push_notifications_config',
                'attributes' => [
                    'enabled' => $stats['notifications_enabled'],
                    'vapid_public_key' => $stats['vapid_public_key'],
                    'subscriptions_count' => $stats['subscriptions_count'],
                    'active_subscriptions' => $stats['active_subscriptions'],
                    'preferences' => $stats['preferences'] ?: $this->getDefaultPreferences(),
                    'available_events' => $availableEvents,
                ],
            ];
        });
    }

    /**
     * Store a new push subscription.
     */
    public function store(ClientApiRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string|max:500',
            'keys' => 'required|array',
            'keys.p256dh' => 'required|string|max:255',
            'keys.auth' => 'required|string|max:255',
        ]);

        if (!$this->pushService->hasVapidKeys()) {
            return new JsonResponse(
                ['error' => 'Push notifications are not configured'],
                JsonResponse::HTTP_SERVICE_UNAVAILABLE
            );
        }

        $user = $request->user();
        $subscription = $this->pushService->subscribe($user, $validated);

        // Clear config cache
        Cache::forget("client.push-notifications.config.{$user->id}");

        return new JsonResponse([
            'object' => 'push_subscription',
            'attributes' => [
                'uuid' => $subscription->uuid,
                'endpoint' => $subscription->endpoint,
                'created_at' => $subscription->created_at->toIso8601String(),
            ],
        ], JsonResponse::HTTP_CREATED);
    }

    /**
     * Delete a push subscription.
     */
    public function delete(ClientApiRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
        ]);

        $user = $request->user();
        $success = $this->pushService->unsubscribe($user, $validated['endpoint']);

        // Clear config cache
        Cache::forget("client.push-notifications.config.{$user->id}");

        if (!$success) {
            return new JsonResponse(
                ['error' => 'Subscription not found'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        return new JsonResponse([], JsonResponse::HTTP_NO_CONTENT);
    }

    /**
     * Update user notification preferences.
     */
    public function updatePreferences(ClientApiRequest $request): JsonResponse
    {
        $validated = $request->validate([
            'preferences' => 'required|array',
            'preferences.*' => 'string|in:server.created,server.deleted,server.suspended,server.unsuspended,backup.completed,backup.failed,server.crashed,server.reinstalled,billing.invoice.generated,billing.payment.success,billing.payment.failed,alert.security,alert.maintenance',
        ]);

        $user = $request->user();
        $this->pushService->updateUserPreferences($user, $validated['preferences']);

        // Clear config cache
        Cache::forget("client.push-notifications.config.{$user->id}");

        // Send test notification
        $this->pushService->sendToUser(
            $user,
            'Notifications Enabled',
            'You will now receive push notifications for your selected events.',
            ['tag' => 'notification-settings-updated'],
            null
        );

        return new JsonResponse([
            'object' => 'notification_preferences',
            'attributes' => [
                'preferences' => $validated['preferences'],
            ],
        ]);
    }

    /**
     * Test push notifications for the current user.
     */
    public function test(ClientApiRequest $request): JsonResponse
    {
        if (!$this->pushService->hasVapidKeys()) {
            return new JsonResponse(
                ['error' => 'Push notifications are not configured'],
                JsonResponse::HTTP_SERVICE_UNAVAILABLE
            );
        }

        $user = $request->user();
        $sent = $this->pushService->sendToUser(
            $user,
            'Test Notification',
            'This is a test notification from DarkOaktyl. If you see this, push notifications are working!',
            [
                'tag' => 'test-notification',
                'requireInteraction' => true,
                'actions' => [
                    ['action' => 'view', 'title' => 'View Dashboard'],
                ],
            ],
            null
        );

        if ($sent === 0) {
            return new JsonResponse(
                ['error' => 'No active subscriptions found. Please enable notifications in your browser first.'],
                JsonResponse::HTTP_BAD_REQUEST
            );
        }

        return new JsonResponse([
            'message' => 'Test notification sent successfully',
            'sent_count' => $sent,
        ]);
    }

    /**
     * Get available events with labels.
     */
    protected function getAvailableEventsWithLabels(): array
    {
        $categories = [
            'server' => [
                ['id' => 'server.created', 'label' => 'Server Created'],
                ['id' => 'server.deleted', 'label' => 'Server Deleted'],
                ['id' => 'server.suspended', 'label' => 'Server Suspended'],
                ['id' => 'server.unsuspended', 'label' => 'Server Unsuspended'],
                ['id' => 'server.crashed', 'label' => 'Server Crashed'],
                ['id' => 'server.reinstalled', 'label' => 'Server Reinstalled'],
            ],
            'backup' => [
                ['id' => 'backup.completed', 'label' => 'Backup Completed'],
                ['id' => 'backup.failed', 'label' => 'Backup Failed'],
            ],
            'billing' => [
                ['id' => 'billing.invoice.generated', 'label' => 'New Invoice'],
                ['id' => 'billing.payment.success', 'label' => 'Payment Successful'],
                ['id' => 'billing.payment.failed', 'label' => 'Payment Failed'],
            ],
            'alert' => [
                ['id' => 'alert.security', 'label' => 'Security Alert'],
                ['id' => 'alert.maintenance', 'label' => 'Maintenance Alert'],
            ],
        ];

        return $categories;
    }

    /**
     * Get default notification preferences (all events enabled).
     */
    protected function getDefaultPreferences(): array
    {
        return [
            'server.created',
            'server.deleted',
            'server.suspended',
            'server.unsuspended',
            'server.crashed',
            'server.reinstalled',
            'backup.completed',
            'backup.failed',
            'billing.invoice.generated',
            'billing.payment.success',
            'billing.payment.failed',
            'alert.security',
            'alert.maintenance',
        ];
    }
}
