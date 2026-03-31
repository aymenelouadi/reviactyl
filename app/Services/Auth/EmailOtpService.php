<?php

namespace Pterodactyl\Services\Auth;

use Carbon\CarbonImmutable;
use Pterodactyl\Models\User;
use Illuminate\Support\Facades\Hash;
use Pterodactyl\Models\EmailOtpToken;

class EmailOtpService
{
    private const OTP_LENGTH = 6;
    private const OTP_EXPIRY_MINUTES = 10;

    /**
     * Generate a new OTP code for the user, deleting any existing tokens first.
     * Returns the plain-text code to be sent via email.
     */
    public function generate(User $user): string
    {
        // Remove any existing tokens for this user before creating a new one.
        EmailOtpToken::where('user_id', $user->id)->delete();

        $code = str_pad((string) random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);

        EmailOtpToken::create([
            'user_id'    => $user->id,
            'token_hash' => Hash::make($code),
            'expires_at' => CarbonImmutable::now()->addMinutes(self::OTP_EXPIRY_MINUTES),
        ]);

        return $code;
    }

    /**
     * Verify a given OTP code for the user.
     * Deletes the token on successful verification (one-time use).
     */
    public function verify(User $user, string $code): bool
    {
        $token = EmailOtpToken::where('user_id', $user->id)
            ->where('expires_at', '>', CarbonImmutable::now())
            ->latest()
            ->first();

        if ($token === null || !Hash::check($code, $token->token_hash)) {
            return false;
        }

        $token->delete();

        return true;
    }
}
