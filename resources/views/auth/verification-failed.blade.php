<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Failed — LUMI</title>
    @include('partials.ds-head')
    @include('partials.ds-arcade-head')
    <style>
        body.lumi-arcade { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .verify-card {
            width: 100%; max-width: 460px; background: #fff; border: 4px solid #d4d4d4; border-radius: 24px;
            box-shadow: 0 6px 0 0 #d4d4d4; padding: 32px 28px; text-align: center;
        }
        .verify-card img { width: 72px; height: 72px; border-radius: 16px; border: 3px solid #8168ab; margin-bottom: 16px; }
        .verify-card h1 { font-family: SuperJoyful, ClanPro, sans-serif; text-transform: uppercase; color: #8168ab; font-size: 1.6rem; margin: 0 0 10px; }
        .verify-card p { color: #6b6b6b; margin: 0 0 22px; }
        .verify-card .btn {
            display: inline-flex; align-items: center; justify-content: center; min-height: 48px; padding: 0.65rem 1.4rem;
            border-radius: 9999px; border: 4px solid #62b239; background: #fff; color: #62b239; font-weight: 600;
            text-decoration: none; letter-spacing: 0.06em; text-transform: uppercase; box-shadow: 0 4px 0 0 #62b239;
        }
    </style>
</head>
<body class="lumi-arcade">
    <div class="verify-card">
        <img src="{{ asset('assets/lumi_app_icon.png') }}" alt="LUMI">
        <h1>Verification Failed</h1>
        <p>{{ $message ?? 'This verification link is invalid or has expired. Ask an administrator to verify your account, or request a new link.' }}</p>
        <a class="btn" href="{{ route('login') }}">Back to Login</a>
    </div>
</body>
</html>
