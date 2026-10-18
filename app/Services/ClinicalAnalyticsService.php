<?php

namespace App\Services;

use App\Models\ChildProfile;
use App\Models\EyeHealthMetrics;
use App\Models\SessionLimits;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Multi-horizon analytics: age-based usage tiers, OPI tear-film levels,
 * and Part-4 dual-track clinical trend alerts (SAFE…CRITICAL).
 */
class ClinicalAnalyticsService
{
    public const RANGES = ['7d', '30d', '90d', '180d', '365d', 'lifetime'];

    public const GRADE_EXCELLENT = 'Excellent';
    public const GRADE_GOOD = 'Good';
    public const GRADE_FAIR = 'Fair';
    public const GRADE_NEEDS_ATTENTION = 'Needs Attention';

    public const ALERT_SAFE = 'SAFE';
    public const ALERT_CAUTION = 'CAUTION';
    public const ALERT_AT_RISK = 'AT_RISK';
    public const ALERT_CRITICAL = 'CRITICAL';

    private const TBUT_SECONDS = 6.0;
    private const SEVERITY = [
        self::ALERT_SAFE => 0,
        self::ALERT_CAUTION => 1,
        self::ALERT_AT_RISK => 2,
        self::ALERT_CRITICAL => 3,
    ];

    /**
     * @return array{start: Carbon, end: Carbon, label: string, bucket: string}
     */
    public function resolveDateRange(string $range = '7d'): array
    {
        $range = in_array($range, self::RANGES, true) ? $range : '7d';
        $end = now()->endOfDay();

        return match ($range) {
            '30d' => [
                'start' => now()->subDays(29)->startOfDay(),
                'end' => $end,
                'label' => 'Last Month (30 Days)',
                'bucket' => 'day',
            ],
            '90d' => [
                'start' => now()->subDays(89)->startOfDay(),
                'end' => $end,
                'label' => 'Last 3 Months',
                'bucket' => 'week',
            ],
            '180d' => [
                'start' => now()->subDays(179)->startOfDay(),
                'end' => $end,
                'label' => 'Last 6 Months',
                'bucket' => 'week',
            ],
            '365d' => [
                'start' => now()->subDays(364)->startOfDay(),
                'end' => $end,
                'label' => 'Last Year',
                'bucket' => 'month',
            ],
            'lifetime' => [
                'start' => Carbon::parse('2000-01-01')->startOfDay(),
                'end' => $end,
                'label' => 'Lifetime',
                'bucket' => 'month',
            ],
            default => [
                'start' => now()->subDays(6)->startOfDay(),
                'end' => $end,
                'label' => 'Last 7 Days',
                'bucket' => 'day',
            ],
        };
    }

    public function chronologicalAgeYears(?string $birthdate): ?int
    {
        if ($birthdate === null || $birthdate === '') {
            return null;
        }

        try {
            return Carbon::parse($birthdate)->age;
        } catch (\Throwable) {
            return null;
        }
    }

    /** Ages 3–5 → 60, 6–8 → 90, 9–12+ → 120. */
    public function ageMaxMinutes(?int $ageYears): int
    {
        if ($ageYears === null) {
            return 120;
        }
        if ($ageYears <= 5) {
            return 60;
        }
        if ($ageYears <= 8) {
            return 90;
        }

        return 120;
    }

    public function ageMaxForChild(ChildProfile $child): int
    {
        return $this->ageMaxMinutes($this->chronologicalAgeYears($child->birthdate ? (string) $child->birthdate : null));
    }

    public function clampDailyLimit(int $requestedMinutes, int $ageMax): int
    {
        return max(15, min($requestedMinutes, $ageMax));
    }

