<?php

namespace App\Console\Commands;

use App\Models\GuardianProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fixes guardian accounts created while User::$incrementing = false left
 * Eloquent user_id null, producing guardian_profile rows with user_id NULL
 * and guardian users with no linked profile.
 */
class RepairOrphanGuardianProfiles extends Command
{
    protected $signature = 'lumi:repair-guardian-profiles {--dry-run : Show changes without writing}';

    protected $description = 'Link orphan guardian_profile rows and create missing profiles for Guardian users';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $nullProfiles = GuardianProfile::whereNull('user_id')->count();
        if ($nullProfiles > 0) {
            $this->warn("Found {$nullProfiles} guardian_profile row(s) with NULL user_id.");
            if ($dryRun) {
                $this->line('  [dry-run] would delete NULL user_id profile rows');
            } else {
                GuardianProfile::whereNull('user_id')->delete();
                $this->info("Deleted {$nullProfiles} NULL user_id profile row(s).");
            }
        }

        $missing = DB::table('user as u')
            ->leftJoin('guardian_profile as gp', 'gp.user_id', '=', 'u.user_id')
            ->whereRaw('LOWER(u.role) = ?', ['guardian'])
            ->whereNull('gp.guardian_id')
            ->orderBy('u.user_id')
            ->get(['u.user_id', 'u.email']);

        if ($missing->isEmpty()) {
            $this->info('All Guardian users already have a guardian_profile.');
            return self::SUCCESS;
        }

        $this->warn('Guardian users missing a profile:');
        foreach ($missing as $row) {
            $this->line("  user_id={$row->user_id}  email={$row->email}");
        }

        if ($dryRun) {
            $this->line('[dry-run] would create '.count($missing).' guardian_profile row(s).');
            return self::SUCCESS;
        }

        $created = 0;
        foreach ($missing as $row) {
            GuardianProfile::create([
                'user_id' => (int) $row->user_id,
                'contact_number' => null,
            ]);
            $created++;
        }

        $this->info("Created {$created} guardian_profile row(s).");

        return self::SUCCESS;
    }
}
