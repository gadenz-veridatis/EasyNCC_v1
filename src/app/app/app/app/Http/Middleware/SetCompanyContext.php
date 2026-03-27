<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetCompanyContext
{
    /**
     * Handle an incoming request.
     *
     * For super-admins, allows setting company context via X-Company-Id header
     * For other users, uses their own company_id
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();

            // Super-admins can switch company context
            if ($user->isSuperAdmin() && $request->header('X-Company-Id')) {
                $companyId = $request->header('X-Company-Id');
                $request->attributes->set('company_id', $companyId);
            } else {
                // Regular users use their own company
                $request->attributes->set('company_id', $user->company_id);
            }
        }

        return $next($request);
    }
}
