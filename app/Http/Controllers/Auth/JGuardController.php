<?php

namespace DarkOak\Http\Controllers\Auth;

use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use DarkOak\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class JGuardController extends Controller
{
    /**
     * Display the jGuard wait page or redirect if delay has expired.
     *
     * @param Request $request
     * @return View|RedirectResponse
     */
    public function __invoke(Request $request): View|RedirectResponse
    {
        // Check if jGuard is enabled
        if (!config('modules.auth.jguard.enabled', false)) {
            return redirect()->route('index');
        }

        // User must be logged in to see this page
        if (!$request->user()) {
            return redirect()->route('auth.login');
        }

        // Check if user has an active jGuard delay entry
        $jguardEntry = DB::table('jguard_delay')
            ->where('user_id', $request->user()->id)
            ->first();

        // If no entry exists or delay has expired, redirect to home
        if (!$jguardEntry) {
            return redirect()->route('index');
        }

        $expiresAt = Carbon::parse($jguardEntry->expires_at);
        
        // If delay has expired, clean up and redirect to home
        if ($expiresAt->isPast()) {
            DB::table('jguard_delay')
                ->where('user_id', $request->user()->id)
                ->delete();
            
            return redirect()->route('index')->with('success', 'Your account has been activated!');
        }

        // Calculate remaining time
        $remainingMinutes = $expiresAt->diffInMinutes(Carbon::now());
        $remainingSeconds = $expiresAt->diffInSeconds(Carbon::now()) % 60;

        return view('templates.auth.jguard', [
            'remainingMinutes' => $remainingMinutes,
            'remainingSeconds' => $remainingSeconds,
            'expiresAt' => $expiresAt,
        ]);
    }
}
