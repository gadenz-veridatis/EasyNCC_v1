<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && !auth()->user()->is_active) {
            $user = auth()->user();

            // Revoke all tokens for Sanctum API authentication
            // Check if it's a real token (not a TransientToken)
            $token = $user->currentAccessToken();
            if ($token && !($token instanceof \Laravel\Sanctum\TransientToken)) {
                $token->delete();
            }

            // Logout for web guard (session-based)
            if (auth()->guard('web')->check()) {
                auth()->guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return response()->json([
                'message' => 'Your account has been deactivated. Please contact your administrator.'
            ], 403);
        }

        return $next($request);
    }
}
