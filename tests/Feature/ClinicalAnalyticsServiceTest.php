<?php

namespace Tests\Feature;

use App\Models\ChildProfile;
use App\Models\EyeHealthMetrics;
use App\Services\ClinicalAnalyticsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ClinicalAnalyticsServiceTest extends TestCase
{
    private ClinicalAnalyticsService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        if (!Schema::hasTable('session_limits')) {
            Schema::create('session_limits', function (Blueprint $table) {
                $table->integer('limit_id', true);
                $table->integer('child_id')->nullable();
                $table->integer('daily_limit_minutes')->nullable();
                $table->string('mode')->nullable();
                $table->boolean('is_active')->default(true);
                $table->float('harmful_distance_threshold')->nullable();
                $table->float('critical_distance_threshold')->nullable();
                $table->boolean('auto_enforce_breaks')->default(false);
                $table->timestamps();
            });
        }
        $this->svc = app(ClinicalAnalyticsService::class);
    }

    private function makeChild(string $birthdate = '2018-01-15'): ChildProfile
    {
        $fixture = $this->createGuardianWithChild();
        $child = $fixture['childProfile'];
        $child->update(['birthdate' => $birthdate]);
        return $child->fresh();
    }

    private function seedMetric(int $childId, array $overrides = []): void
    {
        EyeHealthMetrics::create(array_merge([
            'child_id' => $childId,
            'avg_blink_rate' => 14.0,
            'avg_distance' => 40.0,
            'strain_events' => 0,
            'screen_time_minutes' => 30,
            'health_score' => 90,
            'coins' => 0,
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ], $overrides));
    }

    public function test_resolve_date_range_all_ranges_and_invalid_fallback(): void
    {
        foreach (['7d', '30d', '90d', '180d', '365d', 'lifetime'] as $range) {
            $r = $this->svc->resolveDateRange($range);
            $this->assertArrayHasKey('start', $r, $range);
            $this->assertArrayHasKey('label', $r, $range);
            $this->assertArrayHasKey('bucket', $r, $range);
        }
        $this->assertSame('day', $this->svc->resolveDateRange('7d')['bucket']);
        $this->assertSame('day', $this->svc->resolveDateRange('30d')['bucket']);
        $this->assertSame('week', $this->svc->resolveDateRange('90d')['bucket']);
        $this->assertSame('week', $this->svc->resolveDateRange('180d')['bucket']);
        $this->assertSame('month', $this->svc->resolveDateRange('365d')['bucket']);
        $this->assertSame('month', $this->svc->resolveDateRange('lifetime')['bucket']);
        // Invalid falls back to 7d label
        $this->assertSame('Last 7 Days', $this->svc->resolveDateRange('bogus')['label']);
    }

    public function test_grade_from_score_bands_and_null(): void
    {
        $this->assertSame('Excellent', $this->svc->gradeFromScore(90));
        $this->assertSame('Excellent', $this->svc->gradeFromScore(85));
        $this->assertSame('Good', $this->svc->gradeFromScore(70));
        $this->assertSame('Fair', $this->svc->gradeFromScore(50));
        $this->assertSame('Needs Attention', $this->svc->gradeFromScore(49));
        $this->assertSame('No Data', $this->svc->gradeFromScore(null));
    }

    public function test_opi_levels_one_to_four(): void
    {
        $l1 = $this->svc->calculateOcularProtectionIndex(15);
        $this->assertSame(1, $l1['level']);
        $this->assertNotNull($l1['opi']);

        $l2 = $this->svc->calculateOcularProtectionIndex(11);
        $this->assertSame(2, $l2['level']);

        $l3 = $this->svc->calculateOcularProtectionIndex(8);
        $this->assertSame(3, $l3['level']);
        $this->assertLessThan(1.0, $l3['opi']);

        $l4 = $this->svc->calculateOcularProtectionIndex(4);
        $this->assertSame(4, $l4['level']);

        $zero = $this->svc->calculateOcularProtectionIndex(0);
        $this->assertSame(4, $zero['level']);
        $this->assertNull($zero['opi']);
    }

    public function test_interpret_usage_threshold_five_tiers(): void
    {
        $this->assertSame(1, $this->svc->interpretUsageThreshold(30, 120)['tier']);   // 25%
        $this->assertSame(2, $this->svc->interpretUsageThreshold(90, 120)['tier']);   // 75%
        $this->assertSame(3, $this->svc->interpretUsageThreshold(135, 120)['tier']);  // 112.5%
        $this->assertSame(4, $this->svc->interpretUsageThreshold(165, 120)['tier']);  // 137.5%
        $this->assertSame(5, $this->svc->interpretUsageThreshold(200, 120)['tier']);  // 166%
    }

    public function test_age_max_tiers(): void
    {
        $this->assertSame(60, $this->svc->ageMaxMinutes(4));
        $this->assertSame(90, $this->svc->ageMaxMinutes(7));
        $this->assertSame(120, $this->svc->ageMaxMinutes(10));
        $this->assertSame(120, $this->svc->ageMaxMinutes(null));
        $this->assertSame(60, $this->svc->clampDailyLimit(120, 60));
        $this->assertSame(15, $this->svc->clampDailyLimit(5, 120));
    }

    public function test_empty_range_is_no_data_not_critical(): void
    {
        $child = $this->makeChild();
        $dash = $this->svc->getChildDashboardMetrics($child, '7d');
        $this->assertFalse($dash['has_data']);
        $this->assertSame('No Data', $dash['health_grade']);
        $this->assertSame('NO_DATA', $dash['clinical_trend_alert']['overall_condition']);
        $this->assertFalse($dash['clinical_trend_alert']['show_professional_cta']);
        $this->assertNull($dash['mean_blink_rate']);
        $this->assertNull($dash['ocular_protection_index']['opi']);
    }

    public function test_null_blink_and_distance_rows_are_not_critical(): void
    {
        $child = $this->makeChild();
        // Row exists but blink/distance samples are missing (null, not 0.0)
        $this->seedMetric($child->child_id, [
            'avg_blink_rate' => null,
            'avg_distance' => null,
            'health_score' => null,
        ]);
        $dash = $this->svc->getChildDashboardMetrics($child, '7d');
        $this->assertTrue($dash['has_data']);
        $this->assertNotSame('CRITICAL', $dash['clinical_trend_alert']['dry_eye_status']);
        $this->assertNotSame('CRITICAL', $dash['clinical_trend_alert']['myopia_status']);
    }

    public function test_dashboard_means_violations_and_series(): void
    {
        $child = $this->makeChild();
        $this->seedMetric($child->child_id, [
            'avg_blink_rate' => 8.0,   // low blink day
            'avg_distance' => 25.0,    // violation day
            'screen_time_minutes' => 30,
            'health_score' => 90,
            'strain_events' => 3,
        ]);
        $dash = $this->svc->getChildDashboardMetrics($child, '7d');
        $this->assertTrue($dash['has_data']);
        $this->assertSame(25.0, $dash['mean_distance_cm']);
        $this->assertSame(8.0, $dash['mean_blink_rate']);
        $this->assertSame(4.0, $dash['mean_diopters']); // 100/25
        $this->assertSame(1, $dash['distance_violations']);
        $this->assertSame(1, $dash['low_blink_events']);
        $this->assertCount(7, $dash['series']['labels']);
        $this->assertCount(7, $dash['series']['strain_events']);
        $this->assertCount(7, $dash['series']['health_scores']);
    }

    public function test_evaluate_referral_risk_empty_daily_direct(): void
    {
        $child = $this->makeChild();
        $alert = $this->svc->evaluateLongitudinalReferralRisk($child, '7d', collect(), null, null, null, 120);
        $this->assertSame('NO_DATA', $alert['overall_condition']);
    }
}
