<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Facades\Laravolt\Avatar\Avatar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'role_id',
        'referred_by_user_id',
        'name',
        'email',
        'username',
        'status',
        'avatar',
        'country',
        'referral_code',
        'transaction_pin',
        'kyc_status',
        'password',
        'email_verified_at',
        'transaction_pin_updated_at',
        'push_in_app_notifications',
        'push_email_notifications'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'transaction_pin',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'transaction_pin_updated_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $appends = ['last_name', 'first_name', 'other_name'];


    public function role(): HasOne
    {
        return $this->hasOne(Role::class);
    }

    /**
     * Creates an avatar using user's email
     * @return mixed
     */
    public function create_avatar()
    {
        return Avatar::create($this->email)->toBase64();
    }

    /**
     * Return's the user's last name
     * @return string|null
     */
    public function getLastNameAttribute()
    {
        return explode(' ', $this->name)[0] ?? null;
    }

    /**
     * Returns the user's first name
     * @return string|null
     */
    public function getFirstNameAttribute()
    {
        return explode(' ', $this->name)[1] ?? null;
    }

    /**
     * Returns the user's other name
     * @return string|null
     */
    public function getOtherNameAttribute()
    {
        $parts = explode(' ', $this->name);

        $other_name = count($parts) > 2 ? end($parts) : null;

        return $other_name;
    }

    /**
     * Generate referral code for a user
     * @return void
     */
    public function generate_referral_code(): void
    {
        if ($this->referral_code)
            return;

        $firstName = strlen($this->first_name) > 6 ? substr($this->first_name, 0, 6) : $this->first_name;

        $this->referral_code = strtoupper($firstName . str()->random(3));

        $this->save();
    }

    /**
     * Get's and saves the country from the IP address
     * @param Request $request
     * @return void
     */
    public function save_country_from_ip(Request $request)
    {
        if ($this->country)
            return;

        $ip_address = explode(',', $request->header('X-Forwarded-For'))[0];

        try {
            $response =  Http::get("https://api.country.is/{$ip_address}");
            $country = $response->json()['country'];

            if ($country) {
                $this->update([
                    'country' => $country
                ]);
            }
        } catch (\Exception $e) {
            Log::error('User.getCountryFromIp(): Error Encountered when fetching user country from ip: ' . $e->getMessage());
        }
    }
}
