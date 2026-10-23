<?php

namespace Tests\Feature;

use Tests\TestCase;

class PerDeviceTokenTest extends TestCase
{
    public function test_two_guardian_devices_keep_independent_tokens(): void
    {
        $fixture = $this->createGuardianWithChild();
        $email = $fixture['guardianUser']->email;

        $first = $this->postJson('/api/shared/login', [
            'email' => $email,
            'password' => 'password123',
            'device_id' => 'android-device-one',
        ]);
        $first->assertOk();
        $tokenOne = $first->json('token');
        $this->assertNotEmpty($tokenOne);

        $second = $this->postJson('/api/shared/login', [
            'email' => $email,
            'password' => 'password123',
            'device_id' => 'android-device-two',
        ]);
        $second->assertOk();
        $tokenTwo = $second->json('token');
        $this->assertNotEmpty($tokenTwo);
        $this->assertNotSame($tokenOne, $tokenTwo);

        // First device token must still work after second device login.
        $me = $this->withHeader('Authorization', 'Bearer ' . $tokenOne)
            ->getJson('/api/shared/child/' . $fixture['childProfile']->child_id . '/presence');
        $me->assertOk();

        $this->assertSame(
            2,
            $fixture['guardianUser']->tokens()->where('name', 'like', 'guardian-mobile:%')->count()
        );
    }

    public function test_two_child_devices_keep_independent_tokens(): void
    {
        $fixture = $this->createGuardianWithChild();
        $code = $fixture['childProfile']->login_code;

        $first = $this->postJson('/api/mobile/child/login', [
            'login_code' => $code,
            'password' => 'password123',
            'device_id' => 'phone-one',
        ]);
        $first->assertOk();
        $tokenOne = $first->json('data.access_token');

        $second = $this->postJson('/api/mobile/child/login', [
            'login_code' => $code,
            'password' => 'password123',
            'device_id' => 'phone-two',
        ]);
        $second->assertOk();
        $tokenTwo = $second->json('data.access_token');

        $this->assertNotEmpty($tokenOne);
        $this->assertNotSame($tokenOne, $tokenTwo);
        $this->assertSame(
            2,
            $fixture['childUser']->tokens()->where('name', 'like', 'child-mobile:%')->count()
        );
    }
}
