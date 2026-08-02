<?php

namespace Tests\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\GuardianProfile;
use App\Models\ChildProfile;

trait SightTestSchema
{
    protected function setUpSightSchema(): void
    {
        $this->migrateSightTestSchema();
    }

    protected function migrateSightTestSchema(): void
    {
        Schema::dropAllTables();

        Schema::create('user', function (Blueprint $table) {
            $table->integer('user_id', true);
            $table->string('email')->unique();
            $table->string('last_name')->default('');
            $table->string('first_name')->default('');
            $table->string('password_hash');
            $table->string('role');
            $table->dateTime('created_at')->nullable();
            $table->string('images')->nullable();
            $table->integer('failed_login_attempts')->default(0);
            $table->dateTime('locked_until')->nullable();
            $table->string('status')->default('active');
            $table->boolean('must_change_password')->default(false);
            $table->dateTime('email_verified_at')->nullable();
        });

        Schema::create('guardian_profile', function (Blueprint $table) {
            $table->integer('guardian_id', true);
            $table->integer('user_id')->nullable();
            $table->string('contact_number', 11)->nullable();
        });

        Schema::create('child_profile', function (Blueprint $table) {
            $table->integer('child_id', true);
            $table->integer('user_id')->nullable();
            $table->date('birthdate')->nullable();
            $table->string('device_id')->nullable();
            $table->dateTime('last_sync')->nullable();
            $table->string('login_code', 50)->nullable();
            $table->text('calibration_baseline')->nullable();
            $table->string('fcm_token')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('guardian_child_link', function (Blueprint $table) {
            $table->integer('guardian_id');
            $table->integer('child_id');
            $table->primary(['guardian_id', 'child_id']);
        });

        Schema::create('eye_health_metrics', function (Blueprint $table) {
            $table->integer('metric_id', true);
            $table->integer('child_id')->nullable();
            $table->float('avg_blink_rate')->nullable();
            $table->float('avg_distance')->nullable();
            $table->integer('strain_events')->nullable();
            $table->dateTime('timestamp')->nullable();
            $table->integer('screen_time_minutes')->default(0);
            $table->integer('health_score')->nullable();
            $table->integer('coins')->nullable();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->text('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('email_verification_otps', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamps();
        });
    }

    protected function createGuardianWithChild(array $guardianOverrides = [], array $childOverrides = []): array
    {
        $guardianUser = User::create(array_merge([
            'email' => 'guardian-' . uniqid() . '@example.com',
            'first_name' => 'Test',
            'last_name' => 'Guardian',
            'password_hash' => Hash::make('password123'),
            'role' => 'guardian',
            'status' => 'active',
            'email_verified_at' => now(),
        ], $guardianOverrides));

        $guardianProfile = GuardianProfile::create([
            'user_id' => $guardianUser->user_id,
            'contact_number' => '09123456789',
        ]);

        $childUser = User::create(array_merge([
            'email' => 'child-' . uniqid() . '@example.com',
            'first_name' => 'Test',
            'last_name' => 'Child',
            'password_hash' => Hash::make('password123'),
            'role' => 'child',
            'status' => 'active',
        ], $childOverrides));

        $childProfile = ChildProfile::create([
            'user_id' => $childUser->user_id,
            'birthdate' => '2016-01-15',
            'login_code' => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
        ]);

        DB::table('guardian_child_link')->insert([
            'guardian_id' => $guardianProfile->guardian_id,
            'child_id' => $childProfile->child_id,
        ]);

        return compact('guardianUser', 'guardianProfile', 'childUser', 'childProfile');
    }

    protected function createUnverifiedGuardian(string $email = 'pending@example.com'): User
    {
        return User::create([
            'email' => $email,
            'first_name' => 'Pending',
            'last_name' => 'Guardian',
            'password_hash' => Hash::make('password123'),
            'role' => 'guardian',
            'status' => 'active',
            'email_verified_at' => null,
        ]);
    }
}
