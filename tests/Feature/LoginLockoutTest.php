<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginLockoutTest extends TestCase
{
    public function test_expired_lock_resets_attempts_so_one_typo_does_not_re_lock(): void
    {
        $user = User::create([
            'email' => 'locked-guardian@example.com',
            'first_name' => 'Locked',
            'last_name' => 'Guardian',
            'password_hash' => Hash::make('correct-password'),
            'role' => 'guardian',
            'status' => 'active',
            'email_verified_at' => now(),
            'failed_login_attempts' => 3,
            'locked_until' => now()->subMinute(),
        ]);

        // First failure after expiry should count as attempt #1, not immediately re-lock.
        $response = $this->postJson('/api/shared/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized()
            ->assertJson(['message' => 'Invalid credentials']);

        $user->refresh();
        $this->assertSame(1, (int) $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
    }

    public function test_correct_password_after_expired_lock_logs_in(): void
    {
        $user = User::create([
            'email' => 'unlock-guardian@example.com',
            'first_name' => 'Unlock',
            'last_name' => 'Guardian',
            'password_hash' => Hash::make('correct-password'),
            'role' => 'guardian',
            'status' => 'active',
            'email_verified_at' => now(),
            'failed_login_attempts' => 5,
            'locked_until' => now()->subMinutes(20),
        ]);

        $response = $this->postJson('/api/shared/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Login successful');

        $user->refresh();
        $this->assertSame(0, (int) $user->failed_login_attempts);
        $this->assertNull($user->locked_until);
    }

    public function test_active_lock_still_blocks_login(): void
    {
        $lockedUntil = now()->addMinutes(10);
        $user = User::create([
            'email' => 'still-locked@example.com',
            'first_name' => 'Still',
            'last_name' => 'Locked',
            'password_hash' => Hash::make('correct-password'),
            'role' => 'guardian',
            'status' => 'active',
            'email_verified_at' => now(),
            'failed_login_attempts' => 3,
            'locked_until' => $lockedUntil,
        ]);

        $response = $this->postJson('/api/shared/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('message', 'Account temporarily locked due to failed login attempts');

        $user->refresh();
        $this->assertSame(3, (int) $user->failed_login_attempts);
        $this->assertNotNull($user->locked_until);
    }
}
