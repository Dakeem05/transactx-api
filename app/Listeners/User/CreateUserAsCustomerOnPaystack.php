<?php

namespace App\Listeners\User;

use App\Events\User\UserAccountUpdated;
use App\Services\External\PaystackService;
use App\Services\UserService;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class CreateUserAsCustomerOnPaystack implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserAccountUpdated $event): void
    {
        try {
            $user = $event->user;

            if (!$user->hasPhoneNumber()) {
                logger("User $user->id does not have a phone number. Cannot create customer now. Returning...");
                return;
            }

            if ($user->hasCustomerCode() == true) {
                logger("User $user->id already has a customer code. Cannot create customer again. Returning...");
                return;
            }

            $paystackService = resolve(PaystackService::class);
            $userService = resolve(UserService::class);

            $response = $paystackService->createCustomer(
                $user->first_name,
                $user->last_name,
                $user->email,
                $user->phone_number,
            );

            if (!$response['status'] == 'success') {
                throw new InvalidArgumentException('Could not create customer');
            }

            $customer_code = $response['data']['customer_code'];

            $userService->updateUserAccount($user, [
                'customer_code' => $customer_code
            ]);
            // 
        } catch (InvalidArgumentException $e) {
            Log::error('CreateUserAsCustomerOnPaystack: ' . $e->getMessage());
        } catch (Exception $e) {
            Log::error('CreateUserAsCustomerOnPaystack: ' . $e->getMessage());
        }
    }
}
