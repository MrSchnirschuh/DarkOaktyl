<?php

namespace DarkOak\Console\Commands\Security;

use DarkOak\Models\ApiKey;
use Illuminate\Console\Command;

class CheckApiTokenEncryptionCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'security:check-api-token-encryption';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check encryption status of API tokens and report plaintext tokens';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $apiKeys = ApiKey::all(['id', 'identifier', 'token']);
        
        $encrypted = 0;
        $plaintext = 0;
        $issues = [];
        
        foreach ($apiKeys as $apiKey) {
            $token = $apiKey->token;
            
            // Check if token is encrypted
            if ($this->isEncrypted($token)) {
                $encrypted++;
            } else {
                $plaintext++;
                $issues[] = [
                    'id' => $apiKey->id,
                    'identifier' => $apiKey->identifier,
                    'status' => 'PLAINTEXT ⚠️',
                ];
            }
        }
        
        $this->info("API Token Security Report");
        $this->info("=========================");
        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Tokens', $apiKeys->count()],
                ['Encrypted ✅', $encrypted],
                ['Plaintext ⚠️', $plaintext],
            ]
        );
        
        if (count($issues) > 0) {
            $this->newLine();
            $this->warn("Plaintext tokens found (security risk):");
            $this->table(
                ['ID', 'Identifier', 'Status'],
                $issues
            );
            $this->newLine();
            $this->warn("Run 'php artisan migrate --path=database/migrations/2025_04_12_000000_encrypt_existing_api_tokens.php' to fix.");
            return self::FAILURE;
        }
        
        $this->newLine();
        $this->info("✅ All API tokens are properly encrypted!");
        
        return self::SUCCESS;
    }
    
    /**
     * Check if a string appears to be Laravel encrypted.
     */
    private function isEncrypted(string $value): bool
    {
        // Encrypted tokens are typically base64 encoded and longer than plaintext
        if (strlen($value) < 50) {
            return false;
        }
        
        // Try to detect encrypted format
        $decoded = base64_decode($value, true);
        if ($decoded === false) {
            return false;
        }
        
        $json = json_decode($decoded, true);
        return is_array($json) && isset($json['iv']) && isset($json['value']);
    }
}
