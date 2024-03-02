<?php

namespace App\Http\Requests\User;

use App\Helpers\TransactX;
use App\Rules\ValidReferralCodeRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RegisterUserRequest extends FormRequest
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
            'REGISTER USER: START',
            ["uid" => $this->request_uuid, "request" => $this->except(['password', 'password_confirmation'])]
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
            'username' => ['bail', 'required', 'string', 'unique:users'],
            'email' => ['bail', 'required', 'email', 'unique:users'],
            'password' => [
                'bail',
                'required',
                'confirmed',
                // Password::min(8)->numbers()->symbols()->letters()->mixedCase()->uncompromised(),
                Password::min(8)->numbers()->symbols()->letters()->mixedCase(),
            ],
            'referral_code' => ['bail', 'sometimes', 'nullable', 'string', new ValidReferralCodeRule($this->request_uuid)],
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
            'password' => Hash::make($data['password']),
        ]);
    }

    /**
     * @param  Validator  $validator
     *
     * @return void
     */
    public function failedValidation(Validator $validator): void
    {
        Log::channel('daily')->info(
            'REGISTER USER: VALIDATION',
            ["uid" => $this->request_uuid, "response" => ['errors' => $validator->errors()]]
        );

        throw new HttpResponseException(TransactX::response($validator->errors(), 422));
    }
}
