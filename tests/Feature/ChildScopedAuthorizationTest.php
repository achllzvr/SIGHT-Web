<?php

namespace Tests\Feature;

use App\Models\EyeHealthMetrics;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ChildScopedAuthorizationTest extends TestCase
{
    public function test_guardian_cannot_read_another_childs_metrics(): void
    {
        $owner = $this->createGuardianWithChild([], ['email' => 'owner-child@example.com']);
        $intruder = $this->createGuardianWithChild(
            ['email' => 'intruder@example.com'],
            ['email' => 'intruder-child@example.com']
        );

        EyeHealthMetrics::create([
            'child_id' => $owner['childProfile']->child_id,
            'avg_blink_rate' => 14,
            'avg_distance' => 45,
            'strain_events' => 0,
            'screen_time_minutes' => 30,
            'health_score' => 85,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);

        Sanctum::actingAs($intruder['guardianUser']);

        $response = $this->getJson('/api/shared/child/' . $owner['childProfile']->child_id . '/metrics');

        $response->assertForbidden()
            ->assertJson(['message' => 'Unauthorized']);
    }

    public function test_guardian_can_read_own_child_metrics(): void
    {
        $fixture = $this->createGuardianWithChild();

        EyeHealthMetrics::create([
            'child_id' => $fixture['childProfile']->child_id,
            'avg_blink_rate' => 12,
            'avg_distance' => 50,
            'strain_events' => 1,
            'screen_time_minutes' => 20,
            'health_score' => 80,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);

        Sanctum::actingAs($fixture['guardianUser']);

        $response = $this->getJson('/api/shared/child/' . $fixture['childProfile']->child_id . '/metrics');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'pagination' => ['current_page', 'total_pages', 'total_records', 'per_page'],
            ]);
    }
}
