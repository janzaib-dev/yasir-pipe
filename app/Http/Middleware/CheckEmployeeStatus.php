<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckEmployeeStatus
{
    /**
     * Handle an incoming request.
     * Check if the authenticated user's employee profile is still active.
     * If non-active or terminated, log them out immediately.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            // Check if user's employee profile is active
            if (! $user->isEmployeeActive()) {
                Auth::logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // For AJAX requests, return JSON response
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'error' => 'Your account has been deactivated.',
                        'logout' => true,
                        'redirect' => route('login'),
                    ], 401);
                }

                // For regular requests, redirect to login with message
                return redirect()->route('login')
                    ->with('error', 'Your account has been deactivated. Please contact HR department.');
            }
        }

        return $next($request);
    }
}
