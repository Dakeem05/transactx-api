<?php

namespace App\Http\Middleware;

use App\Helpers\TransactX;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsRolePermitted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$roles  The roles to check against.
     * 
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!auth()->check() || !$this->userHasAnyRole($roles)) {
            return TransactX::response('You are not authorized to perform this operation.', 403);
        }

        return $next($request);
    }

    /**
     * Check if the authenticated user has any of the specified roles.
     *
     * @param  string[]  $roles  The roles to check against.
     * @return bool
     */
    private function userHasAnyRole($roles): bool
    {
        return in_array(auth()->user()->role, $roles);
    }
}
