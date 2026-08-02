<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Clinician Portal — SIGHT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    @include('partials.ds-head')
    <style>
        :root {
            --warn: #eab308;
            --danger-zone: rgba(239, 68, 68, 0.12);
        }
        body {
            background:
                radial-gradient(circle at 90% 50%, rgba(91, 154, 122, 0.12) 0%, transparent 35%),
                radial-gradient(circle at 50% 50%, #ecfdf5 0%, transparent 55%),
                var(--ds-bg);
            min-height: 100vh;
        }
        .otp-box {
            letter-spacing: 0.4rem;
            font-size: 1.75rem;
            text-align: center;
            font-weight: 700;
            height: 48px;
            border-radius: var(--ds-radius-md);
        }
        #qr-reader { width: 100%; max-width: 360px; margin: 0 auto; }
        .empty-state { padding: 2rem 1.5rem; text-align: center; }
        .empty-state i { font-size: 2rem; color: var(--ds-text-disabled); }
        .otp-hint { font-size: 0.875rem; color: var(--ds-text-secondary); }
        .session-timer { font-size: 0.875rem; color: var(--ds-brand-700); }
    </style>
</head>
<body>
@php
    $hasSession = !empty($active) && !empty($sessionId);
    $hasTelemetry = !empty($dashboardData['has_data']);
    $sessionAccessedAt = $hasSession ? ($active['accessed_at'] ?? null) : null;
    $lastSync = $selectedPatient['last_sync'] ?? null;
@endphp
<nav class="navbar navbar-expand-lg px-4 py-3 ds-topbar">
    <div class="container-fluid">
        <span class="brand fs-3">SIGHT</span>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted">{{ $doctor->display_name }}</span>
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button class="btn btn-sm btn-outline-secondary ds-btn-ghost">Logout</button>
            </form>
        </div>
    </div>
</nav>

<div class="container pb-5">
    <ul class="nav nav-pills nav-pill mb-4 gap-2" id="portalTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill" id="access-tab" data-bs-toggle="pill" data-bs-target="#accessPane" type="button">Patient Access</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill" id="history-tab" data-bs-toggle="pill" data-bs-target="#historyPane" type="button">Viewing History</button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="accessPane">
            @if(!$hasSession)
            <div class="row justify-content-center">
                <div class="col-lg-7">
                    <div class="panel p-4 p-md-5 text-center">
                        <h2 class="brand h3 mb-2">Enter on-site access code</h2>
                        <p class="text-muted mb-2">Scan the parent QR code or type the 6-digit OTP. Access lasts until the parent or you end the session.</p>
                        <p class="otp-hint mb-4"><i class="bi bi-clock"></i> Parent codes expire <strong>15 minutes</strong> after generation. Ask for a fresh code if yours has expired.</p>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">6-digit OTP</label>
                            <input id="otpInput" class="form-control otp-box mx-auto" maxlength="6" inputmode="numeric" placeholder="000000" style="max-width: 260px;" aria-describedby="otpExpiryHint">
                            <div id="otpExpiryHint" class="form-text otp-hint mt-2">Codes are single-use and valid for 15 minutes.</div>
                        </div>
                        <button id="redeemBtn" class="btn btn-success ds-btn-primary px-4 mb-4">
                            View Patient Data
                        </button>

                        <hr class="my-4">
                        <p class="text-muted small mb-3">Or scan QR code</p>
                        <div id="qr-reader"></div>
                        <div id="redeemError" class="text-danger mt-3 small"></div>
                    </div>
                </div>
            </div>
            @else
            <div class="session-banner d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <strong>Viewing {{ $selectedPatient['name'] ?? 'Patient' }}</strong>
                    <span class="text-muted ms-2">{{ $selectedPatient['patient_code'] ?? '' }} · Session live</span>
                    @if($sessionAccessedAt)
                    <div class="session-timer mt-1"><i class="bi bi-stopwatch"></i> Active for <span id="sessionElapsed">—</span></div>
                    @endif
                </div>
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-success btn-sm rounded-pill" href="{{ route('doctor.access.report', $sessionId) }}">
                        <i class="bi bi-filetype-pdf"></i> Export PDF
                    </a>
                    <button id="endVisitBtn" class="btn btn-outline-danger btn-sm rounded-pill">End Visit</button>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3"><div class="panel p-3"><div class="text-muted small">Health Grade</div><div class="fs-4 fw-bold">{{ $dashboardData['health_grade'] }}</div></div></div>
                <div class="col-md-3"><div class="panel p-3"><div class="text-muted small">Health Score</div><div class="fs-4 fw-bold">{{ $dashboardData['health_score_display'] }}</div></div></div>
                <div class="col-md-3"><div class="panel p-3"><div class="text-muted small">Avg Distance</div><div class="fs-4 fw-bold">{{ $dashboardData['distance_display'] }}</div></div></div>
                <div class="col-md-3"><div class="panel p-3"><div class="text-muted small">&lt; 30 cm Violations</div><div class="fs-4 fw-bold text-warning">{{ $dashboardData['distance_violations'] }}</div></div></div>
            </div>

            @if(!$hasTelemetry)
            <div class="empty-state mb-4">
                <i class="bi bi-cloud-slash d-block mb-3"></i>
                <h3 class="h6 text-dark mb-2">No eye-health data yet</h3>
                <p class="mb-0">Ask the parent to sync from the LUMI app.@if(empty($lastSync)) No sync has been recorded for this child yet.@else Last sync: {{ \Carbon\Carbon::parse($lastSync)->format('M d, Y g:i A') }}.@endif</p>
            </div>
            @else
            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="panel p-3">
                        <h3 class="h6 mb-3">Blink Rate &amp; Viewing Distance</h3>
                        <canvas id="complianceChart" height="180"></canvas>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="panel p-3">
                        <h3 class="h6 mb-3">Screen Time &amp; Strain</h3>
                        <canvas id="screenTimeChart" height="180"></canvas>
                    </div>
                </div>
                <div class="col-12">
                    <div class="panel p-3">
                        <h3 class="h6 mb-3">Health Score Trend</h3>
                        <canvas id="healthScoreChart" height="120"></canvas>
                    </div>
                </div>
            </div>
            @endif
            @endif
        </div>

        <div class="tab-pane fade" id="historyPane">
            <div class="panel p-4">
                <h2 class="h5 brand mb-3">Your viewing history</h2>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Accessed</th>
                                <th>Ended</th>
                                <th>Status</th>
                                <th>Ended by</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($accessLogs as $log)
                            <tr>
                                <td>{{ $log['child_name'] ?? 'Unknown' }}</td>
                                <td>{{ isset($log['accessed_at']) ? \Carbon\Carbon::parse($log['accessed_at'])->format('M d, Y g:i A') : '—' }}</td>
                                <td>{{ !empty($log['ended_at']) ? \Carbon\Carbon::parse($log['ended_at'])->format('M d, Y g:i A') : '—' }}</td>
                                <td><span class="badge {{ ($log['status'] ?? '') === 'active' ? 'bg-success' : 'bg-secondary' }}">{{ $log['status'] ?? '—' }}</span></td>
                                <td>{{ $log['ended_by'] ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-muted text-center py-4">No viewing sessions yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const hasSession = @json($hasSession);
const hasTelemetry = @json($hasTelemetry);
const sessionId = @json($sessionId);
const sessionAccessedAt = @json($sessionAccessedAt);
const dash = @json($dashboardData);

async function redeemCode(code) {
    const err = document.getElementById('redeemError');
    const btn = document.getElementById('redeemBtn');
    if (err) err.textContent = '';
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Verifying…';
    }
    try {
        const res = await fetch(@json(route('doctor.access.redeem')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ code: String(code).trim() }),
        });
        const body = await res.json().catch(() => ({}));
        if (res.status === 429) {
            if (err) err.textContent = 'Too many attempts. Please wait a minute before trying again.';
            return;
        }
        if (!res.ok || body.status === 'error') {
            if (err) err.textContent = body.message || 'Invalid or expired code';
            return;
        }
        window.location.reload();
    } catch (e) {
        if (err) err.textContent = 'Network error. Try again.';
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.textContent = 'View Patient Data';
        }
    }
}

