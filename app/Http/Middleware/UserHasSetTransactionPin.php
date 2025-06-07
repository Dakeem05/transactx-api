<?php

namespace App\Http\Middleware;

use App\Helpers\TransactX;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UserHasSetTransactionPin
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
            return TransactX::response(false, 'Unauthenticated.', 401);
        }
        
        if (!$user->transaction_pin) {
            return TransactX::response(false, 'Transaction pin has not been set.', 403);
        }
        
        return $next($request);
    }
}
