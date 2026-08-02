<?php

namespace Tests\Feature;

use App\Models\EmailVerificationOtp;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmailOtpVerificationTest extends TestCase
{
    public function test_verify_requires_otp(): void
    {
        $user = $this->createUnverifiedGuardian('verify-required@example.com');

        $response = $this->postJson('/api/mobile/guardian/verify-email', [
            'email' => $user->email,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['otp']);
    }

    public function test_verify_rejects_wrong_otp(): void
    {
        $user = $this->createUnverifiedGuardian('wrong-otp@example.com');

        EmailVerificationOtp::create([
            'user_id' => $user->user_id,
            'code_hash' => Hash::make('123456'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        $response = $this->postJson('/api/mobile/guardian/verify-email', [
            'email' => $user->email,
            'otp' => '000000',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Invalid verification code.',
            ]);

        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    public function test_verify_accepts_correct_otp(): void
    {
        $user = $this->createUnverifiedGuardian('correct-otp@example.com');

        EmailVerificationOtp::create([
            'user_id' => $user->user_id,
            'code_hash' => Hash::make('654321'),
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        $response = $this->postJson('/api/mobile/guardian/verify-email', [
            'email' => $user->email,
            'otp' => '654321',
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Email verified successfully.',
            ]);

        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }
}
