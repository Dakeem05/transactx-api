<?php

namespace App\Rules;

use App\Actions\Auth\ValidateReferralCodeAction;
use App\Dtos\Referral\UpdateReferralDto;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Log;

class ValidReferralCodeRule implements ValidationRule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(private ?string $uuid = null)
    {
        //
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     * 
     * @return bool
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        ValidateReferralCodeAction::handle(UpdateReferralDto::from(['referral_code' => $value]));
    }


    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        Log::channel('daily')->info('REGISTER USER: VALIDATE REFERRAL CODE', [
            "uid" => $this->uuid,
            "response" => "The referral code supplied is invalid",
        ]);

        return 'The referral code supplied is invalid';
    }
}
