<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class UserPresenceService
{
    public const ONLINE_MINUTES = 5;
    public const STANDBY_HOURS = 24;

    public function touch(User $user): void
    {
        if (!Schema::hasColumn('user', 'last_seen_at')) {
            return;
        }

        $user->forceFill(['last_seen_at' => now()])->save();
    }

    public function markOffline(User $user): void
    {
        if (!Schema::hasColumn('user', 'last_seen_at')) {
            return;
        }

        $user->forceFill(['last_seen_at' => null])->save();
    }

    /**
     * @return array{presence_status: string, presence_label: string, last_active: string}
     */
    public function summarize(?User $user): array
    {
        if (!$user || !Schema::hasColumn('user', 'last_seen_at') || empty($user->last_seen_at)) {
            return [
                'presence_status' => 'offline',
                'presence_label' => 'Offline',
                'last_active' => 'Never',
            ];
        }

        $seen = $user->last_seen_at instanceof Carbon
            ? $user->last_seen_at
            : Carbon::parse($user->last_seen_at);

        $minutes = $seen->diffInMinutes(now());
        $hours = $seen->diffInHours(now());

        if ($minutes < self::ONLINE_MINUTES) {
            $status = 'online';
            $label = 'Online';
        } elseif ($hours < self::STANDBY_HOURS) {
            $status = 'standby';
            $label = 'Standby';
        } else {
            $status = 'offline';
            $label = 'Offline';
        }

        return [
            'presence_status' => $status,
            'presence_label' => $label,
            'last_active' => $seen->diffForHumans(),
        ];
    }
}
