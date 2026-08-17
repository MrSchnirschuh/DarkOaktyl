<?php

namespace DarkOak\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidWebhookUrl implements Rule
{
    /**
     * Blocked internal IP ranges to prevent SSRF.
     */
    protected array $blockedRanges = [
        '127.0.0.0/8',     // Loopback
        '10.0.0.0/8',      // Private
        '172.16.0.0/12',   // Private
        '192.168.0.0/16',  // Private
        '0.0.0.0/8',       // Reserved
        '169.254.0.0/16',  // Link-local
        '::1',             // IPv6 loopback
        'fc00::/7',        // IPv6 private
    ];

    public function passes($attribute, $value): bool
    {
        // Must be HTTPS
        if (!str_starts_with($value, 'https://')) {
            return false;
        }

        $parsed = parse_url($value);
        if (!$parsed || !isset($parsed['host'])) {
            return false;
        }

        $host = $parsed['host'];

        // Block localhost variations
        $blockedHosts = ['localhost', '127.0.0.1', '0.0.0.0', '[::1]', 'metadata.google.internal'];
        if (in_array($host, $blockedHosts, true)) {
            return false;
        }

        // Resolve DNS and check IP ranges
        $ips = gethostbynamel($host) ?: [$host];
        foreach ($ips as $ip) {
            if ($this->isInternalIp($ip)) {
                return false;
            }
        }

        return true;
    }

    protected function isInternalIp(string $ip): bool
    {
        // Check loopback
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return true;
        }

        return false;
    }

    public function message(): string
    {
        return 'The :attribute must be a valid HTTPS URL pointing to a public IP address.';
    }
}
