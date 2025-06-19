<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\User\DeviceToken;
use App\Models\User\Wallet;
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
use Illuminate\Support\Facades\Crypt;
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
        'bvn',
        'kyc_status',
        'kyb_status',
        'password',
        'customer_code',
        'email_verified_at',
        'role_id',
        'account_type',
        'main_account_id',
        'user_type',
        'is_active',
        'bvn_status',
        'referred_by_user_id',
        'transaction_pin_updated_at',
        'push_in_app_notifications',
        'last_logged_in_device',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'role_id',
        'bvn',
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
        'is_active' => 'boolean',
    ];

    protected $appends = ['last_name', 'first_name'];


    public function role(): HasOne
    {
        return $this->hasOne(Role::class);
    }

    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function Transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
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
        $parts = explode(' ', $this->name);

        if (count($parts) > 1) {
            return end($parts);
        }

        return null;
    }

    /**
     * Returns the user's first name
     * @return string|null
     */
    public function getFirstNameAttribute()
    {
        return explode(' ', $this->name)[0] ?? null;
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

    public function suspend()
    {
        $this->is_active = false;
        $this->save();
    }

    public function activate()
    {
        $this->is_active = true;
        $this->save();
    }

    public function isMainAccount(): bool
    {
        return $this->account_type === 'main';
    }

    public function isOrganization(): bool
    {
        return $this->user_type === 'organization';
    }

    public function kycVerified(): bool
    {
        return $this->kyc_status === 'SUCCESSFUL';
    }

    public function kybVerified(): bool
    {
        return $this->kyb_status === 'SUCCESSFUL';
    }

    public function bvnVerified(): bool
    {
        return $this->bvn_status === 'SUCCESSFUL';
    }

    public function hasPhoneNumber(): bool
    {
        return !is_null($this->phone_number);
    }

    public function hasCustomerCode(): bool
    {
        return !is_null($this->customer_code);
    }

    public function subAccounts()
    {
        return $this->hasMany(User::class, 'main_account_id');
    }

    public function mainAccount()
    {
        return $this->belongsTo(User::class, 'main_account_id');
    }

    public function scopeWithBvn($query, $bvn)
    {
        // For small user bases (<1000 users)
        return $query->whereIn('id', User::where('bvn_status', 'SUCCESSFUL')->get()
            ->filter(fn($user) => Crypt::decryptString($user->bvn) === $bvn)
            ->pluck('id')
        );
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
        return $this->deviceTokens()->whereStatus('ACTIVE')->pluck('token')->toArray();
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
