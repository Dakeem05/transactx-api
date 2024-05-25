<?php

namespace App\Http\Requests\User\Otp;

use App\Helpers\TransactX;
use Illuminate\Support\Str;

use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SendVerificationCodeRequest extends FormRequest
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
            'SEND VERIFICATION CODE: START',
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
            'email' => 'nullable|required_without:phone|email',
            'phone' => 'nullable|required_without:email|phone',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $validator->validated();

            if (isset($data['email']) && isset($data['phone'])) {
                $validator->errors()->add('email', 'You cannot provide both an email and a phone number.');
                $validator->errors()->add('phone', 'You cannot provide both an email and a phone number.');
            }

            // Include only specific country codes for phone numbers
            if (isset($data['phone'])) {
                $includedCountryCodes = [
                    '+1',  // US & Canada
                    '+44', // UK
                    '+234', // Nigeria
                    '+254', // Kenya
                    '+233', // Ghana
                ];
                $isValid = false;
                foreach ($includedCountryCodes as $code) {
                    if (strpos($data['phone'], $code) === 0) {
                        $isValid = true;
                        break;
                    }
                }
                if (!$isValid) {
                    $validator->errors()->add('phone', 'Phone numbers from this country code are not allowed.');
                }
            }
        });
    }


    /**
     * @param  Validator  $validator
     *
     * @return void
     */
    public function failedValidation(Validator $validator): void
    {
        Log::channel('daily')->info(
            'SEND VERIFICATION CODE: VALIDATION',
            ["uid" => $this->request_uuid, "response" => ['errors' => $validator->errors()]]
        );

        throw new HttpResponseException(TransactX::response($validator->errors(), 422));
    }
}
