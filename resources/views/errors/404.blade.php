<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found — LUMI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7f5; font-family: system-ui, sans-serif; min-height: 100vh; display: flex; align-items: center; }
        .card-404 { background: #fff; border: 1px solid #dde5e0; border-radius: 20px; padding: 2.5rem; max-width: 560px; margin: 2rem auto; text-align: center; }
        .code { font-size: 4rem; font-weight: 800; color: #527267; line-height: 1; }
    </style>
</head>
<body>
<div class="container">
    <div class="card-404 shadow-sm">
        <div class="code">404</div>
        <h1 class="h4 mt-3">We can't find that page</h1>
        <p class="text-muted">
            The link may be old, or the page may have moved. Your LUMI data is safe —
            head back to your dashboard to keep going.
        </p>
        @php
            $user = auth()->user();
            $role = $user ? strtolower((string) $user->role) : null;
            if ($role === 'admin') {
                $homeUrl = route('admin.dashboard');
                $homeLabel = 'Back to Admin Dashboard';
            } elseif ($role === 'doctor') {
                $homeUrl = route('doctor.dashboard');
                $homeLabel = 'Back to Clinician Portal';
            } elseif ($role === 'guardian') {
                $homeUrl = route('guardian.dashboard');
                $homeLabel = 'Back to Guardian Dashboard';
            } else {
                $homeUrl = route('welcome');
                $homeLabel = 'Back to LUMI Home';
            }
        @endphp
        <a class="btn btn-success rounded-pill px-4" href="{{ $homeUrl }}">{{ $homeLabel }}</a>
        @guest
            <div class="mt-3">
                <a href="{{ route('login') }}">Sign in</a>
            </div>
        @endguest
    </div>
</div>
</body>
</html>