document.getElementById('redeemBtn')?.addEventListener('click', () => {
    const code = document.getElementById('otpInput')?.value || '';
    if (!/^\d{6}$/.test(code)) {
        document.getElementById('redeemError').textContent = 'Enter a valid 6-digit OTP';
        return;
    }
    redeemCode(code);
});

if (!hasSession && window.Html5Qrcode) {
    const scanner = new Html5Qrcode('qr-reader');
    scanner.start(
        { facingMode: 'environment' },
        { fps: 8, qrbox: { width: 220, height: 220 } },
        (decoded) => {
            scanner.stop().catch(() => {});
            redeemCode(decoded);
        },
        () => {}
    ).catch(() => {
        const el = document.getElementById('qr-reader');
        if (el) el.innerHTML = '<p class="text-muted small">Camera unavailable — use OTP entry.</p>';
    });
}

document.getElementById('endVisitBtn')?.addEventListener('click', async () => {
    if (!sessionId || !confirm('End this viewing session? The parent will be notified.')) return;
    const btn = document.getElementById('endVisitBtn');
    if (btn) btn.disabled = true;
    try {
        const res = await fetch(`/doctor/access-sessions/${sessionId}/end`, {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
        });
        if (!res.ok) {
            alert('Could not end the session. Please try again.');
            if (btn) btn.disabled = false;
            return;
        }
        window.location.reload();
    } catch (_) {
        alert('Network error while ending session.');
        if (btn) btn.disabled = false;
    }
});

