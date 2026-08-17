<?php

use DarkOak\Models\ApiKey;
use Illuminate\Database\Migrations\Migration;

class EncryptExistingApiTokens extends Migration
{
    /**
     * Run the migrations.
     *
     * Encrypts any API tokens that are still stored in plaintext.
     * This is a security hardening migration.
     */
    public function up(): void
    {
        // Get all API keys
        $apiKeys = ApiKey::all();

        $encryptedCount = 0;
        $alreadyEncryptedCount = 0;

        foreach ($apiKeys as $apiKey) {
            $token = $apiKey->token;

            // Check if token is already encrypted (Laravel encrypted strings start with 'eyJpdiI6')
            if ($this->isEncrypted($token)) {
                ++$alreadyEncryptedCount;
                continue;
            }

            // Token is plaintext, encrypt it
            try {
                $apiKey->token = encrypt($token);
                $apiKey->save();
                ++$encryptedCount;

                Log::info('Migrated plaintext API token to encrypted storage', [
                    'id' => $apiKey->id,
                    'identifier' => $apiKey->identifier,
                ]);
            } catch (Exception $e) {
                Log::error('Failed to encrypt API token', [
                    'id' => $apiKey->id,
                    'identifier' => $apiKey->identifier,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('API token encryption migration completed', [
            'total_processed' => $apiKeys->count(),
            'encrypted' => $encryptedCount,
            'already_encrypted' => $alreadyEncryptedCount,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * Note: We cannot reliably decrypt all tokens since we don't have the
     * plaintext secrets. This migration is intentionally one-way for security.
     */
    public function down(): void
    {
        // This migration cannot be reversed safely.
        // Tokens are encrypted for security and cannot be decrypted
        // without knowing which ones were originally plaintext.
        Log::warning('Cannot reverse API token encryption migration');
    }

    /**
     * Check if a string appears to be Laravel encrypted.
     */
    private function isEncrypted(string $value): bool
    {
        // Laravel encrypted values are base64 encoded and start with specific pattern
        // when decoded. They're typically longer than 100 chars.
        if (strlen($value) < 50) {
            return false;
        }

        // Try to detect encrypted format (base64 encoded JSON)
        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            return false;
        }

        // Check for Laravel encryption structure
        $json = json_decode($decoded, true);

        return is_array($json) && isset($json['iv']) && isset($json['value']);
    }
}
