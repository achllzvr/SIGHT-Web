<?php

namespace Database\Seeders;

use App\Models\AdminProfile;
use App\Models\DoctorProfile;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     * Enforces a single super-admin entity (admin@lumi.com).
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@lumi.com'],
            [
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'password_hash' => Hash::make('password123'),
                'role' => 'admin',
                'phone' => '+234123456789',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        if (method_exists(AdminProfile::class, 'query')) {
            AdminProfile::firstOrCreate(
                ['user_id' => $admin->user_id],
                []
            );
        }

        $doctor = User::firstOrCreate(
            ['email' => 'doctor@lumi.com'],
            [
                'first_name' => 'Dr.',
                'last_name' => 'Martinez',
                'password_hash' => Hash::make('password123'),
                'role' => 'doctor',
                'phone' => '+234987654321',
                'clinic' => 'Martha Eye Center',
                'location' => 'Lagos, NG',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        DoctorProfile::firstOrCreate(
            ['user_id' => $doctor->user_id],
            [
                'phone' => $doctor->phone,
                'clinic' => $doctor->clinic,
                'location' => $doctor->location,
                'is_validated' => true,
            ]
        );

        $doctor2 = User::firstOrCreate(
            ['email' => 'doctor2@lumi.com'],
            [
                'first_name' => 'Dr.',
                'last_name' => 'Smith',
                'password_hash' => Hash::make('password123'),
                'role' => 'doctor',
                'phone' => '+234111222333',
                'clinic' => 'Central Medical',
                'location' => 'Abuja, NG',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        DoctorProfile::firstOrCreate(
            ['user_id' => $doctor2->user_id],
            [
                'phone' => $doctor2->phone,
                'clinic' => $doctor2->clinic,
                'location' => $doctor2->location,
                'is_validated' => true,
            ]
        );

        $this->call(LegalDocumentSeeder::class);
    }
}
