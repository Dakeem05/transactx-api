<?php

namespace App\Http\Requests\User\Account;

use App\Helpers\TransactX;
use App\Rules\FullnameRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class UpdateSubAccountRequest extends FormRequest
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
            'UPDATE SUB ACCOUNT: START',
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
            'name' => ['bail', 'nullable', 'string', new FullnameRule()],
            'username' => ['bail', 'nullable', 'sometimes', 'string', 'unique:users,username'],
            'password' => [
                'bail',
                'nullable',
                'sometimes',
                Password::min(8)->numbers()->symbols()->letters()->mixedCase(),
            ],
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
        Log::channel('daily')->info(
            'UPDATE SUB ACCOUNT: VALIDATION',
            ["uid" => $this->request_uuid, "response" => ['errors' => $validator->errors()]]
        );

        throw new HttpResponseException(TransactX::response(false, "Validation error", 422, $validator->errors()));
    }
}
