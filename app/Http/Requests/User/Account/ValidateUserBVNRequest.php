<?php

namespace App\Http\Requests\User\Account;

use App\Helpers\TransactX;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ValidateUserBVNRequest extends FormRequest
{

    private string $request_uuid;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }


    /**
     * @return void
     */
    public function prepareForValidation(): void
    {
        $this->request_uuid = Str::uuid()->toString();

        Log::channel('daily')->info(
            'VALIDATE USER BVN: START',
            ["uid" => $this->request_uuid, "request" => $this->except(['bvn', 'verification_id'])]
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
            'verification_id' => ['bail', 'required', 'string'],
            'otp' => ['bail', 'required', 'string'],
            'bvn' => ['bail', 'required', 'digits:11'],
        ];
    }


    /**
     * @param $key
     * @param $default
     *
     * @return array
     */
    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);

        return array_merge($data, [
            'request_uuid' => $this->request_uuid,
        ]);
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
            'VALIDATE USER BVN: VALIDATION',
            ["uid" => $this->request_uuid, "response" => ['errors' => $errors]]
        );

       throw new HttpResponseException(
            TransactX::response(false, $firstError, 422)
        );
    }
}
