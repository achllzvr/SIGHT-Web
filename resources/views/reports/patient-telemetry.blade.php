<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LUMI Telemetry Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        h2 { font-size: 14px; margin-top: 18px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        .meta { color: #555; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; }
        .warn { color: #b45309; font-weight: bold; }
        .note { color: #555; font-size: 10px; line-height: 1.45; margin-top: 8px; }
        .guide { background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px 12px; margin-top: 12px; font-size: 10px; line-height: 1.45; color: #334155; }
        .guide strong { color: #0f172a; }
    </style>
</head>
<body>
    <h1>LUMI Eye Health Telemetry Snapshot</h1>
    <div class="meta">
        Generated {{ $generatedAt->format('M d, Y g:i A') }}<br>
        Clinician: {{ $clinician->display_name ?? 'N/A' }}<br>
        Patient: {{ $child->user->display_name ?? 'Unknown' }} (ID {{ $child->child_id }})<br>
        Session #{{ $session->id }} &middot; Accessed {{ optional($session->accessed_at)->format('M d, Y g:i A') }}
    </div>

    <div class="guide">
        <strong>About this report.</strong>
        Data originates from the child’s LUMI mobile app. During Watch sessions, on-device face tracking estimates blink rate and viewing distance; the app curates short metric windows and the parent syncs them to LUMI.
        Figures below summarize the <strong>rolling last 7 calendar days</strong>. This is contextual support for counseling on near-work habits—not a substitute for clinical examination or refraction.
    </div>

    <h2>Summary</h2>
    <table>
        <tr><th>Health Grade</th><td>{{ $telemetry['health_grade'] ?? '—' }}</td></tr>
        <tr><th>Health Score</th><td>{{ $telemetry['health_score'] ?? '—' }}</td></tr>
        <tr><th>Avg Blink Rate</th><td>{{ $telemetry['average_blink_rate'] ?? '—' }} /min</td></tr>
        <tr><th>Avg Viewing Distance</th><td>{{ $telemetry['average_distance'] ?? '—' }} cm</td></tr>
        <tr><th>Avg Screen Time</th><td>{{ $telemetry['average_screen_time'] ?? '—' }} min</td></tr>
        <tr>
            <th>Distance Violations (&lt; 30 cm)</th>
            <td class="warn">{{ $telemetry['distance_violations'] ?? 0 }}</td>
        </tr>
        <tr><th>Low Blink Days (&lt; 12 /min)</th><td>{{ $telemetry['low_blink_events'] ?? 0 }}</td></tr>
    </table>
    <p class="note">
        <strong>Health grade bands:</strong> Excellent ≥90 · Good ≥80 · Fair ≥70 · Needs Attention &lt;70 (from latest/average health score).<br>
        <strong>Violations / low-blink days:</strong> counts of days whose daily average fell below the threshold—not per-second episode counts.<br>
        <strong>Screen time:</strong> minutes of LUMI Watch tracking only (may under-represent total device use).
    </p>

    <h2>7-Day Distance Trend (cm)</h2>
    <table>
        <thead>
            <tr>
                <th>Day</th>
                @foreach(($telemetry['labels'] ?? []) as $label)
                    <th>{{ $label }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Avg Distance</td>
                @foreach(($telemetry['distances'] ?? []) as $d)
                    <td>{{ $d === null ? '—' : $d }}</td>
                @endforeach
            </tr>
            <tr>
                <td>Blink Rate (/min)</td>
                @foreach(($telemetry['blink_rates'] ?? []) as $b)
                    <td>{{ $b === null ? '—' : $b }}</td>
                @endforeach
            </tr>
            <tr>
                <td>Screen Time (min)</td>
                @foreach(($telemetry['screen_times'] ?? []) as $s)
                    <td>{{ $s === null ? '—' : $s }}</td>
                @endforeach
            </tr>
            <tr>
                <td>Strain Events</td>
                @foreach(($telemetry['strain_events'] ?? []) as $e)
                    <td>{{ $e === null ? '—' : $e }}</td>
                @endforeach
            </tr>
            <tr>
                <td>Health Score</td>
                @foreach(($telemetry['health_scores'] ?? []) as $h)
                    <td>{{ $h === null ? '—' : $h }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>
    <p class="note">
        Daily values are averages (or sums for strain events) of synced metric windows for that calendar day. Blank cells mean no synced data that day.
    </p>

    <h2>Metric guide</h2>
    <table>
        <tr>
            <th>Metric</th>
            <th>Meaning &amp; calculation</th>
            <th>Clinical relevance</th>
        </tr>
        <tr>
            <td>Blink rate</td>
            <td>Estimated blinks/min from facial landmarks during Watch tracking; daily average of synced windows.</td>
            <td>Low rates can increase tear evaporation and digital eye strain symptoms; useful for blink-exercise counseling.</td>
        </tr>
        <tr>
            <td>Viewing distance</td>
            <td>Estimated face-to-device distance (cm). Portal warning zone: &lt; 30 cm (≈ &gt; 3.3 D demand).</td>
            <td>Sustained near focus is a near-work habit risk factor; supports posture and viewing-distance advice.</td>
        </tr>
        <tr>
            <td>Strain events</td>
            <td>Count of distance samples closer than 30 cm inside each metric window, summed per day.</td>
            <td>Higher counts suggest denser close-range exposure—not a medical diagnosis of “eye strain.”</td>
        </tr>
        <tr>
            <td>Health score</td>
            <td>App score 0–100. Drops with high accommodative demand / low blinks; recovers after healthy breaks/exercises.</td>
            <td>Falling trends flag accumulating near-work stress; rising trends suggest improving habits.</td>
        </tr>
    </table>

    <p style="margin-top: 24px; color: #666; font-size: 10px;">
        Harmful near-work zone threshold: 30 cm. Accuracy depends on camera angle, lighting, calibration, and parent sync frequency.
        Prefer multi-day trends over single-day extremes. Generated for on-site clinical review during a temporary parent-authorized access session.
    </p>
</body>
</html>
