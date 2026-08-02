<?php

namespace Tests\Feature;

use App\Models\EyeHealthMetrics;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MetricsBatchIngestTest extends TestCase
{
    public function test_authenticated_child_can_post_metrics_batch(): void
    {
        $fixture = $this->createGuardianWithChild();
        Sanctum::actingAs($fixture['childUser']);

        $payload = [
            'metrics' => [
                [
                    'avg_blink_rate' => 13.5,
                    'avg_distance' => 42.0,
                    'strain_events' => 1,
                    'screen_time_minutes' => 30,
                    'health_score' => 88,
                    'coins' => 10,
                    'timestamp' => '2026-07-27 10:00:00',
                ],
            ],
        ];

        $response = $this->postJson(
            '/api/mobile/child/' . $fixture['childProfile']->child_id . '/sync/metrics/batch',
            $payload
        );

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.inserted_records', 1);

        $this->assertDatabaseHas('eye_health_metrics', [
            'child_id' => $fixture['childProfile']->child_id,
            'avg_blink_rate' => 13.5,
        ]);
    }

    public function test_authenticated_guardian_can_post_metrics_batch_for_linked_child(): void
    {
        $fixture = $this->createGuardianWithChild();
        Sanctum::actingAs($fixture['guardianUser']);

        $payload = [
            'metrics' => [
                [
                    'avg_blink_rate' => 11.0,
                    'avg_distance' => 38.0,
                    'strain_events' => 0,
                    'screen_time_minutes' => 25,
                    'health_score' => 75,
                    'timestamp' => '2026-07-27 11:00:00',
                ],
            ],
        ];

        $response = $this->postJson(
            '/api/mobile/child/' . $fixture['childProfile']->child_id . '/sync/metrics/batch',
            $payload
        );

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.inserted_records', 1);

        $this->assertSame(1, EyeHealthMetrics::where('child_id', $fixture['childProfile']->child_id)->count());
    }

    public function test_unlinked_guardian_cannot_post_metrics_batch(): void
    {
        $owner = $this->createGuardianWithChild([], ['email' => 'batch-owner@example.com']);
        $intruder = $this->createGuardianWithChild(
            ['email' => 'batch-intruder@example.com'],
            ['email' => 'batch-intruder-child@example.com']
        );

        Sanctum::actingAs($intruder['guardianUser']);

        $response = $this->postJson(
            '/api/mobile/child/' . $owner['childProfile']->child_id . '/sync/metrics/batch',
            [
                'metrics' => [
                    [
                        'timestamp' => '2026-07-27 12:00:00',
                        'screen_time_minutes' => 10,
                    ],
                ],
            ]
        );

        $response->assertForbidden()
            ->assertJsonPath('status', 'error');
    }
}
