<?php

namespace App\Models\APP_KEEP_MONEY;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class PasswordResetOtp extends Model
{
    protected $fillable = [
        'email',
        'otp',
        'expires_at',
        'is_used'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    /**
     * Check if OTP is expired
     */
    public function isExpired()
    {
        return $this->expires_at < Carbon::now();
    }

    /**
     * Check if OTP is valid (not used and not expired)
     */
    public function isValid()
    {
        return !$this->is_used && !$this->isExpired();
    }

    /**
     * Mark OTP as used
     */
    public function markAsUsed()
    {
        $this->update(['is_used' => true]);
    }

    /**
     * Generate random 6-digit OTP
     */
    public static function generateOtp()
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Create new OTP for email
     */
    public static function createForEmail($email, $expiresInMinutes = 5)
    {
        // Delete old OTPs for this email
        self::where('email', $email)->delete();

        // Create new OTP
        return self::create([
            'email' => $email,
            'otp' => self::generateOtp(),
            'expires_at' => Carbon::now()->addMinutes($expiresInMinutes),
            'is_used' => false,
        ]);
    }

    /**
     * Find valid OTP for email
     */
    public static function findValidOtp($email, $otp)
    {
        return self::where('email', $email)
            ->where('otp', $otp)
            ->where('is_used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();
    }
}
