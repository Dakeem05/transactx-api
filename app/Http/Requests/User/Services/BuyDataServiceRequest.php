<?php

namespace App\Http\Requests\User\Services;

use App\Helpers\TransactX;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class BuyDataServiceRequest extends FormRequest
{

    private string $request_uuid;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


    public function prepareForValidation(): void
    {
        $this->request_uuid = Str::uuid()->toString();

        Log::channel('daily')->info(
            'BUY AIRTIME SERVICE REQUEST: START',
            ["uid" => $this->request_uuid, "request" => $this->all()]
        );
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id' => ['bail', 'required', 'string'],
            'amount' => ['bail', 'required', 'numeric'],
            'total_amount' => ['bail', 'required', 'numeric'],
            'phone_number' => ['bail', 'required', 'digits:11'],
            'plan' => ['bail', 'required', 'string'],
            'validity' => ['bail', 'required', 'string'],
            'network' => ['bail', 'required', 'string'],
            'add_beneficiary' => ['bail', 'boolean', 'required'],
        ];
    }

   /**
     * @param  Validator  $validator
     *
     * @return void
     */
    public function failedValidation(Validator $validator): void
    {
        $errors = $validator->errors()->toArray();
        
        // Get the first validation error message
        $firstError = collect($errors)->flatten()->first();

        Log::channel('daily')->info(
            'BUY AIRTIME SERVICE REQUEST',
            ["uid" => $this->request_uuid, "response" => ['errors' => $errors]]
        );

       throw new HttpResponseException(
            TransactX::response(false, $firstError, 422)
        );
    }
}
