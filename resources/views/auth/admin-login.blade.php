<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Eye Health Dashboard</title>
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
            padding: 48px 1.5rem 60px;
        }

        .hero-section::before {
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

        .hero-section::after {
            content: '';
            position: absolute;
            top: 28%;
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
            top: 32%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-family: var(--ds-font-sans), sans-serif;
            font-size: 30vw;
            font-weight: 900;
            color: #ecfdf5;
            z-index: -1;
            letter-spacing: 4px;
            user-select: none;
            opacity: 0.82;
            transition: all 0.6s ease;
            animation: floatLumi 8s ease-in-out infinite alternate;
            -webkit-animation: floatLumi 8s ease-in-out infinite alternate;
            -webkit-text-stroke: 1.5px rgba(255, 255, 255, 0.2);
            text-shadow:
                5px 15px 30px rgba(0, 0, 0, 0.05),
                -1px -1px 0 rgba(255, 255, 255, 0.4);
        }

        .hero-section:hover .bg-lumi-text {
            color: #9BB6A9;
            opacity: 1;
            -webkit-text-stroke: 1.5px rgba(255, 255, 255, 0.5);
        }

        .bg-lumi-text.hero-dark {
            color: #7f9f7d;
            opacity: 1;
            -webkit-text-stroke: 1.5px rgba(255, 255, 255, 0.65);
            text-shadow:
                5px 15px 40px rgba(0, 0, 0, 0.12),
                -1px -1px 0 rgba(255, 255, 255, 0.45);
        }

        .hero-grid {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 1300px;
            display: grid;
            grid-template-columns: minmax(320px, 1.15fr) minmax(320px, 0.85fr);
            align-items: flex-start;
            gap: 2.5rem;
        }

        .hero-left,
        .hero-right {
            position: relative;
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

        @keyframes floatLumi {
            from {
                transform: translate(-50%, -50%) translateY(-12px) scale(1);
            }
            to {
                transform: translate(-50%, -50%) translateY(12px) scale(1.02);
            }
        }

        .login-container {
            width: 100%;
            max-width: 540px;
            margin: 0 auto;
            transition: transform 0.25s ease;
            transform-style: preserve-3d;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            border-radius: var(--ds-radius-xl);
            box-shadow: 0 32px 120px rgba(0, 0, 0, 0.12);
            overflow: hidden;
            min-height: 640px;
            display: flex;
            flex-direction: column;
            position: relative;
            animation: fadeInUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
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
            padding: 3rem 2.5rem 1.5rem;
            background: transparent;
        }

        .login-logo {
            width: 56px;
            height: 56px;
            background-color: var(--primary-green);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.35rem;
            margin-bottom: 0.75rem;
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

        .login-card .card-body {
            padding: 2.5rem;
            flex: 1;
        }

        .form-control {
            border: 1px solid var(--ds-border);
            border-radius: var(--ds-radius-md);
            height: 40px;
            padding: 0 1.25rem;
            font-size: 0.95rem;
            background: rgba(255,255,255,0.9);
        }

        .form-control:focus {
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.2rem rgba(82, 114, 103, 0.12);
            outline: none;
        }

        .forgot-password,
        .signup-link {
            color: var(--primary-green);
        }

        .btn-signin {
            width: 100%;
            max-width: 240px;
            background-color: var(--primary-green);
            color: white;
            font-weight: 700;
            border-radius: var(--ds-radius-md);
            padding: 0.85rem 1.5rem;
            border: none;
            transition: all 0.2s ease;
        }

        .btn-signin:hover {
            background-color: var(--ds-brand-600);
        }

        .button-container {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5rem;
        }

        .signup-text {
            text-align: center;
            color: #5f6a70;
            font-size: 0.95rem;
            margin-top: 1.25rem;
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
                border-radius: var(--ds-radius-xl);
            }

            .login-card .card-header {
                padding-top: 2rem;
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
                            <div class="login-logo">A</div>
                            <h1 class="card-title">Admin Login</h1>
                            <p class="card-text">Enter your credentials to access your admin dashboard</p>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('admin.login.submit') }}" method="POST">
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

                            <div class="signup-text">
                                Don't have an account?
                                <a href="{{ route('signup') }}" class="signup-link">Sign up</a>
                            </div>
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
