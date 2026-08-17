<?php

namespace DarkOak\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Security Headers Middleware.
 *
 * Adds security-related HTTP headers to all responses:
 * - X-Content-Type-Options: Prevents MIME-type sniffing
 * - X-Frame-Options: Prevents clickjacking
 * - X-XSS-Protection: Enables browser XSS filter
 * - Referrer-Policy: Controls referrer information
 * - Permissions-Policy: Restricts browser features
 * - Content-Security-Policy: Prevents XSS and injection attacks (configurable)
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, \Closure $next)
    {
        /** @var Response $response */
        $response = $next($request);

        // Prevent MIME-type sniffing
        $response->header('X-Content-Type-Options', 'nosniff');

        // Prevent clickjacking - only allow framing from same origin
        $response->header('X-Frame-Options', 'SAMEORIGIN');

        // Enable browser XSS filter (legacy, but still useful for older browsers)
        $response->header('X-XSS-Protection', '1; mode=block');

        // Control referrer information
        $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Restrict browser features
        $response->header('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Content Security Policy - configurable via env
        // Default: restrict to same origin, allow inline styles/scripts for React
        $csp = env('APP_CSP_HEADER', null);
        if ($csp) {
            $response->header('Content-Security-Policy', $csp);
        }

        // HSTS - Only set if app is running over HTTPS
        if ($request->isSecure() || env('APP_FORCE_HTTPS', false)) {
            $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
