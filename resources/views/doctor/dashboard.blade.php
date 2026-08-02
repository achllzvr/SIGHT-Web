<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Clinician Portal — LUMI</title>
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
        #qr-reader { width: 100%; max-width: 320px; margin: 0 auto; }
        .empty-state { padding: 2rem 1.5rem; text-align: center; }
        .empty-state i { font-size: 2rem; color: var(--ds-text-disabled); }
        .otp-hint { font-size: 0.875rem; color: var(--ds-text-secondary); }
        .session-timer { font-size: 0.875rem; color: var(--ds-brand-700); }
        .guide-callout {
            background: #f0fdf4;
            border: 1px solid rgba(82, 114, 103, 0.35);
            border-radius: var(--ds-radius-md);
            padding: 1rem 1.15rem;
        }
        .guide-callout h3 { font-size: 0.95rem; margin-bottom: 0.35rem; }
        .guide-callout p, .chart-guide, .kpi-hint { font-size: 0.8125rem; color: var(--ds-text-secondary); line-height: 1.45; margin-bottom: 0; }
        .kpi-hint { margin-top: 0.35rem; }
        .chart-guide { margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px dashed rgba(0,0,0,0.08); }
        .chart-guide strong { color: #334155; font-weight: 600; }
        .ref-list { margin: 0; padding-left: 1.1rem; }
        .ref-list li { margin-bottom: 0.55rem; font-size: 0.8125rem; color: var(--ds-text-secondary); line-height: 1.45; }
        .ref-list li strong { color: #1e293b; }
        .ref-accordion .accordion-button { font-weight: 600; font-size: 0.9rem; }
        .ref-accordion .accordion-body { padding-top: 0.25rem; }
    </style>
    @include('partials.ds-arcade-head')
    <style>
        /* Loaded after arcade CSS so spacing wins on Hostinger/cache */
        .doctor-portal {
            padding-top: 1.25rem !important;
        }
        #portalTabs.nav {
            display: flex !important;
            flex-wrap: wrap !important;
            align-items: center !important;
            gap: 0 !important;
            margin: 0 0 1rem !important;
            padding: 0 0 8px !important; /* room for active pill hard shadow */
        }
        #portalTabs > .nav-item {
            margin: 0 12px 0 0 !important;
            padding: 0 !important;
        }
        #portalTabs > .nav-item:last-child { margin-right: 0 !important; }
        #portalTabs .nav-link {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            min-height: 42px !important;
            padding: 0.55rem 1.25rem !important;
            border: 3px solid #d4d4d4 !important;
            border-radius: 9999px !important;
            background: #fff !important;
            color: #6b6b6b !important;
            line-height: 1.2 !important;
            box-shadow: none !important;
        }
        #portalTabs .nav-link.active {
            background: #ffdcf9 !important;
            border-color: #8168ab !important;
            color: #8168ab !important;
            box-shadow: 0 3px 0 0 #8168ab !important;
        }
        .doctor-portal .tab-content {
            margin-top: 0 !important;
            padding-top: 0 !important;
        }
        .access-split {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem 2rem;
            align-items: stretch;
            text-align: left;
            margin-top: 1.25rem;
        }
        .access-split__col {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            min-width: 0;
        }
        .access-split__divider {
            display: none;
        }
        .access-split__label {
            font-size: 0.8rem;
            font-weight: 500;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #6b6b6b;
            margin-bottom: 0.85rem;
            text-align: center;
        }
        #qr-reader {
            width: 100%;
            max-width: 320px;
            margin: 0 auto;
        }
        #qr-reader video,
        #qr-reader canvas {
            border-radius: 16px;
        }
        @media (max-width: 767.98px) {
            .access-split {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            .access-split__divider {
                display: block;
                width: 100%;
                border: 0;
                border-top: 2px dashed #d4d4d4;
                margin: 0;
            }
        }
    </style>
</head>
<body class="lumi-arcade">
@php
    $hasSession = !empty($active) && !empty($sessionId);
    $hasTelemetry = !empty($dashboardData['has_data']);
    $sessionAccessedAt = $hasSession ? ($active['accessed_at'] ?? null) : null;
    $lastSync = $selectedPatient['last_sync'] ?? null;
    $openSettings = !empty($openSettings);
    $profilePhone = old('phone', $doctorProfile?->phone ?? $doctor->phone ?? '');
    $profileClinic = old('clinic', $doctorProfile?->clinic ?? $doctor->clinic ?? '');
    $profileSpecialty = old('specialty', $doctorProfile?->specialty ?? $doctor->specialty ?? '');
    $profileLicense = old('license_number', $doctorProfile?->license_number ?? $doctor->license_number ?? '');
    $profileLocation = old('location', $doctorProfile?->location ?? $doctor->location ?? '');
@endphp
<nav class="navbar navbar-expand-lg px-4 py-3 ds-topbar">
    <div class="container-fluid">
        <span class="brand fs-3">LUMI</span>
        <div class="d-flex align-items-center gap-3">
            <span class="text-muted">{{ $doctor->display_name }}</span>
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button class="btn btn-sm btn-outline-secondary ds-btn-ghost">Logout</button>
            </form>
        </div>
    </div>
</nav>

<div class="container pb-5 doctor-portal">
    <ul class="nav nav-pills nav-pill" id="portalTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill {{ $openSettings ? '' : 'active' }}" id="access-tab" data-bs-toggle="pill" data-bs-target="#accessPane" type="button" role="tab" aria-controls="accessPane" aria-selected="{{ $openSettings ? 'false' : 'true' }}">Patient Access</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill" id="history-tab" data-bs-toggle="pill" data-bs-target="#historyPane" type="button" role="tab" aria-controls="historyPane" aria-selected="false">Viewing History</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill {{ $openSettings ? 'active' : '' }}" id="settings-tab" data-bs-toggle="pill" data-bs-target="#settingsPane" type="button" role="tab" aria-controls="settingsPane" aria-selected="{{ $openSettings ? 'true' : 'false' }}">Account &amp; Security</button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade {{ $openSettings ? '' : 'show active' }}" id="accessPane" role="tabpanel" aria-labelledby="access-tab">
            @if(!$hasSession)
            <div class="row justify-content-center">
                <div class="col-12">
                    <div class="panel p-4 p-md-5 text-center">
                        <h2 class="brand h3 mb-2">Enter on-site access code</h2>
                        <p class="text-muted mb-2">Scan the parent QR code or type the 6-digit OTP. Access lasts until the parent or you end the session.</p>
                        <p class="otp-hint mb-0"><i class="bi bi-clock"></i> Parent codes expire <strong>15 minutes</strong> after generation. Ask for a fresh code if yours has expired.</p>

                        <div class="access-split">
                            <div class="access-split__col">
                                <div class="access-split__label">Type 6-digit OTP</div>
                                <div class="w-100" style="max-width: 280px;">
                                    <label class="form-label fw-semibold" for="otpInput">6-digit OTP</label>
                                    <input id="otpInput" class="form-control otp-box mx-auto" maxlength="6" inputmode="numeric" placeholder="000000" aria-describedby="otpExpiryHint">
                                    <div id="otpExpiryHint" class="form-text otp-hint mt-2">Codes are single-use and valid for 15 minutes.</div>
                                </div>
                                <button id="redeemBtn" class="btn btn-success ds-btn-primary px-4 mt-3">
                                    View Patient Data
                                </button>
                            </div>

                            <hr class="access-split__divider">

                            <div class="access-split__col">
                                <div class="access-split__label">Or scan QR code</div>
                                <div id="qr-reader"></div>
                            </div>
                        </div>

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

            <div class="guide-callout mb-4">
                <h3 class="d-flex align-items-center gap-2"><i class="bi bi-info-circle"></i> How to read this visit snapshot</h3>
                <p class="mb-2">
                    These figures come from the child’s <strong>LUMI mobile app</strong>. During Watch sessions, on-device face tracking records blink rate and viewing distance; the app curates those samples into short metric windows and the parent syncs them to LUMI.
                    Charts below cover the <strong>last 7 days</strong> (daily aggregates). Summary cards use that same window.
                    @if(!empty($lastSync))
                        Last parent sync: <strong>{{ \Carbon\Carbon::parse($lastSync)->format('M d, Y g:i A') }}</strong>.
                    @else
                        No parent sync timestamp is on file yet for this child.
                    @endif
                </p>
                <p class="mb-0">
                    Use this as <strong>contextual support for counseling</strong> (near-work habits, breaks, blink patterns)—not as a substitute for clinical examination or refraction.
                </p>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="panel p-3 h-100">
                        <div class="text-muted small">Health Grade</div>
                        <div class="fs-4 fw-bold">{{ $dashboardData['health_grade'] }}</div>
                        <p class="kpi-hint">Label from the latest health score: Excellent ≥90 · Good ≥80 · Fair ≥70 · Needs Attention &lt;70.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="panel p-3 h-100">
                        <div class="text-muted small">Health Score</div>
                        <div class="fs-4 fw-bold">{{ $dashboardData['health_score_display'] }}</div>
                        <p class="kpi-hint">0–100 score from the app. Drops when viewing is too close or blinks are too few; recovers after healthy breaks/exercises.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="panel p-3 h-100">
                        <div class="text-muted small">Avg Distance</div>
                        <div class="fs-4 fw-bold">{{ $dashboardData['distance_display'] }}</div>
                        <p class="kpi-hint">Mean viewing distance across days with data. Target habit: keep screens at about <strong>30 cm or farther</strong>.</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="panel p-3 h-100">
                        <div class="text-muted small">&lt; 30 cm Violations</div>
                        <div class="fs-4 fw-bold text-warning">{{ $dashboardData['distance_violations'] }}</div>
                        <p class="kpi-hint">Number of days in this 7-day window whose <em>daily average</em> distance fell below 30 cm (near-work risk days).</p>
                    </div>
                </div>
            </div>

            @if($hasTelemetry)
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="panel p-3 h-100">
                        <div class="text-muted small">Avg Blink Rate</div>
                        <div class="fs-5 fw-bold">{{ $dashboardData['blink_rate_display'] }}</div>
                        <p class="kpi-hint">Daily averages, then mean across days with data. Low blink rates (&lt;12/min in daily avg) are flagged as dry-eye / concentration risk.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="panel p-3 h-100">
                        <div class="text-muted small">Avg Screen Time</div>
                        <div class="fs-5 fw-bold">{{ $dashboardData['screen_time_display'] }}</div>
                        <p class="kpi-hint">Minutes of active Watch tracking per day (averaged). Reflects supervised play/use tracked by LUMI—not all device use.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="panel p-3 h-100">
                        <div class="text-muted small">Low Blink Days</div>
                        <div class="fs-5 fw-bold">{{ $dashboardData['low_blink_events'] }}</div>
                        <p class="kpi-hint">Days whose average blink rate was below 12 blinks/min. Useful when discussing blink exercises and break habits.</p>
                    </div>
                </div>
            </div>
            @endif

            @if(!$hasTelemetry)
            <div class="empty-state mb-4">
                <i class="bi bi-cloud-slash d-block mb-3"></i>
                <h3 class="h6 text-dark mb-2">No eye-health data yet</h3>
                <p class="mb-0">Ask the parent to open LUMI, let the child use Watch tracking, then sync from the parent account.@if(empty($lastSync)) No sync has been recorded for this child yet.@else Last sync: {{ \Carbon\Carbon::parse($lastSync)->format('M d, Y g:i A') }}.@endif</p>
            </div>
            @else
            <div class="row g-3">
                <div class="col-lg-6">
                    <div class="panel p-3">
                        <h3 class="h6 mb-1">Blink Rate &amp; Viewing Distance</h3>
                        <p class="text-muted small mb-3">Daily averages over the last 7 days. Red dashed line = 30 cm near-work warning.</p>
                        <canvas id="complianceChart" height="180"></canvas>
                        <div class="chart-guide">
                            <strong>What this shows:</strong> Blink rate (blinks/min) and average face-to-screen distance (cm) for each day.<br>
                            <strong>How it’s calculated:</strong> The app samples blinks and distance during Watch sessions, stores short windows, then syncs. The portal averages those windows per calendar day.<br>
                            <strong>Why it matters:</strong> Prolonged near focus and reduced blinking are linked to digital eye strain and discomfort. Sustained averages under 30 cm or sparse blinking are counseling cues for posture, breaks, and blink training.
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="panel p-3">
                        <h3 class="h6 mb-1">Screen Time &amp; Strain</h3>
                        <p class="text-muted small mb-3">Bars = tracked Watch minutes; line = strain events counted that day.</p>
                        <canvas id="screenTimeChart" height="180"></canvas>
                        <div class="chart-guide">
                            <strong>What this shows:</strong> How long Watch tracking was active and how often near-distance “strain” samples were counted.<br>
                            <strong>How it’s calculated:</strong> Screen time is minutes of active Watch use logged by LUMI. Strain events count distance samples closer than the harmful threshold (default 30 cm) inside each metric window, then summed per day.<br>
                            <strong>Why it matters:</strong> Higher load plus frequent close-range samples suggests denser near-work exposure—helpful when advising 20-20-20 breaks and session limits.
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="panel p-3">
                        <h3 class="h6 mb-1">Health Score Trend</h3>
                        <p class="text-muted small mb-3">Daily average of the child’s in-app health score (0–100).</p>
                        <canvas id="healthScoreChart" height="120"></canvas>
                        <div class="chart-guide">
                            <strong>What this shows:</strong> Day-by-day health score from LUMI’s gamified eye-care meter.<br>
                            <strong>How it’s calculated:</strong> The app starts near 100 and deducts points when accommodative demand is high (closer than ~30 cm) or blink rate falls below a protective baseline (~10 blinks/min). Completing breaks and blink exercises can restore points. Daily points here are averages of synced windows.<br>
                            <strong>Why it matters:</strong> A falling trend often means accumulating near-work stress; a rising trend suggests better habits or recovery after interventions you recommend.
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="panel p-0 overflow-hidden ref-accordion">
                        <div class="accordion accordion-flush" id="metricReference">
                            <div class="accordion-item border-0">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#metricReferenceBody" aria-expanded="false" aria-controls="metricReferenceBody">
                                        <i class="bi bi-journal-medical me-2"></i> Metric reference — definitions, source &amp; clinical use
                                    </button>
                                </h2>
                                <div id="metricReferenceBody" class="accordion-collapse collapse" data-bs-parent="#metricReference">
                                    <div class="accordion-body px-4 pb-4">
                                        <ul class="ref-list">
                                            <li><strong>Data source.</strong> Parent-authorized LUMI child account. Metrics are captured on the phone during Watch tracking (face present), curated locally, then synced to LUMI when the parent is online.</li>
                                            <li><strong>Time window.</strong> This view always shows the rolling last 7 calendar days. Days without synced data appear as gaps on charts.</li>
                                            <li><strong>Blink rate.</strong> Estimated blinks per minute from on-device facial landmarks. Sustained low rates can increase tear-film evaporation and symptoms of digital eye strain.</li>
                                            <li><strong>Viewing distance.</strong> Estimated face-to-device distance in centimeters. The portal treats &lt; 30 cm as the harmful near-work zone (≈ &gt; 3.3 D accommodative demand).</li>
                                            <li><strong>Strain events.</strong> Count of close-range distance samples inside each metric window (threshold 30 cm), summed per day—not a medical diagnosis of “eye strain.”</li>
                                            <li><strong>Screen time.</strong> Minutes of LUMI Watch tracking only. It under-represents total screen exposure outside the app.</li>
                                            <li><strong>Health score &amp; grade.</strong> App-side behavioral score (0–100) summarizing distance + blink compliance, with recovery from healthy breaks. Grade bands: Excellent ≥90, Good ≥80, Fair ≥70, Needs Attention &lt;70.</li>
                                            <li><strong>Violations / low-blink days.</strong> Counts of days whose <em>daily average</em> distance &lt; 30 cm or blink rate &lt; 12/min—quick flags for counseling, not per-second episode counts.</li>
                                            <li><strong>Limitations.</strong> Accuracy depends on camera angle, lighting, calibration, and parent sync frequency. Prefer trends and counseling context over single-day extremes.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
            @endif
        </div>

        <div class="tab-pane fade" id="historyPane" role="tabpanel" aria-labelledby="history-tab">
            <div class="panel p-4">
                <h2 class="h5 brand mb-3">Your viewing history</h2>
                <p class="text-muted small mb-3">Past temporary access sessions you opened with a parent OTP/QR. Active sessions remain open until you or the parent end them.</p>
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

        <div class="tab-pane fade {{ $openSettings ? 'show active' : '' }}" id="settingsPane" role="tabpanel" aria-labelledby="settings-tab">
            @if(session('success'))
                <div class="alert alert-success mb-4" role="alert">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger mb-4" role="alert">
                    <ul class="mb-0 ps-3">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('doctor.settings.update') }}" class="doctor-settings-form">
                @csrf

                <div class="panel p-4 p-md-5 mb-4">
                    <h2 class="h5 brand mb-2">Clinician Account</h2>
                    <p class="text-muted small mb-4">Same account &amp; security controls parents get on mobile — view your email, update profile details, and reset your password.</p>

                    <div class="d-flex flex-wrap align-items-center gap-3 mb-4 p-3 rounded-4 border" style="border-width: 3px !important;">
                        <i class="bi bi-envelope" style="font-size: 1.35rem;"></i>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">{{ $doctor->email }}</div>
                            <div class="small text-muted">
                                @if($doctor->email_verified_at)
                                    Verified {{ $doctor->email_verified_at->format('M d, Y') }}
                                @else
                                    Pending administrator verification
                                @endif
                            </div>
                        </div>
                        <span class="badge {{ $doctor->email_verified_at ? 'bg-success' : 'bg-secondary' }}">
                            {{ $doctor->email_verified_at ? 'Verified' : 'Unverified' }}
                        </span>
                    </div>
                    <p class="form-text otp-hint mb-0">Email is shown for reference (like parent accounts). Contact an administrator if you need to change the login email.</p>
                </div>

                <div class="panel p-4 p-md-5 mb-4">
                    <h2 class="h5 brand mb-2">Profile</h2>
                    <p class="text-muted small mb-4">Update the professional details from your clinician signup.</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="first_name">First Name</label>
                            <input id="first_name" type="text" name="first_name" class="form-control" value="{{ old('first_name', $doctor->first_name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="last_name">Last Name</label>
                            <input id="last_name" type="text" name="last_name" class="form-control" value="{{ old('last_name', $doctor->last_name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="phone">Phone Number</label>
                            <input id="phone" type="text" name="phone" class="form-control" value="{{ $profilePhone }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="specialty">Specialty</label>
                            <input id="specialty" type="text" name="specialty" class="form-control" value="{{ $profileSpecialty }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="clinic">Clinic</label>
                            <input id="clinic" type="text" name="clinic" class="form-control" value="{{ $profileClinic }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="license_number">License Number</label>
                            <input id="license_number" type="text" name="license_number" class="form-control" value="{{ $profileLicense }}" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="location">Location</label>
                            <input id="location" type="text" name="location" class="form-control" value="{{ $profileLocation }}" placeholder="Optional">
                        </div>
                    </div>
                </div>

                <div class="panel p-4 p-md-5 mb-4">
                    <h2 class="h5 brand mb-2">Reset Password</h2>
                    <p class="text-muted small mb-4">Leave blank to keep your current password. Requires your current password to confirm a change.</p>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="current_password">Current Password</label>
                            <input id="current_password" type="password" name="current_password" class="form-control" autocomplete="current-password">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="new_password">New Password</label>
                            <input id="new_password" type="password" name="new_password" class="form-control" autocomplete="new-password" minlength="8">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="new_password_confirmation">Confirm Password</label>
                            <input id="new_password_confirmation" type="password" name="new_password_confirmation" class="form-control" autocomplete="new-password" minlength="8">
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-success ds-btn-primary px-4">Save Account Settings</button>
            </form>
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
<script>
(function presenceHeartbeat() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    if (!csrf) return;
    const ping = () => {
        if (document.visibilityState !== 'visible') return;
        fetch(@json(route('doctor.presence')), {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            keepalive: true,
        }).catch(() => {});
    };
    ping();
    setInterval(ping, 60000);
    document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible') ping();
    });
})();
</script>
</body>
</html>
