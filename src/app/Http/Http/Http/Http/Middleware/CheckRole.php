<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        $user = auth()->user();

        if (!$user->hasAnyRole($roles)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthorized. You do not have permission to access this resource.'
                ], 403);
            }

            return Inertia::render('EasyNCC/Unauthorized', [
                'message' => 'Non hai i permessi per accedere a questa pagina. Se ritieni di dover avere accesso, contatta l\'amministratore della tua azienda.',
            ])->toResponse($request)->setStatusCode(403);
        }

        return $next($request);
    }
}
