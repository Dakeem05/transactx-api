<?php

namespace App\Http\Middleware\Subscription;

use App\Helpers\TransactX;
use App\Models\LinkedBankAccount as ModelsLinkedBankAccount;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LinkedBankAccount
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

        $features = json_decode($user->subscription->model->features);
        $count = ModelsLinkedBankAccount::where('user_id', $user->id)->count();
        dd($count);
        if ($features->linked_bank_accounts->limit == 0 || $count == $features->linked_bank_accounts->limit) {
            return TransactX::response(false, 'You are not allowed to proceed due to your subscription plan.', 403);
        }

        return $next($request);
    }
}
