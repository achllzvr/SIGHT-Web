<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>LUMI Guardian Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7f5; font-family: system-ui, sans-serif; }
        .shell { max-width: 1100px; margin: 0 auto; padding: 1.5rem; }
        .card-soft { background: #fff; border: 1px solid #dde5e0; border-radius: 16px; padding: 1.25rem; }
        .badge-safe { background: #16a34a; }
        .badge-caution { background: #d97706; }
        .badge-risk { background: #ea580c; }
        .badge-critical { background: #dc2626; }
        .pro-card { border: 1px solid #e5ebe7; border-radius: 12px; padding: 1rem; height: 100%; }
    </style>
</head>
<body>
<div class="shell">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h3 mb-1">Guardian Web Dashboard</h1>
            <p class="text-muted mb-0">Read-only view while your child uses LUMI on mobile. Live status refreshes every 15 seconds.</p>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn btn-outline-secondary btn-sm">Log out</button>
        </form>
    </div>

    @if($children->isEmpty())
        <div class="card-soft">
            <p class="mb-0">No child profiles yet. Create a child in the LUMI mobile app, then return here.</p>
        </div>
    @else
        <form method="GET" class="card-soft mb-3 d-flex flex-wrap gap-3 align-items-end">
            <div>
                <label class="form-label fw-semibold" for="child_id">Child</label>
                <select name="child_id" id="child_id" class="form-select" onchange="this.form.submit()">
                    @foreach($children as $c)
                        <option value="{{ $c->child_id }}" @selected($selected && $selected->child_id === $c->child_id)>
                            {{ $c->user?->display_name ?? ('Child #'.$c->child_id) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label fw-semibold" for="range">Historical range</label>
                <select name="range" id="range" class="form-select" onchange="this.form.submit()">
                    @foreach(['7d'=>'Last 7 Days','30d'=>'Last Month','90d'=>'Last 3 Months','180d'=>'Last 6 Months','365d'=>'Last Year','lifetime'=>'Lifetime'] as $val => $label)
                        <option value="{{ $val }}" @selected($range === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <span id="presenceBadge" class="badge rounded-pill text-bg-{{ ($presence['presence_status'] ?? '') === 'online' ? 'success' : 'secondary' }}">
                    Device: {{ $presence['presence_label'] ?? 'Offline' }}
                </span>
            </div>
        </form>

        @if($dashboard)
            @php
                $hasData = (bool) ($dashboard['has_data'] ?? false);
                $alert = $hasData ? ($dashboard['clinical_trend_alert'] ?? null) : null;
            @endphp
            <div class="row g-3 mb-3">
                <div class="col-md-3"><div class="card-soft"><div class="text-muted small">Health Grade</div><div class="fs-4 fw-bold">{{ $hasData ? ($dashboard['health_grade'] ?? '—') : 'No Data' }}</div></div></div>
                <div class="col-md-3"><div class="card-soft"><div class="text-muted small">Avg Distance</div><div class="fs-4 fw-bold">{{ $hasData && $dashboard['mean_distance_cm'] !== null ? $dashboard['mean_distance_cm'].' cm' : '—' }}</div></div></div>
                <div class="col-md-3"><div class="card-soft"><div class="text-muted small">Avg Blink</div><div class="fs-4 fw-bold">{{ $hasData && $dashboard['mean_blink_rate'] !== null ? $dashboard['mean_blink_rate'].'/min' : '—' }}</div></div></div>
                <div class="col-md-3"><div class="card-soft"><div class="text-muted small">Avg Screen Time</div><div class="fs-4 fw-bold">{{ $hasData && $dashboard['mean_screen_time_minutes'] !== null ? $dashboard['mean_screen_time_minutes'].' min' : '—' }}</div></div></div>
            </div>

            @php
                $conditionPlain = [
                    'SAFE' => 'Looking good',
                    'CAUTION' => 'Worth watching',
                    'AT_RISK' => 'Needs attention',
                    'CRITICAL' => 'Talk to a doctor',
                ];
                $overall = $alert['overall_condition'] ?? null;
            @endphp
            <div class="card-soft mb-3">
                <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                    <h2 class="h5 mb-0">How eyes are doing lately</h2>
                    @if($hasData && $alert)
                        <span class="badge rounded-pill
                            @if($overall === 'SAFE') badge-safe
                            @elseif($overall === 'CAUTION') badge-caution
                            @elseif($overall === 'AT_RISK') badge-risk
                            @else badge-critical @endif">
                            {{ $conditionPlain[$overall] ?? 'Checking…' }}
                        </span>
                        <span class="badge text-bg-light text-dark">Near vision: {{ $conditionPlain[$alert['myopia_status'] ?? ''] ?? '—' }}</span>
                        <span class="badge text-bg-light text-dark">Blink comfort: {{ $conditionPlain[$alert['dry_eye_status'] ?? ''] ?? '—' }}</span>
                    @endif
                </div>
                @if(!$hasData)
                    <p class="fw-semibold mb-1">No eye-care data in this time range yet</p>
                    <p class="mb-0 text-muted">Device Online only means the app is awake. After Watch tracking, sync from the parent account so trends can appear here.</p>
                @elseif($alert)
                    <p class="fw-semibold mb-1">{{ $alert['alert_title'] ?? '' }}</p>
                    <p class="mb-1"><strong>What we noticed:</strong> {{ $alert['what_is_wrong_text'] ?? '' }}</p>
                    <p class="mb-0 text-muted"><strong>Why this matters:</strong> {{ $alert['why_showing_clinical_basis_text'] ?? '' }}</p>
                @else
                    <p class="mb-0 text-muted">No tips yet for this time range.</p>
                @endif
                @if($hasData && !empty($dashboard['usage_threshold']))
                    <hr>
                    <p class="mb-0"><strong>Screen time:</strong> {{ $dashboard['usage_threshold']['badge'] ?? '' }} — {{ $dashboard['usage_threshold']['label'] ?? '' }}</p>
                @endif
            </div>
        @endif

        <div class="card-soft">
            <h2 class="h5">Find an Eye Doctor</h2>
            <p class="text-muted">Verified LUMI clinics. Create a 15-minute visit code from the mobile app to share today’s summary.</p>
            <div class="row g-3">
                @forelse($professionals as $pro)
                    <div class="col-md-6">
                        <div class="pro-card">
                            <div class="fw-semibold">{{ $pro->user?->display_name ?? 'Clinician' }}</div>
                            <div class="small text-muted">{{ $pro->specialty }} · ✔ LUMI Verified</div>
                            <div class="small mt-2">{{ $pro->clinic }}</div>
                            <div class="small">{{ $pro->clinic_address ?? $pro->location }}</div>
                            <div class="small">{{ $pro->operating_hours ?? 'Hours not listed' }}</div>
                            <div class="small">{{ $pro->phone }}</div>
                        </div>
                    </div>
                @empty
                    <div class="col-12"><p class="mb-0 text-muted">No clinics are listed yet.</p></div>
                @endforelse
            </div>
        </div>
    @endif
</div>
@if($selected)
<script>
(function () {
    const childId = @json($selected->child_id);
    const range = @json($range);
    const badge = document.getElementById('presenceBadge');
    const poll = () => {
        if (document.visibilityState !== 'visible') return;
        fetch(@json(route('guardian.poll')) + '?child_id=' + childId + '&range=' + encodeURIComponent(range), {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin',
        }).then(r => r.json()).then(body => {
            const p = body?.data?.presence;
            if (badge && p?.presence_label) {
                badge.textContent = 'Device: ' + p.presence_label;
                badge.className = 'badge rounded-pill text-bg-' + (p.presence_status === 'online' ? 'success' : 'secondary');
            }
        }).catch(() => {});
    };
    poll();
    setInterval(poll, 15000);
})();
</script>
@endif
</body>
</html>
