<?php

namespace Database\Seeders;

use App\Models\AdminProfile;
use App\Models\DoctorProfile;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Local `user` / profile tables often lack AUTO_INCREMENT (imported dump),
     * so IDs are assigned explicitly. Role enum values are capitalized.
     * phone/clinic/location belong on doctor_profile, not user.
     */
    public function run(): void
    {
        $admin = $this->upsertUser(
            email: 'admin@lumi.com',
            attributes: [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'password_hash' => Hash::make('password123'),
                'role' => 'Admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $this->upsertAdminProfile($admin->user_id);

        $doctor = $this->upsertUser(
            email: 'doctor@lumi.com',
            attributes: [
                'first_name' => 'Dr.',
                'last_name' => 'Martinez',
                'password_hash' => Hash::make('password123'),
                'role' => 'Doctor',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $this->upsertDoctorProfile($doctor->user_id, [
            'phone' => '+234987654321',
            'clinic' => 'Martha Eye Center',
            'location' => 'Lagos, NG',
            'is_validated' => true,
        ]);

        $doctor2 = $this->upsertUser(
            email: 'doctor2@lumi.com',
            attributes: [
                'first_name' => 'Dr.',
                'last_name' => 'Smith',
                'password_hash' => Hash::make('password123'),
                'role' => 'Doctor',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $this->upsertDoctorProfile($doctor2->user_id, [
            'phone' => '+234111222333',
            'clinic' => 'Central Medical',
            'location' => 'Abuja, NG',
            'is_validated' => true,
        ]);

        $this->call(LegalDocumentSeeder::class);
    }

    private function upsertUser(string $email, array $attributes): User
    {
        $existing = User::where('email', $email)->first();
        if ($existing) {
            $existing->fill($attributes);
            $existing->save();

            return $existing;
        }

        $nextId = ((int) DB::table('user')->max('user_id')) + 1;

        return User::create(array_merge($attributes, [
            'email' => $email,
            'user_id' => $nextId,
        ]));
    }

    private function upsertAdminProfile(int $userId): void
    {
        $existing = AdminProfile::where('user_id', $userId)->first();
        if ($existing) {
            $existing->role_level = 'Admin';
            $existing->save();

            return;
        }

        $nextId = ((int) DB::table('admin_profile')->max('admin_id')) + 1;
        AdminProfile::create([
            'admin_id' => $nextId,
            'user_id' => $userId,
            'role_level' => 'Admin',
        ]);
    }

    private function upsertDoctorProfile(int $userId, array $attributes): void
    {
        $existing = DoctorProfile::where('user_id', $userId)->first();
        if ($existing) {
            $existing->fill($attributes);
            $existing->save();

            return;
        }

        $nextId = ((int) DB::table('doctor_profile')->max('doctor_id')) + 1;
        DoctorProfile::create(array_merge($attributes, [
            'doctor_id' => $nextId,
            'user_id' => $userId,
        ]));
    }
}