    /**
     * 5-tier usage threshold interpretation (% of age max).
     *
     * @return array{tier: int, key: string, label: string, badge: string, percent: float}
     */
    public function interpretUsageThreshold(float $actualMinutes, int $ageMaxMinutes): array
    {
        $ageMaxMinutes = max(1, $ageMaxMinutes);
        $percent = ($actualMinutes / $ageMaxMinutes) * 100.0;

        // Parent-friendly badges/labels; doctors primarily consume tier/key/badge enums.
        if ($percent <= 50) {
            return [
                'tier' => 1,
                'key' => 'optimal',
                'label' => 'Healthy amount of screen time for this age',
                'badge' => 'Healthy amount',
                'percent' => round($percent, 1),
            ];
        }
        if ($percent <= 100) {
            return [
                'tier' => 2,
                'key' => 'allowable',
                'label' => "Within today's screen-time limit",
                'badge' => "Within today's limit",
                'percent' => round($percent, 1),
            ];
        }
        if ($percent <= 125) {
            return [
                'tier' => 3,
                'key' => 'mild_over',
                'label' => 'A little past the daily limit — eyes may feel tired',
                'badge' => 'A little over the limit',
                'percent' => round($percent, 1),
            ];
        }
        if ($percent <= 150) {
            return [
                'tier' => 4,
                'key' => 'high_over',
                'label' => 'Well past the daily limit — take longer breaks',
                'badge' => 'Well over the limit',
                'percent' => round($percent, 1),
            ];
        }

        return [
            'tier' => 5,
            'key' => 'critical_over',
            'label' => 'Far past the daily limit — please check with an eye doctor',
            'badge' => 'Far over the limit — doctor visit advised',
            'percent' => round($percent, 1),
        ];
    }

    /**
     * Ousler OPI = TBUT / IBI with TBUT ≈ 6s, IBI = 60/B.
     *
     * @return array{blink_rate: float, ibi_seconds: float|null, opi: float|null, level: int, label: string}
     */
    public function calculateOcularProtectionIndex(float $blinkRate): array
    {
        if ($blinkRate <= 0) {
            return [
                'blink_rate' => $blinkRate,
                'ibi_seconds' => null,
                'opi' => null,
                'level' => 4,
                'label' => 'Eyes need a break — try the blink game',
            ];
        }

        $ibi = 60.0 / $blinkRate;
        $opi = self::TBUT_SECONDS / $ibi;

        if ($blinkRate >= 12 && $opi >= 1.20) {
            $level = 1;
            $label = 'Eyes look comfortable';
        } elseif ($blinkRate >= 10 && $opi >= 1.00) {
            $level = 2;
            $label = 'Blinking a bit less than ideal';
        } elseif ($blinkRate >= 6 && $opi >= 0.60) {
            $level = 3;
            $label = 'Eyes may feel dry — more blinks help';
        } else {
            $level = 4;
            $label = 'Eyes need a break — try the blink game';
        }

        return [
            'blink_rate' => round($blinkRate, 2),
            'ibi_seconds' => round($ibi, 2),
            'opi' => round($opi, 2),
            'level' => $level,
            'label' => $label,
        ];
    }

    public function gradeFromScore(?float $score): string
    {
        if ($score === null) {
            return self::GRADE_NEEDS_ATTENTION;
        }
        if ($score >= 85) {
            return self::GRADE_EXCELLENT;
        }
        if ($score >= 70) {
            return self::GRADE_GOOD;
        }
        if ($score >= 50) {
            return self::GRADE_FAIR;
        }

        return self::GRADE_NEEDS_ATTENTION;
    }

