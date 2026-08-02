<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - LUMI</title>
    @include('partials.ds-head')
    <style>
        body { font-family: Arial, sans-serif; background: #f3f4f6; margin: 0; }
        .wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { width: 100%; max-width: 460px; background: #fff; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,.08); padding: 28px; }
        h1 { margin: 0 0 8px; font-size: 24px; color: #111827; }
        p { margin: 0 0 18px; color: #6b7280; }
        label { display: block; font-weight: 600; margin-bottom: 6px; color: #374151; }
        input { width: 100%; box-sizing: border-box; border: 1px solid #d1d5db; border-radius: 8px; padding: 10px 12px; margin-bottom: 14px; }
        button { width: 100%; border: none; border-radius: 8px; padding: 11px 12px; background: #4b6059; color: #fff; font-weight: 700; cursor: pointer; }
        .status { background: #dcfce7; color: #166534; border-radius: 8px; padding: 10px 12px; margin-bottom: 12px; }
        .errors { background: #fee2e2; color: #991b1b; border-radius: 8px; padding: 10px 12px; margin-bottom: 12px; }
        a { display: inline-block; margin-top: 14px; color: #4b6059; text-decoration: none; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Forgot Password</h1>
        <p>Enter your account email and we will send a password reset link.</p>

        @if(session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="errors">
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <label for="email">Email Address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required>
            <button type="submit">Send Reset Link</button>
        </form>

        <a href="{{ route('login') }}">Back to Login</a>
    </div>
</div>
</body>
</html>
