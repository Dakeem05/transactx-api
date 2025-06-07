<?php

namespace App\Http\Middleware;

use App\Helpers\TransactX;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserIsOrganization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return TransactX::response(false, 'Unauthenticated', 401);
        }
        // Check if the user is active

        if (!$user->isOrganization()) {
            return TransactX::response(false, 'User is not an organization.', 403);
        }

        return $next($request);
    }
}