    /**
     * Full dashboard payload for a child + range.
     */
    public function getChildDashboardMetrics(ChildProfile $child, string $range = '7d'): array
    {
        $resolved = $this->resolveDateRange($range);
        $start = $resolved['start'];
        $end = $resolved['end'];

        // Lifetime: use earliest metric if available.
        if ($range === 'lifetime') {
            $earliest = EyeHealthMetrics::where('child_id', $child->child_id)->min('timestamp');
            if ($earliest) {
                $start = Carbon::parse($earliest)->startOfDay();
            } elseif ($child->birthdate) {
                $start = Carbon::parse((string) $child->birthdate)->startOfDay();
            }
        }

        $rows = EyeHealthMetrics::where('child_id', $child->child_id)
            ->whereBetween('timestamp', [$start, $end])
            ->orderBy('timestamp')
            ->get();

        $daily = $this->aggregateByDay($rows);
        $series = $this->bucketSeries($daily, $resolved['bucket'], $start, $end);

        $meanDistance = $this->mean($daily->pluck('avg_distance')->filter(fn ($v) => $v !== null)->all());
        $meanBlink = $this->mean($daily->pluck('avg_blink_rate')->filter(fn ($v) => $v !== null)->all());
        $meanScreen = $this->mean($daily->pluck('screen_time_minutes')->all());
        $meanHealth = $this->mean($daily->pluck('avg_health_score')->filter(fn ($v) => $v !== null)->all());

        $ageMax = $this->ageMaxForChild($child);
        $ageYears = $this->chronologicalAgeYears($child->birthdate ? (string) $child->birthdate : null);
        $usage = $this->interpretUsageThreshold($meanScreen ?? 0, $ageMax);
        $opi = $this->calculateOcularProtectionIndex((float) ($meanBlink ?? 0));

        $alert = $this->evaluateLongitudinalReferralRisk($child, $range, $daily, $meanDistance, $meanBlink, $meanScreen, $ageMax);

        $grade = $this->gradeFromScore($meanHealth);
        if ($alert['overall_condition'] === self::ALERT_CRITICAL || $usage['tier'] >= 5) {
            $grade = self::GRADE_NEEDS_ATTENTION;
        }

        $limits = SessionLimits::where('child_id', $child->child_id)->first();

        return [
            'range' => $range,
            'range_label' => $resolved['label'],
            'child_id' => $child->child_id,
            'age_years' => $ageYears,
            'age_max_minutes' => $ageMax,
            'configured_daily_limit_minutes' => $limits?->daily_limit_minutes,
            'has_data' => $daily->isNotEmpty(),
            'health_score' => $meanHealth !== null ? round($meanHealth, 1) : null,
            'health_grade' => $grade,
            'mean_distance_cm' => $meanDistance !== null ? round($meanDistance, 1) : null,
            'mean_diopters' => $meanDistance && $meanDistance > 0 ? round(100.0 / $meanDistance, 2) : null,
            'mean_blink_rate' => $meanBlink !== null ? round($meanBlink, 1) : null,
            'mean_screen_time_minutes' => $meanScreen !== null ? round($meanScreen, 1) : null,
            'ocular_protection_index' => $opi,
            'usage_threshold' => $usage,
            'clinical_trend_alert' => $alert,
            'series' => $series,
            'unlock_milestones' => $this->unlockMilestones($ageYears),
        ];
    }

    /**
     * Part-4 dual-track evaluator.
     *
     * @param  Collection<int, object>  $daily
     * @return array<string, mixed>
     */
    public function evaluateLongitudinalReferralRisk(
        ChildProfile $child,
        string $range = '7d',
        ?Collection $daily = null,
        ?float $meanDistance = null,
        ?float $meanBlink = null,
        ?float $meanScreen = null,
        ?int $ageMax = null,
    ): array {
        if ($daily === null) {
            $resolved = $this->resolveDateRange($range);
            $rows = EyeHealthMetrics::where('child_id', $child->child_id)
                ->whereBetween('timestamp', [$resolved['start'], $resolved['end']])
                ->orderBy('timestamp')
                ->get();
            $daily = $this->aggregateByDay($rows);
            $meanDistance = $this->mean($daily->pluck('avg_distance')->filter(fn ($v) => $v !== null)->all());
            $meanBlink = $this->mean($daily->pluck('avg_blink_rate')->filter(fn ($v) => $v !== null)->all());
            $meanScreen = $this->mean($daily->pluck('screen_time_minutes')->all()) ?? 0.0;
        }

        $ageMax = $ageMax ?? $this->ageMaxForChild($child);
        $d = $meanDistance;
        $b = $meanBlink;
        $t = $meanScreen ?? 0.0;

        $dayCount = max(1, $daily->count());
        $sessionsBelow30 = 0;
        $sessionTotal = 0;
        $daysViolate = 0;

        foreach ($daily as $day) {
            $dayDist = $day->avg_distance;
            $dayTime = (float) ($day->screen_time_minutes ?? 0);
            $sessionTotal++;
            if ($dayDist !== null && (float) $dayDist < 30) {
                $sessionsBelow30++;
            }
            if (($dayDist !== null && (float) $dayDist < 30) || $dayTime > $ageMax) {
                $daysViolate++;
            }
        }

        $pctSessionsBelow30 = $sessionTotal > 0 ? ($sessionsBelow30 / $sessionTotal) * 100.0 : 0.0;
        $pctDaysViolate = ($daysViolate / $dayCount) * 100.0;
        $tPct = ($t / max(1, $ageMax)) * 100.0;

        $myopia = $this->classifyMyopia($d, $tPct, $pctSessionsBelow30, $pctDaysViolate);
        $opiLive = $this->calculateOcularProtectionIndex((float) ($b ?? 0));
        $dryEye = $this->classifyDryEye($b, $opiLive['opi']);

        $overall = $this->maxAlert($myopia, $dryEye);
        $copy = $this->alertCopy($overall, $d, $b, $t, $opiLive, $ageMax);

        return [
            'myopia_status' => $myopia,
            'dry_eye_status' => $dryEye,
            'overall_condition' => $overall,
            'alert_title' => $copy['title'],
            'what_is_wrong_text' => $copy['what'],
            'why_showing_clinical_basis_text' => $copy['why'],
            'show_professional_cta' => in_array($overall, [self::ALERT_CAUTION, self::ALERT_AT_RISK, self::ALERT_CRITICAL], true),
            'metrics_snapshot' => [
                'mean_distance_cm' => $d !== null ? round($d, 1) : null,
                'mean_blink_rate' => $b !== null ? round($b, 1) : null,
                'mean_screen_time_minutes' => round($t, 1),
                'opi' => $opiLive['opi'],
                'pct_sessions_below_30cm' => round($pctSessionsBelow30, 1),
                'pct_days_violating' => round($pctDaysViolate, 1),
                'age_max_minutes' => $ageMax,
            ],
        ];
    }

