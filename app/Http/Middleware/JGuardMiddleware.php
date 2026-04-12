<?php

namespace DarkOak\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JGuardMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Check if jGuard is enabled
        if (!config('modules.auth.jguard.enabled', false)) {
            return $next($request);
        }

        // Only check for authenticated users
        if (!$request->user()) {
            return $next($request);
        }

        // Skip jGuard check for the jGuard page itself to avoid redirect loops
        if ($request->is('auth/jguard')) {
            return $next($request);
        }

        // Check if user has an active jGuard delay entry
        $jguardEntry = DB::table('jguard_delay')
            ->where('user_id', $request->user()->id)
            ->first();

        if ($jguardEntry) {
            // Check if the delay has expired
            $expiresAt = Carbon::parse($jguardEntry->expires_at);
            
            if ($expiresAt->isFuture()) {
                // User is still blocked - redirect to jGuard page
                $remainingMinutes = $expiresAt->diffInMinutes(Carbon::now());
                
                return redirect()
                    ->route('auth.jguard')
                    ->with('jguard_remaining', $remainingMinutes);
            } else {
                // Delay has expired - clean up the entry
                DB::table('jguard_delay')
                    ->where('user_id', $request->user()->id)
                    ->delete();
            }
        }

        return $next($request);
    }
}
