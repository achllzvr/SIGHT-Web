<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - LUMI</title>
    @include('partials.ds-head')
    @include('partials.ds-arcade-head')
    <style>
        body.lumi-arcade { margin: 0; min-height: 100vh; }
        .wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card {
            width: 100%; max-width: 460px; background: #fff; border-radius: 24px; border: 4px solid #d4d4d4;
            box-shadow: 0 6px 0 0 #d4d4d4; padding: 28px;
        }
        h1 { margin: 0 0 8px; font-family: SuperJoyful, ClanPro, sans-serif; text-transform: uppercase; font-size: 1.6rem; color: #8168ab; }
        p { margin: 0 0 18px; color: #6b6b6b; }
        label { display: block; font-weight: 600; margin-bottom: 6px; color: #2d2d2d; }
        input {
            width: 100%; box-sizing: border-box; border: 4px solid #d4d4d4; border-radius: 9999px;
            padding: 12px 16px; margin-bottom: 14px; font-family: ClanPro, sans-serif;
        }
        input:focus { outline: none; border-color: #8168ab; }
        button {
            width: 100%; border: 4px solid #8168ab; border-radius: 9999px; padding: 12px;
            background: #ffdcf9; color: #8168ab; font-weight: 700; cursor: pointer;
            text-transform: uppercase; letter-spacing: 0.06em; box-shadow: 0 4px 0 0 #8168ab;
        }
        .errors { background: #ffe4e6; color: #f43f5e; border: 3px solid #f43f5e; border-radius: 16px; padding: 10px 12px; margin-bottom: 12px; }
        a { display: inline-block; margin-top: 14px; color: #8168ab; text-decoration: none; font-weight: 600; }
        .modal-backdrop {
            position: fixed; inset: 0; background: rgba(45,45,45,0.45); display: flex; align-items: center; justify-content: center;
            z-index: 50; padding: 24px;
        }
        .modal-card {
            width: 100%; max-width: 400px; background: #fff; border: 4px solid #62b239; border-radius: 24px;
            box-shadow: 0 6px 0 0 #62b239; padding: 28px; text-align: center;
        }
        .modal-card h2 { margin: 0 0 10px; font-family: SuperJoyful, ClanPro, sans-serif; text-transform: uppercase; color: #8168ab; font-size: 1.35rem; }
        .modal-card p { margin: 0 0 8px; }
        .modal-card .hint { color: #9a9a9a; font-size: 0.85rem; margin-top: 12px; }
    </style>
</head>
<body class="lumi-arcade">
@php $showSuccess = session('password_reset_success'); @endphp
<div class="wrap">
    <div class="card">
        <h1>Reset Password</h1>
        <p>Set your new password below.</p>

        @if($errors->any())
            <div class="errors">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        @unless($showSuccess)
        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <label for="email">Email Address</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required>

            <label for="password">New Password</label>
            <input id="password" name="password" type="password" required>

            <label for="password_confirmation">Confirm New Password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required>

            <button type="submit">Reset Password</button>
        </form>
        @else
        <p>Your password was updated successfully.</p>
        @endunless

        <a href="{{ route('login') }}">Back to Login</a>
    </div>
</div>

@if($showSuccess)
<div class="modal-backdrop" id="successModal" role="dialog" aria-modal="true" aria-labelledby="successTitle">
    <div class="modal-card">
        <h2 id="successTitle">Password Updated</h2>
        <p>Your password has been reset. You can now sign in with your new password.</p>
        <p class="hint">Redirecting to login in <span id="countdown">4</span>s…</p>
    </div>
</div>
<script>
    let n = 4;
    const el = document.getElementById('countdown');
    const loginUrl = @json(route('login'));
    setInterval(() => {
        n -= 1;
        if (el) el.textContent = String(Math.max(n, 0));
        if (n <= 0) window.location.href = loginUrl;
    }, 1000);
</script>
@endif
</body>
</html>