    private function classifyMyopia(?float $d, float $tPct, float $pctSessionsBelow30, float $pctDaysViolate): string
    {
        if ($d !== null && $d < 20) {
            return self::ALERT_CRITICAL;
        }
        if ($tPct > 125 || $pctDaysViolate > 50) {
            return self::ALERT_CRITICAL;
        }
        if ($d !== null && $d < 30) {
            return self::ALERT_AT_RISK;
        }
        if ($tPct > 100 || ($pctSessionsBelow30 > 30 && $pctSessionsBelow30 <= 50)) {
            return self::ALERT_AT_RISK;
        }
        if ($d !== null && $d < 35) {
            return self::ALERT_CAUTION;
        }
        if ($tPct > 75 || $pctSessionsBelow30 >= 15) {
            return self::ALERT_CAUTION;
        }
        if ($d === null) {
            return self::ALERT_CAUTION;
        }

        return self::ALERT_SAFE;
    }

    private function classifyDryEye(?float $b, ?float $opi): string
    {
        if ($b === null || $b <= 0) {
            return self::ALERT_CRITICAL;
        }
        if ($b < 6 || ($opi !== null && $opi < 0.60)) {
            return self::ALERT_CRITICAL;
        }
        if ($b < 10 || ($opi !== null && $opi < 1.00)) {
            return self::ALERT_AT_RISK;
        }
        if ($b < 12 || ($opi !== null && $opi < 1.20)) {
            return self::ALERT_CAUTION;
        }

        return self::ALERT_SAFE;
    }

    private function maxAlert(string $a, string $b): string
    {
        return (self::SEVERITY[$a] ?? 0) >= (self::SEVERITY[$b] ?? 0) ? $a : $b;
    }

    /**
     * @param  array<string, mixed>  $opiLive
     * @return array{title: string, what: string, why: string}
     */
    private function alertCopy(string $overall, ?float $d, ?float $b, float $t, array $opiLive, int $ageMax): array
    {
        $dStr = $d !== null ? number_format($d, 1) : 'n/a';
        $bStr = $b !== null ? number_format($b, 1) : 'n/a';
        $tStr = number_format($t, 0);
        $opiStr = $opiLive['opi'] !== null ? number_format((float) $opiLive['opi'], 2) : 'n/a';

        // Parent/guardian-facing prose (doctors still receive overall_condition enums).
        return match ($overall) {
            self::ALERT_SAFE => [
                'title' => 'Looking good — healthy screen habits',
                'what' => "Average viewing distance is about {$dStr} cm, blinking is about {$bStr} times per minute, and screen time is within the daily limit ({$tStr} of {$ageMax} minutes).",
                'why' => 'Keeping the phone a bit farther than a forearm’s length and blinking regularly helps eyes stay comfortable during screen time.',
            ],
            self::ALERT_CAUTION => [
                'title' => 'Worth watching — early tired-eye signals',
                'what' => "Distance ({$dStr} cm), blinking ({$bStr}/min, comfort score {$opiStr}), or screen time ({$tStr} minutes) is getting close to a limit for this age.",
                'why' => 'Sitting too close or blinking less often can make eyes feel tired or dry. A short break and more blinks usually help.',
            ],
            self::ALERT_AT_RISK => [
                'title' => 'Needs attention — closer viewing and dry-eye habits',
                'what' => "Recent habits show closer viewing ({$dStr} cm), fewer blinks ({$bStr}/min, comfort score {$opiStr}), and {$tStr} minutes of screen time versus a {$ageMax}-minute daily guide.",
                'why' => 'Holding the phone too close and staying past the daily limit often go with tired or dry eyes. Consider shorter sessions and an eye-doctor check if this continues.',
            ],
            default => [
                'title' => 'Talk to a doctor — strong eye-strain signals',
                'what' => "Strong strain signals: distance {$dStr} cm, blinks {$bStr}/min (comfort score {$opiStr}), screen time {$tStr} minutes (daily guide {$ageMax}).",
                'why' => 'Very close viewing and very few blinks can leave eyes dry and strained. Please check with an eye doctor from the clinic list.',
            ],
        };
    }

