<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistrationRequest extends Model
{
    protected $fillable = [
        'name', 'email', 'message', 'status',
        'otp', 'otp_expires_at', 'otp_attempts',
        'email_verified_at', 'api_key', 'reviewed_at',
    ];

    protected $hidden = ['otp', 'api_key'];

    protected $casts = [
        'otp_expires_at'    => 'datetime',
        'email_verified_at' => 'datetime',
        'reviewed_at'       => 'datetime',
    ];

    public function isPending(): bool   { return $this->status === 'pending'; }
    public function isApproved(): bool  { return $this->status === 'approved'; }

    public function isOtpValid(string $otp): bool
    {
        return $this->otp === $otp
            && $this->otp_expires_at
            && $this->otp_expires_at->isFuture();
    }

    public function generateOtp(): string
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $this->update([
            'otp'             => $otp,
            'otp_expires_at'  => now()->addMinutes(10),
            'otp_attempts'    => 0,
        ]);
        return $otp;
    }
}
