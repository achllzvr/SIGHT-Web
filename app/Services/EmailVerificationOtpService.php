<?php

namespace App\Services;

use App\Models\EmailVerificationOtp;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmailVerificationOtpService
{
    private const TTL_MINUTES = 10;
    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly PhpMailerService $mailer,
    ) {
    }

    public function createAndSend(User $user): void
    {
        $this->invalidateForUser((int) $user->user_id);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        EmailVerificationOtp::create([
            'user_id' => $user->user_id,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            'attempts' => 0,
        ]);

        $this->mailer->sendEmailVerificationOtp($user, $code);
    }

    public function verify(string $email, string $otp): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return $this->error('Invalid verification request.', 404);
        }

        if ($user->email_verified_at) {
            return $this->success('Email is already verified.');
        }

        $record = EmailVerificationOtp::where('user_id', $user->user_id)
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->first();

        if (!$record) {
            return $this->error('Verification code expired or not found. Please request a new code.', 400);
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            return $this->error('Too many failed attempts. Please request a new code.', 429);
        }

        if (!Hash::check($otp, $record->code_hash)) {
            $record->increment('attempts');

            return $this->error('Invalid verification code.', 422);
        }

        DB::transaction(function () use ($user, $record) {
            $user->email_verified_at = now();
            $user->save();

            EmailVerificationOtp::where('user_id', $user->user_id)->delete();
        });

        return $this->success('Email verified successfully.');
    }

    public function resend(string $email): array
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return $this->error('Invalid verification request.', 404);
        }

        if ($user->email_verified_at) {
            return $this->success('Email is already verified.');
        }

        try {
            $this->createAndSend($user);
        } catch (\Throwable $e) {
            logger()->error('Failed to resend verification OTP: ' . $e->getMessage());

            return $this->error('Unable to send verification email. Please try again later.', 500);
        }

        return $this->success('Verification code sent.');
    }

    private function invalidateForUser(int $userId): void
    {
        EmailVerificationOtp::where('user_id', $userId)->delete();
    }

    private function success(string $message): array
    {
        return [
            'http_code' => 200,
            'body' => [
                'status' => 'success',
                'message' => $message,
            ],
        ];
    }

    private function error(string $message, int $httpCode): array
    {
        return [
            'http_code' => $httpCode,
            'body' => [
                'status' => 'error',
                'message' => $message,
            ],
        ];
    }
}
