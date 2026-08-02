<?php

namespace App\Services;

use App\Models\ChildProfile;
use App\Models\GuardianProfile;
use App\Models\SessionLimits;
use App\Models\User;
use App\Models\VirtualPet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RuleEngineService
{
    private function extractNameParts(array $payload): array
    {
        $firstName = trim((string) ($payload['first_name'] ?? ''));
        $lastName = trim((string) ($payload['last_name'] ?? ''));

        if ($firstName !== '' || $lastName !== '') {
            return [$firstName !== '' ? $firstName : 'Unknown', $lastName !== '' ? $lastName : 'Unknown'];
        }

        return ['Unknown', 'Unknown'];
    }

    public function registerGuardian(array $payload): array
    {
        [$firstName, $lastName] = $this->extractNameParts($payload);

        $user = User::create([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'email' => $payload['email'],
            'password_hash' => Hash::make($payload['password']),
            'role' => 'Guardian',
        ]);

        GuardianProfile::create([
            'user_id' => $user->user_id,
            'contact_number' => $payload['contact_number'] ?? null,
        ]);

        return $this->response('success', 'Guardian registered successfully', [
            'user' => [
                'id' => $user->user_id,
                'name' => $user->display_name,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
            ],
        ], null, 201);
    }

    public function addChild(int $authUserId, array $payload): array
    {
        $guardian = GuardianProfile::where('user_id', $authUserId)->first();

        if (!$guardian) {
            return $this->response('error', 'Guardian profile not found', null, ['guardian' => ['Guardian profile not found']], 404);
        }

        $result = DB::transaction(function () use ($payload, $guardian) {
            [$firstName, $lastName] = $this->extractNameParts($payload);

            // Use the mobile-provided password if it exists, otherwise generate a random one (for web)
            $passwordHash = isset($payload['mobile_password']) 
                ? Hash::make($payload['mobile_password']) 
                : Hash::make(uniqid());

            $childUser = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => null,
                'password_hash' => $passwordHash,
                'role' => 'Child',
            ]);

            // Use the mobile-provided login code if it exists, otherwise generate a unique one
            $loginCode = $payload['mobile_login_code'] ?? $this->generateUniqueLoginCode();

            $child = ChildProfile::create([
                'user_id' => $childUser->user_id,
                'birthdate' => $payload['birthdate'] ?? '2015-01-01', // Fallback for mobile
                'login_code' => $loginCode,
            ]);

            DB::table('guardian_child_link')->insert([
                'guardian_id' => $guardian->guardian_id,
                'child_id' => $child->child_id,
            ]);

            SessionLimits::create([
                'child_id' => $child->child_id,
                'daily_limit_minutes' => 120,
                'mode' => 'Relaxed',
                'is_active' => true,
                'harmful_distance_threshold' => 30,
                'critical_distance_threshold' => 10,
                'auto_enforce_breaks' => true,
            ]);

            VirtualPet::create([
                'child_id' => $child->child_id,
                'pet_state' => 'Healthy',
                'currency' => 0,
                'xp_points' => 0,
            ]);

            return [
                'child' => $child,
                'child_user' => $childUser,
                'login_code' => $loginCode,
            ];
        });

        return $this->response('success', 'Child profile created successfully', [
            'child' => [
                'child_id' => $result['child']->child_id,
                'name' => $result['child_user']->display_name,
                'first_name' => $result['child_user']->first_name,
                'last_name' => $result['child_user']->last_name,
                'birthdate' => $result['child']->birthdate,
                'login_code' => $result['login_code'],
            ],
        ], null, 201);
    }

    public function updateChildLimits(int $authUserId, int $childId, array $payload): array
    {
        $guardian = GuardianProfile::where('user_id', $authUserId)->first();

        if (!$guardian) {
            return $this->response('error', 'Guardian profile not found', null, ['guardian' => ['Guardian profile not found']], 404);
        }

        $isOwner = DB::table('guardian_child_link')
            ->where('guardian_id', $guardian->guardian_id)
            ->where('child_id', $childId)
            ->exists();

        if (!$isOwner) {
            return $this->response('error', 'Unauthorized', null, ['authorization' => ['Unauthorized']], 403);
        }

        $limits = SessionLimits::where('child_id', $childId)->first();

        if (!$limits) {
            return $this->response('error', 'Session limits not found', null, ['session_limits' => ['Session limits not found']], 404);
        }

        $limits->update([
            'daily_limit_minutes' => $payload['daily_limit_minutes'] ?? $limits->daily_limit_minutes,
            'mode' => $payload['mode'] ?? $limits->mode,
            'harmful_distance_threshold' => $payload['harmful_distance_threshold'] ?? $limits->harmful_distance_threshold,
            'critical_distance_threshold' => $payload['critical_distance_threshold'] ?? $limits->critical_distance_threshold,
            'auto_enforce_breaks' => $payload['auto_enforce_breaks'] ?? $limits->auto_enforce_breaks,
            'updated_at' => now(),
        ]);

        return $this->response('success', 'Limits updated successfully', [
            'updated_at' => optional($limits->updated_at)->toIso8601String(),
        ]);
    }

    public function deleteChild(int $authUserId, int $childId): array
    {
        $child = ChildProfile::find($childId);

        if (!$child) {
            return $this->response('error', 'Child not found', null, ['child_id' => ['Child not found']], 404);
        }

        $guardian = GuardianProfile::where('user_id', $authUserId)->first();

        if (!$guardian) {
            return $this->response('error', 'Guardian profile not found', null, ['guardian' => ['Guardian profile not found']], 404);
        }

        $isOwner = DB::table('guardian_child_link')
            ->where('guardian_id', $guardian->guardian_id)
            ->where('child_id', $childId)
            ->exists();

        if (!$isOwner) {
            return $this->response('error', 'Unauthorized', null, ['authorization' => ['Unauthorized']], 403);
        }

        DB::transaction(function () use ($child) {
            $childUser = $child->user()->first();
            $child->delete();

            if ($childUser) {
                $childUser->delete();
            }
        });

        return $this->response('success', 'Child profile deleted successfully');
    }

    private function generateUniqueLoginCode(): string
    {
        do {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (ChildProfile::where('login_code', $code)->exists());

        return $code;
    }

    private function response(string $status, string $message, ?array $data = [], $errors = null, int $httpCode = 200): array
    {
        return [
            'http_code' => $httpCode,
            'body' => [
                'status' => $status,
                'message' => $message,
                'data' => $data ?? new \stdClass(),
                'errors' => $errors,
            ],
        ];
    }
}