    /**
     * @return Collection<string, object>
     */
    private function aggregateByDay(Collection $rows): Collection
    {
        return $rows->groupBy(fn ($r) => Carbon::parse($r->timestamp)->toDateString())
            ->map(function (Collection $group, string $date) {
                $dists = $group->pluck('avg_distance')->filter(fn ($v) => $v !== null);
                $blinks = $group->pluck('avg_blink_rate')->filter(fn ($v) => $v !== null);
                $health = $group->pluck('health_score')->filter(fn ($v) => $v !== null);

                return (object) [
                    'metric_date' => $date,
                    'avg_distance' => $dists->isEmpty() ? null : $dists->avg(),
                    'avg_blink_rate' => $blinks->isEmpty() ? null : $blinks->avg(),
                    'screen_time_minutes' => $group->sum('screen_time_minutes'),
                    'strain_events' => $group->sum('strain_events'),
                    'avg_health_score' => $health->isEmpty() ? null : $health->avg(),
                ];
            })
            ->sortKeys();
    }

    /**
     * @param  Collection<string, object>  $daily
     * @return array{labels: list<string>, distances: list<?float>, blink_rates: list<?float>, screen_times: list<float>}
     */
    private function bucketSeries(Collection $daily, string $bucket, Carbon $start, Carbon $end): array
    {
        if ($bucket === 'day') {
            $labels = [];
            $distances = [];
            $blinks = [];
            $screens = [];
            $cursor = $start->copy();
            while ($cursor->lte($end)) {
                $key = $cursor->toDateString();
                $row = $daily->get($key);
                $labels[] = $cursor->format('M j');
                $distances[] = $row && $row->avg_distance !== null ? round((float) $row->avg_distance, 1) : null;
                $blinks[] = $row && $row->avg_blink_rate !== null ? round((float) $row->avg_blink_rate, 1) : null;
                $screens[] = $row ? round((float) $row->screen_time_minutes, 1) : 0.0;
                $cursor->addDay();
            }

            return compact('labels') + [
                'distances' => $distances,
                'blink_rates' => $blinks,
                'screen_times' => $screens,
            ];
        }

        $groups = $daily->groupBy(function ($row) use ($bucket) {
            $c = Carbon::parse($row->metric_date);

            return $bucket === 'week' ? $c->format('o-\WW') : $c->format('Y-m');
        });

        $labels = [];
        $distances = [];
        $blinks = [];
        $screens = [];
        foreach ($groups as $key => $group) {
            $labels[] = $key;
            $dVals = $group->pluck('avg_distance')->filter(fn ($v) => $v !== null);
            $bVals = $group->pluck('avg_blink_rate')->filter(fn ($v) => $v !== null);
            $distances[] = $dVals->isEmpty() ? null : round((float) $dVals->avg(), 1);
            $blinks[] = $bVals->isEmpty() ? null : round((float) $bVals->avg(), 1);
            $screens[] = round((float) $group->avg('screen_time_minutes'), 1);
        }

        return [
            'labels' => $labels,
            'distances' => $distances,
            'blink_rates' => $blinks,
            'screen_times' => $screens,
        ];
    }

    /**
     * @return list<array{minutes: int, unlocks_at_age: int, locked: bool}>
     */
    private function unlockMilestones(?int $ageYears): array
    {
        $age = $ageYears ?? 0;

        return [
            ['minutes' => 60, 'unlocks_at_age' => 3, 'locked' => $age < 3],
            ['minutes' => 90, 'unlocks_at_age' => 6, 'locked' => $age < 6],
            ['minutes' => 120, 'unlocks_at_age' => 9, 'locked' => $age < 9],
        ];
    }

    /**
     * @param  list<mixed>  $values
     */
    private function mean(array $values): ?float
    {
        $nums = array_values(array_filter($values, fn ($v) => $v !== null && is_numeric($v)));
        if ($nums === []) {
            return null;
        }

        return array_sum($nums) / count($nums);
    }
}
