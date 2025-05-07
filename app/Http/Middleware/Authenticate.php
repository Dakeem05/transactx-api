<?php

namespace App\Http\Middleware;

use App\Helpers\TransactX;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Closure;

class Authenticate extends Middleware
{
    /**
     * Handle an unauthenticated request.
     *
     * @param \Illuminate\Http\Request $request
     * @param array $guards
     * @return void
     */
    protected function unauthenticated($request, array $guards)
    {
        // Return a plain JSON response without Laravel's wrapper
        return response()->json(
            TransactX::response(false, 'Unauthenticated', 401),
            401,
            [],
            JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }

    /**
     * Get the path the user should be redirected to when not authenticated.
     */
    protected function redirectTo($request): ?string
    {
        return null; // No redirect for APIs
    }
}