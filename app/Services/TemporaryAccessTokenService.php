<?php

namespace App\Services;

use App\Models\ChildProfile;
use App\Models\GuardianProfile;
use App\Models\TemporaryAccessToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TemporaryAccessTokenService
{
    public const TTL_MINUTES = 15;

    /**
     * Generate a 15-minute OTP + QR payload for a guardian-owned child.
     */
    public function generateForChild(int $guardianUserId, int $childId): array
    {
        $ownership = $this->assertGuardianOwnsChild($guardianUserId, $childId);
        if ($ownership !== null) {
            return $ownership;
        }

        $child = ChildProfile::find($childId);
        if (!$child) {
            return $this->error('Child not found', 404);
        }

        $token = DB::transaction(function () use ($childId) {
            // Invalidate unused open tokens for this child
            TemporaryAccessToken::where('child_id', $childId)
                ->where('is_used', false)
                ->where('expires_at', '>', now())
                ->update(['expires_at' => now()]);

            $code = $this->generateUniqueOtp();
            $payload = 'SIGHT-ACCESS:' . $code . ':' . $childId . ':' . Str::uuid()->toString();

            return TemporaryAccessToken::create([
                'child_id' => $childId,
                'token_code' => $code,
                'qr_payload' => $payload,
                'expires_at' => now()->addMinutes(self::TTL_MINUTES),
                'is_used' => false,
            ]);
        });

        return $this->ok('Access token generated', [
            'token_id' => $token->id,
            'token_code' => $token->token_code,
            'qr_payload' => $token->qr_payload,
            'expires_at' => $token->expires_at->toIso8601String(),
            'ttl_seconds' => self::TTL_MINUTES * 60,
            'child_id' => $childId,
        ], 201);
    }

    /**
     * Resolve unused, unexpired token by OTP code or full QR payload.
     */
    public function findValidToken(string $codeOrPayload): ?TemporaryAccessToken
    {
        $input = trim($codeOrPayload);

        $query = TemporaryAccessToken::query()
            ->where('is_used', false)
            ->where('expires_at', '>', now());

        if (preg_match('/^\d{6}$/', $input)) {
            return $query->where('token_code', $input)->first();
        }

        return $query->where('qr_payload', $input)->first();
    }

    public function markUsed(TemporaryAccessToken $token): void
    {
        $token->update(['is_used' => true]);
    }

    private function generateUniqueOtp(): string
    {
        do {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $collision = TemporaryAccessToken::where('token_code', $code)
                ->where('is_used', false)
                ->where('expires_at', '>', now())
                ->exists();
        } while ($collision);

        return $code;
    }

    /** @return array|null error payload, or null when ownership is valid */
    private function assertGuardianOwnsChild(int $guardianUserId, int $childId): ?array
    {
        $guardian = GuardianProfile::where('user_id', $guardianUserId)->first();
        if (!$guardian) {
            return $this->error('Guardian profile not found', 404);
        }

        $owns = DB::table('guardian_child_link')
            ->where('guardian_id', $guardian->guardian_id)
            ->where('child_id', $childId)
            ->exists();

        if (!$owns) {
            return $this->error('Unauthorized', 403);
        }

        return null;
    }

    private function ok(string $message, array $data = [], int $code = 200): array
    {
        return [
            'http_code' => $code,
            'body' => [
                'status' => 'success',
                'message' => $message,
                'data' => $data,
                'errors' => null,
            ],
        ];
    }

    private function error(string $message, int $code = 400): array
    {
        return [
            'http_code' => $code,
            'body' => [
                'status' => 'error',
                'message' => $message,
                'data' => new \stdClass(),
                'errors' => ['error' => [$message]],
            ],
        ];
    }
}
