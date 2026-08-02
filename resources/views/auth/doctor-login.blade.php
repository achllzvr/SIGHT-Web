<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Login - Eye Health Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @include('partials.ds-head')
    <style>
        :root {
            --primary-green: var(--ds-brand-500);
            --bg-light: #f8fafc;
        }
        body {
            /* Copied from welcome.blade.php .hero-section background */
            background:
                radial-gradient(circle at 90% 50%, rgba(91, 154, 122, 0.14) 0%, transparent 35%),
                radial-gradient(circle at 50% 50%, #ecfdf5 0%, transparent 60%),
                radial-gradient(circle at 15% 20%, rgba(245, 158, 11, 0.12) 0%, transparent 20%);
            font-family: var(--ds-font-sans);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative; /* Needed for pseudo-elements */
            overflow: hidden; /* Hide overflow from pseudo-elements */
        }
        body::before { /* Copied from welcome.blade.php .hero-section::before */
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            width: 100%;
            height: 800px;
            background: #f8fafc;
            border-radius: 0 0 50% 50%;
            z-index: -2;
            pointer-events: none;
        }
        body::after { /* Copied from welcome.blade.php .hero-section::after */
            content: '';
            position: absolute;
            top: 30%;
            left: 65%;
            width: 400px;
            height: 400px;
            border-radius: 100%;
            background: radial-gradient(circle, rgba(222, 251, 225, 0.52) 00%, transparent 70%);
            z-index: 5;
            pointer-events: none;
        }
        .login-container {
            width: 100%;
            max-width: 540px;
            padding: 1rem;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            border-radius: var(--ds-radius-xl);
            box-shadow: 0 32px 120px rgba(0, 0, 0, 0.12);
            min-height: 640px;
            position: relative; /* Ensure z-index works */
            z-index: 10; /* Bring card to front */
            display: flex;
            flex-direction: column;
        }
        .alert-danger {
            background: rgba(220, 38, 38, 0.08);
            border: 1px solid rgba(220, 38, 38, 0.15);
            color: #b91c1c;
            border-radius: var(--ds-radius-md);
            padding: 1.25rem;
            font-size: 0.95rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 10px 30px rgba(220, 38, 38, 0.05);
        }
        .login-card .card-header {
            border: none;
            padding: 2.5rem 2.5rem 1.5rem;
            background: white;
            border-radius: 1rem 1rem 0 0;
        }
        .login-logo {
            width: 48px;
            height: 48px;
            background-color: var(--primary-green);
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }
        .login-card .card-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }
        .login-card .card-text {
            color: #6b7280;
            font-size: 0.875rem;
            margin: 0;
        }
        .login-card .card-body {
            padding: 2.5rem;
            overflow-y: auto;
            flex: 1;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            font-size: 0.875rem;
            font-weight: 500;
            color: #1f2937;
            margin-bottom: 0.75rem;
            display: block;
        }
        .form-control {
            border: 2px solid #000;
            border-radius: var(--ds-radius-md);
            height: 48px;
            padding: 0 1.25rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        .form-control:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.2rem rgba(82, 114, 103, 0.1);
            outline: none;
        }
        .password-wrapper {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6b7280;
            cursor: pointer;
            padding: 0.5rem;
        }
        .password-toggle:hover {
            color: #1f2937;
        }
        .checkbox-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            gap: 1rem;
        }
        .form-check {
            margin: 0;
        }
        .form-check-input {
            width: 18px;
            height: 18px;
            margin-top: 0.125rem;
            cursor: pointer;
            border: 2px solid #d1d5db;
        }
        .form-check-input:checked {
            background-color: var(--primary-green);
            border-color: var(--primary-green);
        }
        .form-check-label {
            font-size: 0.875rem;
            font-weight: normal;
            color: #1f2937;
            cursor: pointer;
            margin: 0;
        }
        .forgot-password {
            font-size: 0.875rem;
            color: var(--primary-green);
            text-decoration: none;
            font-weight: 500;
        }
        .forgot-password:hover {
            text-decoration: underline;
            color: var(--primary-green);
        }
        .btn-signin {
            width: 160px;
            background-color: var(--primary-green);
            color: white;
            font-weight: 600;
            border-radius: var(--ds-radius-md);
            padding: 0.75rem 2rem;
            border: none;
            transition: all 0.2s;
        }
        .btn-signin:hover {
            background-color: #3f5a52;
            color: white;
        }
        .button-container {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        .signup-text {
            text-align: center;
            color: #6b7280;
            font-size: 0.875rem;
            margin-top: auto;
        }
        .signup-link {
            color: var(--primary-green);
            text-decoration: none;
            font-weight: 600;
        }
        .signup-link:hover {
            text-decoration: underline;
            color: var(--primary-green);
        }
        .error-message {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
    </style>
    @include('partials.ds-arcade-head')
</head>
<body class="lumi-arcade">
    <div class="bg-lumi-text">LUMI</div>
    <div class="login-container">
        <div class="card login-card">
            <div class="card-header">
                <div class="login-logo">E</div>
                <h1 class="card-title">Welcome Back</h1>
                <p class="card-text">Enter your credentials to access your account</p>
            </div>

            <div class="card-body">
                <form action="{{ route('doctor.login.submit') }}" method="POST">
                    @csrf

                    @if ($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            @foreach ($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-control @error('email') is-invalid @enderror"
                            placeholder="name@example.com"
                            value="{{ old('email') }}"
                        >
                        @error('email')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="password-wrapper">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-control @error('password') is-invalid @enderror"
                                placeholder="••••••••"
                            >
                            <button
                                type="button"
                                class="password-toggle"
                                onclick="togglePassword()"
                            >
                                <i class="bi bi-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="error-message">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="checkbox-wrapper">
                        <div class="form-check">
                            <input
                                type="checkbox"
                                id="remember"
                                name="remember"
                                class="form-check-input"
                            >
                            <label class="form-check-label" for="remember">
                                Remember me
                            </label>
                        </div>
                        <a href="{{ route('password.request') }}" class="forgot-password">Forgot Password?</a>
                    </div>

                <div class="button-container">
                        <button type="submit" class="btn btn-signin">Sign In</button>
                </div>
                </form>

                <div class="signup-text">
                    Don't have an account?
                    <a href="{{ route('signup') }}" class="signup-link">Sign up</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('bi-eye');
                eyeIcon.classList.add('bi-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('bi-eye-slash');
                eyeIcon.classList.add('bi-eye');
            }
        }
    </script>
</body>
</html>
