<?php

namespace DarkOak\Services\PushNotifications;

use DarkOak\Models\PushSubscription;
use DarkOak\Models\OrganizationMember;
use DarkOak\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    private Client $httpClient;
    private array $vapidConfig;

    public function __construct()
    {
        $this->httpClient = new Client(['timeout' => 10]);
        $this->vapidConfig = [
            'subject' => config('services.vapid.subject', 'mailto:admin@darkoaktyl.com'),
            'public_key' => config('services.vapid.public_key'),
            'private_key' => config('services.vapid.private_key'),
        ];
    }

    /**
     * Send push notification to a user for a specific event
     */
    public function notifyUser(User $user, string $event, array $payload): bool
    {
        if (!$this->isVapidConfigured()) {
            Log::warning('VAPID not configured, skipping push notification');
            return false;
        }

        $subscriptions = PushSubscription::forUser($user->id)
            ->forEvent($event)
            ->get();

        if ($subscriptions->isEmpty()) {
            return false;
        }

        $success = true;
        foreach ($subscriptions as $subscription) {
            if (!$this->sendNotification($subscription, $payload)) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Send notification to all users with a specific permission
     */
    public function notifyUsersWithPermission(string $permission, string $event, array $payload): void
    {
        $users = User::whereHas('permissions', function ($query) use ($permission) {
            $query->where('permission', $permission);
        })->get();

        foreach ($users as $user) {
            $this->notifyUser($user, $event, $payload);
        }
    }

    /**
     * Send notification to server owner
     */
    public function notifyServerOwner(int $serverId, string $event, array $payload): void
    {
        $server = \DarkOak\Models\Server::find($serverId);
        if (!$server) {
            return;
        }

        $this->notifyUser($server->user, $event, array_merge($payload, [
            'server_id' => $serverId,
            'server_name' => $server->name,
        ]));
    }

    /**
     * Send notification to organization members
     */
    public function notifyOrganization(int $organizationId, string $event, array $payload, ?int $excludeUserId = null): void
    {
        $organization = \DarkOak\Models\Organization::find($organizationId);
        if (!$organization) {
            return;
        }

        $members = $organization->members()->with('user');
        if ($excludeUserId) {
            $members->where('user_id', '!=', $excludeUserId);
        }

        foreach ($members->get() as $member) {
            assert($member instanceof OrganizationMember);
            $this->notifyUser($member->user, $event, array_merge($payload, [
                'organization_id' => $organizationId,
                'organization_name' => $organization->name,
            ]));
        }
    }

    /**
     * Send single push notification to subscription
     */
    private function sendNotification(PushSubscription $subscription, array $payload): bool
    {
        try {
            $message = json_encode([
                'title' => $payload['title'] ?? 'DarkOaktyl Notification',
                'body' => $payload['body'] ?? '',
                'icon' => $payload['icon'] ?? '/favicon.ico',
                'badge' => $payload['badge'] ?? '/favicon.ico',
                'tag' => $payload['tag'] ?? 'default',
                'data' => $payload['data'] ?? [],
                'requireInteraction' => $payload['requireInteraction'] ?? false,
                'actions' => $payload['actions'] ?? [],
            ]);

            $headers = $this->generateVapidHeaders(
                $subscription->endpoint,
                strlen($message)
            );

            $encryptedMessage = $this->encryptMessage($message, $subscription);

            $response = $this->httpClient->post($subscription->endpoint, [
                'headers' => $headers,
                'body' => $encryptedMessage,
                'http_errors' => false,
            ]);

            if ($response->getStatusCode() === 201 || $response->getStatusCode() === 200) {
                $subscription->touchLastUsed();
                return true;
            }

            // Subscription expired or invalid
            if ($response->getStatusCode() === 404 || $response->getStatusCode() === 410) {
                $subscription->delete();
            }

            Log::warning('Push notification failed', [
                'status' => $response->getStatusCode(),
                'endpoint' => substr($subscription->endpoint, 0, 50) . '...',
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Push notification exception', [
                'error' => $e->getMessage(),
                'endpoint' => substr($subscription->endpoint, 0, 50) . '...',
            ]);
            return false;
        }
    }

    /**
     * Generate VAPID authentication headers
     */
    private function generateVapidHeaders(string $endpoint, int $contentLength): array
    {
        $audience = parse_url($endpoint, PHP_URL_SCHEME) . '://' . parse_url($endpoint, PHP_URL_HOST);
        $expiration = time() + 86400; // 24 hours

        $header = json_encode(['typ' => 'JWT', 'alg' => 'ES256']);
        $payload = json_encode([
            'aud' => $audience,
            'exp' => $expiration,
            'sub' => $this->vapidConfig['subject'],
        ]);

        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        $signature = $this->signVapid($base64Header . '.' . $base64Payload);

        $jwt = $base64Header . '.' . $base64Payload . '.' . $signature;

        return [
            'Authorization' => 'WebPush ' . $jwt,
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => $contentLength,
            'TTL' => '3600',
            'Crypto-Key' => 'p256ecdsa=' . $this->vapidConfig['public_key'],
        ];
    }

    /**
     * Sign VAPID JWT using ECDSA
     */
    private function signVapid(string $data): string
    {
        $privateKey = $this->vapidConfig['private_key'];
        
        // Use OpenSSL for ECDSA signing
        $signature = '';
        openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    }

    /**
     * Encrypt message for Web Push
     */
    private function encryptMessage(string $message, PushSubscription $subscription): string
    {
        // Simplified encryption - in production, use web-push-libs
        // This is a placeholder for the actual encryption logic
        // Real implementation would use proper ECE (Encrypted Content-Encoding)
        
        return $message;
    }

    /**
     * Check if VAPID is properly configured
     */
    private function isVapidConfigured(): bool
    {
        return !empty($this->vapidConfig['public_key']) 
            && !empty($this->vapidConfig['private_key']);
    }

    /**
     * Clean up stale subscriptions
     */
    public function cleanupStaleSubscriptions(int $days = 30): int
    {
        $count = PushSubscription::stale($days)->delete();
        Log::info('Cleaned up stale push subscriptions', ['count' => $count]);
        return $count;
    }

    /**
     * Get VAPID public key for frontend
     */
    public function getVapidPublicKey(): ?string
    {
        return $this->vapidConfig['public_key'] ?? null;
    }
}