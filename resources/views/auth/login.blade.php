<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - Eye Health Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @include('partials.ds-head')
    <style>
        :root {
            --lumi-green: #eef9f1;
            --lumi-mint: #dcfce7;
            --lumi-purple: #a855f7;
            --bg-white: #ffffff;
            --text-main: var(--ds-text);
            --primary-green: var(--ds-brand-500);
        }

        body {
            font-family: var(--ds-font-sans);
            background-color: var(--bg-white);
            color: var(--text-main);
            margin: 0;
            overflow-x: hidden;
            min-height: 100vh;
        }

        .hero-section {
            position: relative;
            min-height: 100vh;
            background:
                radial-gradient(circle at 90% 50%, rgba(91, 154, 122, 0.14) 0%, transparent 35%),
                radial-gradient(circle at 50% 50%, #ecfdf5 0%, transparent 60%),
                radial-gradient(circle at 15% 20%, rgba(245, 158, 11, 0.12) 0%, transparent 20%);
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 80px 1.5rem 60px;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            top: 0;
            width: 100%;
            height: 620px;
            background: #f8fafc;
            border-radius: 0 0 50% 50%;
            z-index: -2;
            pointer-events: none;
        }

        .hero-section::after {
            content: '';
            position: absolute;
            top: 22%;
            left: 65%;
            width: 420px;
            height: 420px;
            border-radius: 100%;
            background: radial-gradient(circle, rgba(222, 251, 225, 0.52) 0%, transparent 70%);
            z-index: 5;
            pointer-events: none;
        }

        .bg-lumi-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-family: var(--ds-font-sans), sans-serif;
            font-size: 30vw;
            font-weight: 900;
            color: #ecfdf5;
            z-index: -1;
            letter-spacing: 25px;
            user-select: none;
            opacity: 0.82;
            transition: all 0.6s ease;
            -webkit-text-stroke: 1.5px rgba(255, 255, 255, 0.2);
            text-shadow:
                5px 15px 30px rgba(0, 0, 0, 0.05),
                -1px -1px 0 rgba(255, 255, 255, 0.4);
        }

        .bg-lumi-text.hero-dark {
            color: #C4DDB9;
            opacity: 1;
            -webkit-text-stroke: 1.5px rgba(255, 255, 255, 0.5);
            text-shadow:
                5px 15px 30px rgba(0, 0, 0, 0.05),
                -1px -1px 0 rgba(255, 255, 255, 0.4);
        }

        .hero-grid {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 1300px;
            display: grid;
            grid-template-columns: minmax(320px, 1.15fr) minmax(320px, 0.85fr);
            align-items: flex-start;
            justify-items: center;
            gap: 2.5rem;
            padding-top: 0.75rem;
        }

        .hero-left,
        .hero-right {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .hero-right {
            overflow: visible;
        }

        .phone-wrapper {
            position: relative;
            width: 100%;
            max-width: 680px;
            margin: -80px auto 0;
            transform-style: preserve-3d;
        }

        .phone-character {
            width: 100%;
            display: block;
            border-radius: var(--ds-radius-xl);
            transition: transform 0.25s ease, filter 0.25s ease;
            will-change: transform;
            filter: drop-shadow(0 30px 70px rgba(0, 0, 0, 0.12));
        }

        .button-glow {
            position: absolute;
            bottom: -48px;
            left: 50%;
            transform: translateX(-50%);
            width: 480px;
            height: 340px;
            border-radius: 100%;
            background: radial-gradient(circle, rgba(168, 85, 247, 0.18) 10%, transparent 70%);
            z-index: -1;
            pointer-events: none;
        }

        .mascot-side {
            position: absolute;
            width: 220px;
            text-align: center;
            opacity: 0;
            transform: scale(0);
            transition: opacity 0.4s ease, transform 0.5s cubic-bezier(0.34, 1.3, 0.64, 1);
        }

        .mascot-left {
            left: 8%;
            top: 16%;
            width: 150px;
        }

        .mascot-left-far {
            left: 24%;
            top: 46%;
            width: 150px;
        }

        .mascot-left-near {
            left: 18%;
            top: 60%;
            width: 100px;
        }

        .mascot-right-upper {
            right: 12%;
            top: 15%;
            width: 180px;
        }

        .mascot-right-lower {
            right: 18%;
            top: 48%;
width: 130px;
            z-index: 11;
        }

        .mascot-side img {
            width: 100%;
            height: auto;
            animation: mascotTilt 4s ease-in-out infinite;
            transform-origin: center bottom;
            transition: transform 0.3s ease-out;
        }

        .mascot-side img:hover {
            transform: scale(1.1) rotate(0deg);
            animation: none;
        }

        @keyframes mascotTilt {
            0%, 100% { transform: rotate(-3deg) translateY(0); }
            50% { transform: rotate(3deg) translateY(-8px); }
        }

        .login-container {
            width: 100%;
            max-width: 540px;
            margin: 0 auto;
            transition: transform 0.25s ease;
            transform-style: preserve-3d;
        }

        .login-card {
            background: #ffffff;
            border: 1px solid var(--ds-border);
            border-radius: var(--ds-radius-xl);
            box-shadow: var(--ds-shadow-card);
            overflow: hidden;
            min-height: 620px;
            max-height: 620px;
            display: flex;
            flex-direction: column;
            position: relative;
            animation: cardFadeIn 0.65s cubic-bezier(0.22, 1, 0.36, 1) both;
            transition: transform 150ms cubic-bezier(0.4, 0, 0.2, 1), box-shadow 150ms cubic-bezier(0.4, 0, 0.2, 1);
        }

        .login-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--ds-shadow-card-hover);
        }

        .login-card .card-body {
            padding: 2.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .login-card .alert-container {
            min-height: 100px;
            margin-bottom: 1rem;
        }

        .alert-danger {
            background: rgba(220, 38, 38, 0.08);
            border: 1px solid rgba(220, 38, 38, 0.15);
            color: #b91c1c;
            border-radius: var(--ds-radius-md);
            padding: 1.25rem;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            box-shadow: 0 10px 30px rgba(220, 38, 38, 0.05);
        }

        .login-card .card-header {
            border: none;
            padding: 3rem 2.5rem 1.25rem;
            background: transparent;
            text-align: left;
        }

        .login-logo {
            width: 56px;
            height: 56px;
            background-color: #ffdcf9;
            border: 3px solid #8168ab;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #8168ab;
            font-size: 1.35rem;
            margin-bottom: 0.75rem;
            margin-left: 0;
            margin-right: 0;
            overflow: hidden;
            padding: 0;
            box-shadow: 0 3px 0 0 #8168ab;
        }
        .login-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .login-card .card-title {
            font-size: 2rem;
            font-weight: 800;
            color: var(--ds-text);
            margin-bottom: 0.5rem;
        }

        .login-card .card-text {
            color: var(--ds-text-secondary);
            font-size: 0.95rem;
            margin: 0;
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper .form-control {
            padding-right: 3.25rem;
        }

        .password-toggle {
            position: absolute;
            top: 50%;
            right: 1rem;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #75807d;
            padding: 0.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease, transform 0.2s ease;
        }

        .password-toggle:hover {
            color: var(--primary-green);
            transform: translateY(-50%) scale(1.05);
        }

        .error-message {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0;
        }

        .form-check-input {
            margin-top: 0;
            border-color: rgba(82, 114, 103, 0.28);
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

        .form-control {
            border: 1px solid var(--ds-border);
            border-radius: var(--ds-radius-md);
            height: 40px;
            padding: 0 1.25rem;
            font-size: 0.95rem;
            background: rgba(255,255,255,0.9);
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 1px var(--ds-surface), 0 0 0 3px var(--ds-brand-500);
            outline: none;
        }

        .form-label {
            display: block;
            text-align: left;
            margin-bottom: 0.5rem;
        }

        .forgot-password,
        .signup-link {
            color: var(--primary-green);
            font-size: 0.875rem;
            text-decoration: none;
            font-weight: 500;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            gap: 1.5rem;
        }

        .btn-signin {
            width: 100%;
            background-color: var(--primary-green);
            color: white;
            font-weight: 600;
            border-radius: var(--ds-radius-md);
            padding: 0.5rem 1rem;
            min-height: 40px;
            border: none;
            transition: transform 150ms cubic-bezier(0.4, 0, 0.2, 1), background-color 150ms cubic-bezier(0.4, 0, 0.2, 1), box-shadow 150ms cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--ds-shadow-card);
        }

        .btn-signin:hover {
            background-color: var(--ds-brand-600);
            box-shadow: var(--ds-shadow-card-hover);
        }

        .btn-signin:active {
            transform: scale(0.97);
        }

        .btn-signin:focus-visible {
            outline: none;
            box-shadow: 0 0 0 2px var(--ds-surface), 0 0 0 4px var(--ds-brand-500);
        }

        .button-container {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        @keyframes cardFadeIn {
            from {
                opacity: 0;
                transform: translateY(14px) scale(0.985);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .signup-link:hover,
        .forgot-password:hover {
            text-decoration: underline;
        }

        @media (max-width: 992px) {
            .hero-grid {
                grid-template-columns: 1fr;
            }

            .hero-section {
                padding-top: 60px;
                padding-bottom: 80px;
            }

            .hero-section::after {
                top: 22%;
                left: 50%;
                width: 280px;
                height: 280px;
            }

            .bg-lumi-text {
                font-size: 40vw;
                top: -18%;
            }

            .mascot-side {
                display: none;
            }
        }

        @media (max-width: 560px) {
            .login-card {
                min-height: auto;
                max-height: none;
                border-radius: var(--ds-radius-xl);
            }

            .login-card .card-header {
                padding-top: 2rem;
            }

            .login-card .card-body {
                padding: 1.5rem;
            }

            .login-card .card-header {
                padding-left: 1.5rem;
                padding-right: 1.5rem;
            }

            .checkbox-wrapper {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.85rem;
            }

            .button-container {
                margin-bottom: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <section class="hero-section">
        <div class="bg-lumi-text">LUMI</div>

        <div class="mascot-side mascot-left">
            <img src="{{ asset('assets/7.png') }}" alt="Mascot Left">
        </div>

        <div class="mascot-side mascot-left-far">
            <img src="{{ asset('assets/11.png') }}" alt="Mascot Far Left">
        </div>

        <div class="mascot-side mascot-left-near">
            <img src="{{ asset('assets/10.png') }}" alt="Mascot Near Left">
        </div>

        <div class="mascot-side mascot-right-upper">
            <img src="{{ asset('assets/9.png') }}" alt="Mascot Upper Right">
        </div>

        <div class="mascot-side mascot-right-lower">
            <img src="{{ asset('assets/8.png') }}" alt="Mascot Lower Right">
        </div>

        <div class="hero-grid">
            <div class="hero-left">
                <div class="phone-wrapper">
                    <img src="{{ asset('assets/phone.png') }}" class="phone-character" alt="Phone Mascot">
                    <div class="button-glow"></div>
                </div>
            </div>

            <div class="hero-right">
                <div class="login-container">
                    <div class="card login-card">
                        <div class="card-header">
                            <div class="login-logo"><img src="{{ asset('assets/lumi_app_icon.png') }}" alt="LUMI" style="width: 100%; height: 100%; object-fit: cover;"></div>
                            <h1 class="card-title">Welcome Back</h1>
                            <p class="card-text">Sign in to your account to continue</p>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('login.submit') }}" method="POST">
                                @csrf

                                @if(session('info'))
                                    <div class="flash-message flash-info">{{ session('info') }}</div>
                                @endif

                                @if(session('success'))
                                    <div class="flash-message flash-success">{{ session('success') }}</div>
                                @endif

                                @if(session('error'))
                                    <div class="flash-message flash-error">{{ session('error') }}</div>
                                @endif

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
                                        required
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
                                            required
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

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

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

        const phoneCharacter = document.querySelector('.phone-character');
        const loginCard = document.querySelector('.login-card');

        document.addEventListener('mousemove', (e) => {
            if (window.innerWidth > 992) {
                const x = (e.clientX / window.innerWidth) * 2 - 1;
                const y = (e.clientY / window.innerHeight) * 2 - 1;
                if (phoneCharacter) {
                    phoneCharacter.style.transform = `perspective(1000px) rotateY(${x * 8}deg) rotateX(${y * 8}deg)`;
                }
                if (loginCard) {
                    loginCard.style.transform = `perspective(1000px) rotateY(${x * 3}deg) rotateX(${y * 3}deg)`;
                }
            }
        });

        document.addEventListener('mouseleave', () => {
            if (phoneCharacter) {
                phoneCharacter.style.transform = 'perspective(1000px) rotateX(0) rotateY(0)';
            }
            if (loginCard) {
                loginCard.style.transform = 'perspective(1000px) rotateX(0) rotateY(0)';
            }
        });

        const heroSection = document.querySelector('.hero-section');
        const bgLumiText = document.querySelector('.bg-lumi-text');

        if (heroSection && loginCard && bgLumiText) {
            heroSection.addEventListener('mousemove', (event) => {
                if (!loginCard.contains(event.target)) {
                    bgLumiText.classList.add('hero-dark');
                } else {
                    bgLumiText.classList.remove('hero-dark');
                }
            });

            heroSection.addEventListener('mouseleave', () => {
                bgLumiText.classList.remove('hero-dark');
            });
        }
    </script>
</body>
</html>
