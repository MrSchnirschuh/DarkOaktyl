<?php

namespace DarkOak\Http\Middleware;

use DarkOak\Models\ApiKey;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class CheckApiKeyScope
{
    /**
     * Handle an incoming request.
     *
     * @throws AccessDeniedHttpException
     */
    public function handle(Request $request, \Closure $next, string ...$requiredScopes): mixed
    {
        // Hole den aktuellen API-Key vom Request
        $apiKey = $this->getApiKeyFromRequest($request);

        // Wenn kein API-Key gefunden wurde, fortfahren (z.B. Session-Auth)
        if (!$apiKey instanceof ApiKey) {
            return $next($request);
        }

        // Prüfe, ob der API-Key die erforderlichen Scopes hat
        if (!$this->hasRequiredScopes($apiKey, $requiredScopes)) {
            throw new AccessDeniedHttpException('Insufficient permissions. This API key does not have the required scope(s): ' . implode(', ', $this->getMissingScopes($apiKey, $requiredScopes)));
        }

        return $next($request);
    }

    /**
     * Extrahiert den API-Key aus dem Request.
     */
    private function getApiKeyFromRequest(Request $request): ?ApiKey
    {
        // Versuche über Sanctum den aktuellen Token zu bekommen
        if ($request->user()) {
            $token = $request->user()->currentAccessToken();
            if ($token instanceof ApiKey) {
                return $token;
            }
        }

        // Fallback: Prüfe Authorization Header direkt
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
            $apiKey = ApiKey::findToken($token);
            if ($apiKey) {
                return $apiKey;
            }
        }

        return null;
    }

    /**
     * Prüft, ob der API-Key alle erforderlichen Scopes besitzt.
     *
     * @param array<string> $requiredScopes
     */
    private function hasRequiredScopes(ApiKey $apiKey, array $requiredScopes): bool
    {
        $keyScopes = $this->getKeyScopes($apiKey);

        // Wenn keine Scopes gespeichert sind (legacy), hat der Key alle Berechtigungen
        if (empty($keyScopes)) {
            return true;
        }

        foreach ($requiredScopes as $scope) {
            if (!in_array($scope, $keyScopes, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Gibt die fehlenden Scopes zurück.
     *
     * @param array<string> $requiredScopes
     *
     * @return array<string>
     */
    private function getMissingScopes(ApiKey $apiKey, array $requiredScopes): array
    {
        $keyScopes = $this->getKeyScopes($apiKey);

        // Wenn keine Scopes gespeichert sind, gibt es keine fehlenden (legacy-Verhalten)
        if (empty($keyScopes)) {
            return [];
        }

        return array_filter(
            $requiredScopes,
            fn (string $scope) => !in_array($scope, $keyScopes, true)
        );
    }

    /**
     * Holt die Scopes eines API-Keys.
     *
     * @return array<string>
     */
    private function getKeyScopes(ApiKey $apiKey): array
    {
        // Scopes sind im JSON-Feld 'scopes' gespeichert
        $scopes = $apiKey->scopes ?? [];

        // Rückwärtskompatibilität: Wenn scopes null oder leer, hat Key alle Berechtigungen
        if ($scopes === null) {
            return [];
        }

        return is_array($scopes) ? $scopes : json_decode($scopes, true) ?? [];
    }

    /**
     * Hilfsmethode für Controller-Checks.
     * Prüft, ob der aktuelle Request einen Scope hat.
     */
    public static function hasScope(Request $request, string $scope): bool
    {
        $middleware = new self();
        $apiKey = $middleware->getApiKeyFromRequest($request);

        if (!$apiKey instanceof ApiKey) {
            return true; // Kein API-Key = Session-Auth = voller Zugriff
        }

        return $middleware->hasRequiredScopes($apiKey, [$scope]);
    }

    /**
     * Hilfsmethode: Prüft read/write-Scope basierend auf HTTP-Methode.
     */
    public static function checkResourceAccess(Request $request, ApiKey $apiKey, string $resource): bool
    {
        $keyScopes = $apiKey->scopes ?? [];

        // Rückwärtskompatibilität
        if (empty($keyScopes)) {
            return true;
        }

        $method = strtoupper($request->method());

        // Read-Operationen: GET, HEAD, OPTIONS
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            return in_array("{$resource}:read", $keyScopes, true);
        }

        // Write-Operationen: POST, PUT, PATCH, DELETE
        return in_array("{$resource}:write", $keyScopes, true);
    }
}
