<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\User\DeviceToken;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Facades\Laravolt\Avatar\Avatar;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\MessageTarget;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasUuids;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'username',
        'status',
        'avatar',
        'country',
        'referral_code',
        'transaction_pin',
        'kyc_status',
        'password',
        'email_verified_at',
        'role_id',
        'referred_by_user_id',
        'transaction_pin_updated_at',
        'push_in_app_notifications',
        'last_logged_in_device'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'role_id',
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
        'transaction_pin' => 'hashed',
        'push_in_app_notifications' => 'boolean',
        'push_email_notifications' => 'boolean',
    ];

    protected $appends = ['last_name', 'first_name', 'other_name'];


    public function role(): HasOne
    {
        return $this->hasOne(Role::class);
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    /**
     * Creates an avatar using user's email
     * @return mixed
     */
    public function createAvatar()
    {
        return Avatar::create($this->email)->toBase64();
    }

    /**
     * Return's the user's last name
     * @return string|null
     */
    public function getLastNameAttribute()
    {
        if (is_null($this->name))
            return null;
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
    public function generateReferralCode(): void
    {
        if ($this->referral_code)
            return;

        $firstName = strlen($this->first_name) > 6 ? substr($this->first_name, 0, 6) : $this->first_name;

        $this->referral_code = strtoupper($firstName . str()->random(3));

        $this->save();
    }


    public function kycVerified(): bool
    {
        return $this->kyc_status === 'SUCCESSFUL';
    }


    /**
     * Get's and saves the country from the IP address
     * @param Request $request
     * @return void
     */
    public function saveCountryFromIP(Request $request)
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

    /**
     * Updates the last logged in device for a user
     * @param string $device
     * @return void
     */
    public function updateLastLoggedInDevice(string $device)
    {
        $this->update([
            'last_logged_in_device' => $device
        ]);
    }

    /**
     * Route notifications for the mail channel.
     *
     * @return  array<string, string>|string
     */
    public function routeNotificationForMail(Notification $notification): array|string
    {
        // Return email address and name...
        return [$this->email => $this->name ?? null];
    }


    /**
     * Route notifications for the fcm channel.
     *
     * @return  array<string, string>|string
     */
    public function routeNotificationForFCM($notification)
    {
        return $this->deviceTokens->whereStatus('ACTIVE')->pluck('token')->toArray();
    }

    /**
     * Optional method to determine which message target to use
     * We will use TOKEN type when not specified
     *
     * @see MessageTarget::TYPES
     */
    public function routeNotificationForFCMTargetType($notification)
    {
        return MessageTarget::TOKEN;
    }

    /**
     * Optional method to determine which Firebase project to use
     * We will use default project when not specified
     */
    public function routeNotificationForFCMProject($notification)
    {
        return config('firebase.default');
    }
}
