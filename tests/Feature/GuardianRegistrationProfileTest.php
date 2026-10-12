<?php

namespace Tests\Feature;

use App\Models\GuardianProfile;
use App\Models\User;
use App\Services\RuleEngineService;
use Illuminate\Support\Facades\Hash;
use Tests\Support\SightTestSchema;
use Tests\TestCase;

class GuardianRegistrationProfileTest extends TestCase
{
    use SightTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpSightSchema();
    }

    public function test_register_guardian_persists_user_id_on_profile(): void
    {
        $service = app(RuleEngineService::class);

        $result = $service->registerGuardian([
            'first_name' => 'Prinz',
            'last_name' => 'Tester',
            'email' => 'prinz-new@example.com',
            'password' => 'password123',
        ]);

        $this->assertSame('success', $result['body']['status']);
        $userId = (int) $result['body']['data']['user']['id'];
        $this->assertGreaterThan(0, $userId);

        $user = User::find($userId);
        $this->assertNotNull($user);
        $this->assertSame('prinz-new@example.com', $user->email);

        $profile = GuardianProfile::where('user_id', $userId)->first();
        $this->assertNotNull($profile, 'guardian_profile must be linked to the new user_id');
        $this->assertSame($userId, (int) $profile->user_id);
    }

    public function test_user_create_without_explicit_id_still_hydrates_primary_key(): void
    {
        $user = User::create([
            'first_name' => 'No',
            'last_name' => 'Id',
            'email' => 'noid@example.com',
            'password_hash' => Hash::make('secret'),
            'role' => 'Guardian',
        ]);

        $this->assertNotEmpty($user->user_id);
        $this->assertGreaterThan(0, (int) $user->user_id);
    }
}