function formatElapsed(ms) {
    const totalSeconds = Math.max(0, Math.floor(ms / 1000));
    const minutes = Math.floor(totalSeconds / 60);
    const seconds = totalSeconds % 60;
    return `${minutes}m ${String(seconds).padStart(2, '0')}s`;
}

function updateSessionElapsed() {
    const el = document.getElementById('sessionElapsed');
    if (!el || !sessionAccessedAt) return;
    const started = new Date(sessionAccessedAt).getTime();
    if (Number.isNaN(started)) return;
    el.textContent = formatElapsed(Date.now() - started);
}

if (hasSession && sessionAccessedAt) {
    updateSessionElapsed();
    setInterval(updateSessionElapsed, 1000);
}

if (hasSession && sessionId) {
    setInterval(async () => {
        try {
            const res = await fetch(@json(route('doctor.access.active')), { headers: { 'Accept': 'application/json' } });
            const body = await res.json();
            if (!body?.data?.active) {
                alert('The parent ended this viewing session.');
                window.location.reload();
            }
        } catch (_) {}
    }, 5000);
}

if (hasSession && hasTelemetry && dash?.has_data) {
    const labels = dash.labels || [];
    const harmful = dash.harmful_distance_cm || 30;
    const harmLine = labels.map(() => harmful);

    new Chart(document.getElementById('complianceChart'), {
        type: 'line',
        data: {
            labels,
            datasets: [
                { label: 'Blink rate', data: dash.blink_rates, borderColor: '#527267', yAxisID: 'y', tension: 0.3 },
                { label: 'Distance (cm)', data: dash.distances, borderColor: '#2563eb', yAxisID: 'y1', tension: 0.3 },
                { label: '30 cm warning', data: harmLine, borderColor: '#dc2626', borderDash: [6, 4], pointRadius: 0, yAxisID: 'y1' },
            ],
        },
        options: {
            responsive: true,
            scales: {
                y: { position: 'left', title: { display: true, text: 'Blinks/min' } },
                y1: { position: 'right', min: 0, max: 80, grid: { drawOnChartArea: false }, title: { display: true, text: 'cm' } },
            },
        },
    });

    new Chart(document.getElementById('screenTimeChart'), {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'Screen time (min)', data: dash.screen_times, backgroundColor: 'rgba(82,114,103,0.5)' },
                { label: 'Strain events', data: dash.strain_events, type: 'line', borderColor: '#f59e0b', tension: 0.3 },
            ],
        },
        options: { responsive: true },
    });

    new Chart(document.getElementById('healthScoreChart'), {
        type: 'line',
        data: {
            labels,
            datasets: [{ label: 'Health score', data: dash.health_scores, borderColor: '#16a34a', tension: 0.3, fill: false }],
        },
        options: { responsive: true, scales: { y: { min: 0, max: 100 } } },
    });
}
</script>
</body>
</html>
