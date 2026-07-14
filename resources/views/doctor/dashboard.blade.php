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
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@400;600;700&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-green: #527267;
            --bg-light: #f8fafc;
            --warn: #b45309;
            --danger-zone: rgba(220, 38, 38, 0.12);
        }
        body {
            font-family: 'Source Sans 3', system-ui, sans-serif;
            background:
                radial-gradient(circle at 90% 50%, rgba(42, 131, 68, 0.15) 0%, transparent 35%),
                radial-gradient(circle at 50% 50%, #E4FFD8 0%, transparent 60%),
                #f8fafc;
            min-height: 100vh;
        }
        .brand { font-family: 'Fredoka', sans-serif; color: var(--primary-green); font-weight: 700; }
        .panel {
            background: #fff;
            border: 1px solid rgba(82, 114, 103, 0.18);
            border-radius: 1.25rem;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.08);
        }
        .otp-box {
            letter-spacing: 0.4rem;
            font-size: 1.75rem;
            text-align: center;
            font-weight: 700;
        }
        .session-banner {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            border-radius: 1rem;
            padding: 0.85rem 1.1rem;
        }
        .nav-pill .nav-link.active {
            background: var(--primary-green);
            color: #fff;
        }
        #qr-reader { width: 100%; max-width: 360px; margin: 0 auto; }
        .table thead th { font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; }
    </style>
</head>
<body>
@php
    $hasSession = !empty($active) && !empty($sessionId);
    $telemetryJson = json_encode($dashboardData ?? []);
@endphp
<nav class="navbar navbar-expand-lg px-4 py-3">
    <div class="container-fluid">
        <span class="brand fs-3">SIGHT</span>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted">{{ $doctor->display_name }}</span>
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button class="btn btn-sm btn-outline-secondary rounded-pill">Logout</button>
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
                        <p class="text-muted mb-4">Scan the parent QR code or type the 6-digit OTP. Access lasts until the parent or you end the session.</p>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">6-digit OTP</label>
                            <input id="otpInput" class="form-control otp-box mx-auto" maxlength="6" inputmode="numeric" placeholder="000000" style="max-width: 260px;">
                        </div>
                        <button id="redeemBtn" class="btn btn-success rounded-pill px-4 mb-4" style="background: var(--primary-green); border:0;">
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
const sessionId = @json($sessionId);
const dash = @json($dashboardData);

async function redeemCode(code) {
    const err = document.getElementById('redeemError');
    if (err) err.textContent = '';
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
        const body = await res.json();
        if (!res.ok || body.status === 'error') {
            if (err) err.textContent = body.message || 'Invalid or expired code';
            return;
        }
        window.location.reload();
    } catch (e) {
        if (err) err.textContent = 'Network error. Try again.';
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
    if (!sessionId) return;
    await fetch(`/doctor/access-sessions/${sessionId}/end`, {
        method: 'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
    });
    window.location.reload();
});

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

if (hasSession && dash?.has_data) {
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
