<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SIGHT Telemetry Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1a1a1a; }
        h1 { font-size: 20px; margin-bottom: 4px; }
        h2 { font-size: 14px; margin-top: 18px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        .meta { color: #555; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; }
        .warn { color: #b45309; font-weight: bold; }
    </style>
</head>
<body>
    <h1>SIGHT Eye Health Telemetry Snapshot</h1>
    <div class="meta">
        Generated {{ $generatedAt->format('M d, Y g:i A') }}<br>
        Clinician: {{ $clinician->display_name ?? 'N/A' }}<br>
        Patient: {{ $child->user->display_name ?? 'Unknown' }} (ID {{ $child->child_id }})<br>
        Session #{{ $session->id }} &middot; Accessed {{ optional($session->accessed_at)->format('M d, Y g:i A') }}
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
        <tr><th>Low Blink Events</th><td>{{ $telemetry['low_blink_events'] ?? 0 }}</td></tr>
    </table>

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
        </tbody>
    </table>

    <p style="margin-top: 24px; color: #666; font-size: 10px;">
        Harmful near-work zone threshold: 30 cm. This report is generated for on-site clinical review during a temporary access session.
    </p>
</body>
</html>
