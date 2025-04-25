<?php

namespace App\Http\Middleware;

use App\Helpers\TransactX;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user->is_active) {
            return TransactX::response('Your account has been temporarily suspended.', 403);
        }

        return $next($request);
    }
}
