<?php

namespace App\Http\Controllers\v1\User\Account\SubAccount;

use App\Actions\Auth\CreateSubAccountAction;
use App\Dtos\User\CreateSubAccountDto;
use App\Helpers\TransactX;
use App\Http\Controllers\Controller;
use App\Http\Requests\User\Account\CreateSubAccountRequest;
use App\Http\Resources\User\CreateSubAccountResource;
use App\Services\UserService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CreateSubAccountController extends Controller
{
    public function __construct(
    ) 
    {
        $this->middleware('user.subscription.subaccount');
    }
     /**
     * Handle the incoming request.
     */
    public function __invoke(CreateSubAccountRequest $request)
    {
        try {
            $request_data = CreateSubAccountDto::from($request->validated());
            
            $data = CreateSubAccountAction::handle($request_data, $request);

            if ($data instanceof JsonResponse) {
                return $data;
            }
            
            return TransactX::response(true, 'Sub account created successfully', 200, new CreateSubAccountResource($data));
        } catch (Exception $e) {
            Log::error('CREATE SUB ACCOUNT: Error Encountered: ' . $e->getMessage());

            return TransactX::response(false, $e->getMessage(), 500);
        }
    }
}
