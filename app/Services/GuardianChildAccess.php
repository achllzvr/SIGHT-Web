<?php

namespace App\Services;

use App\Models\GuardianProfile;
use Illuminate\Support\Facades\DB;

class GuardianChildAccess
{
    public function ownsChild(int $guardianUserId, int $childId): bool
    {
        $guardian = GuardianProfile::where('user_id', $guardianUserId)->first();

        if (!$guardian) {
            return false;
        }

        return DB::table('guardian_child_link')
            ->where('guardian_id', $guardian->guardian_id)
            ->where('child_id', $childId)
            ->exists();
    }
}
