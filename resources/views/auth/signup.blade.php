<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @include('partials.ds-head')
    <style>
        body {
            font-family: var(--ds-font-sans), system-ui, -apple-system, sans-serif;
            background-color: #fafafa;
            margin: 0;
            overflow-x: hidden;
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
            z-index: -1;
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

        .container-wrapper {
            min-height: calc(100vh - 120px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            width: 100%;
            max-width: 1100px;
            margin: 0 auto;
            z-index: 1;
        }

        .card-container {
            width: 100%;
            max-width: 540px;
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            border-radius: var(--ds-radius-xl);
            box-shadow: 0 32px 120px rgba(0, 0, 0, 0.12);
            overflow: hidden;
            min-height: 640px;
            display: flex;
            flex-direction: column;
        }
        
        .card-header {
            padding: 3rem 2.5rem 1.5rem;
            text-align: left;
        }
        
        .steps-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 2rem;
        }
        
        .step-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
        }
        
        .step-circle {
            width: 2rem;
            height: 2rem;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .step-circle.active {
            background-color: #527267;
        }
        
        .step-circle.inactive {
            background-color: #d1d5db;
        }
        
        .step-label {
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .step-line {
            flex: 1;
            height: 0.25rem;
            border-radius: 9999px;
            margin: 0 0.5rem;
        }
        
        .step-line.active {
            background-color: #527267;
        }
        
        .step-line.inactive {
            background-color: #d1d5db;
        }
        
        .card-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .card-description {
            font-size: 0.875rem;
            color: #6b7280;
        }
        
        .card-content {
            padding: 0 2.5rem 2.5rem 2.5rem;
            flex: 1;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.75rem;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #000;
            border-radius: 9999px;
            height: 3rem;
            font-size: 1rem;
            box-sizing: border-box;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #527267;
        }
        
        .password-wrapper {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #9ca3af;
            padding: 0.25rem;
        }
        
        .password-toggle:hover {
            color: #374151;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .button-row {
            display: flex;
            justify-content: center;
            gap: 1rem;
            padding-top: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .button-single {
            display: flex;
            justify-content: center;
            padding-top: 0.25rem;
            margin-bottom: 0.5rem;
        }
        
        .btn {
            padding: 0.75rem 2.5rem;
            font-size: 1rem;
            font-weight: 600;
            border: none;
            border-radius: 9999px;
            cursor: pointer;
            transition: all 0.2s;
            width: 10rem;
        }
        
        .btn-primary {
            background-color: #527267;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: rgba(82, 114, 103, 0.9);
        }
        
        .btn-secondary {
            background-color: white;
            color: #527267;
            border: 2px solid #527267;
        }
        
        .btn-secondary:hover {
            background-color: transparent;
            border-color: rgba(82, 114, 103, 0.9);
            color: rgba(82, 114, 103, 0.9);
        }
        
        .signin-link {
            text-align: center;
            font-size: 0.875rem;
            color: #6b7280;
            margin-top: 1rem;
        }
        
        .signin-link a {
            color: #527267;
            text-decoration: none;
            font-weight: 600;
        }
        
        .signin-link a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            color: #dc2626;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        .form-errors {
            background: rgba(220, 38, 38, 0.08);
            border: 1px solid rgba(220, 38, 38, 0.15);
            color: #b91c1c;
            border-radius: var(--ds-radius-md);
            padding: 1.25rem;
            font-size: 0.95rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(220, 38, 38, 0.05);
        }
        
        .form-errors ul {
            margin: 0;
            padding-left: 1.5rem;
        }
        
        .form-errors li {
            margin-bottom: 0.25rem;
        }
    </style>
</head>
<body>
    <section class="hero-section">
        <div class="bg-lumi-text">LUMI</div>
        <div class="container-wrapper">
            <div class="card-container">
            <div class="card-header">
                <div class="steps-container">
                    <div class="step-item">
                        <div class="step-circle {{ session('signup_step') == 'account' || session('signup_step') === null ? 'active' : 'inactive' }}">
                            1
                        </div>
                        <span class="step-label">Account Info</span>
                    </div>

                    <div class="step-line {{ session('signup_step') == 'professional' ? 'active' : 'inactive' }}"></div>

                    <div class="step-item">
                        <div class="step-circle {{ session('signup_step') == 'professional' ? 'active' : 'inactive' }}">
                            2
                        </div>
                        <span class="step-label">Professional Info</span>
                    </div>
                </div>

                <div>
                    <h1 class="card-title" id="stepTitle">Create Account</h1>
                    <p class="card-description" id="stepDescription">Set up your account to get started</p>
                </div>
            </div>

            <div class="card-content">
                @if ($errors->any())
                    <div class="form-errors">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="signupForm" method="POST" action="{{ route('signup.store') }}">
                    @csrf

                    <!-- Account Info Tab -->
                    <div id="accountTab" class="tab-content active">
                        <div class="form-group-row">
                            <div class="form-group">
                                <label for="first_name" class="form-label">First Name</label>
                                <input 
                                    type="text" 
                                    id="first_name" 
                                    name="first_name" 
                                    placeholder="John"
                                    class="form-input"
                                    value="{{ old('first_name') }}"
                                    required
                                >
                                @error('first_name')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input 
                                    type="text" 
                                    id="last_name" 
                                    name="last_name" 
                                    placeholder="Doe"
                                    class="form-input"
                                    value="{{ old('last_name') }}"
                                    required
                                >
                                @error('last_name')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                placeholder="name@example.com"
                                class="form-input"
                                value="{{ old('email') }}"
                                required
                            >
                            @error('email')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group-row">
                            <div class="form-group">
                                <label for="password" class="form-label">Password</label>
                                <div class="password-wrapper">
                                    <input 
                                        type="password" 
                                        id="password" 
                                        name="password" 
                                        placeholder="••••••••"
                                        class="form-input"
                                        required
                                    >
                                    <button 
                                        type="button" 
                                        class="password-toggle"
                                        onclick="togglePassword('password')"
                                    >
                                        <span id="passwordIcon">👁️</span>
                                    </button>
                                </div>
                                @error('password')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                <input 
                                    type="password" 
                                    id="password_confirmation" 
                                    name="password_confirmation" 
                                    placeholder="••••••••"
                                    class="form-input"
                                    required
                                >
                                @error('password_confirmation')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="button-single">
                            <button type="button" class="btn btn-primary" onclick="nextStep()">
                                Continue
                            </button>
                        </div>
                    </div>

                    <!-- Professional Info Tab -->
                    <div id="professionalTab" class="tab-content">
                        <div class="form-group">
                            <label for="phone" class="form-label">Phone Number</label>
                            <input 
                                type="tel" 
                                id="phone" 
                                name="phone" 
                                placeholder="Phone number"
                                class="form-input"
                                value="{{ old('phone') }}"
                                required
                            >
                            @error('phone')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="clinic" class="form-label">Hospital / Clinic</label>
                            <input 
                                type="text" 
                                id="clinic" 
                                name="clinic" 
                                placeholder="Hospital or Clinic name"
                                class="form-input"
                                value="{{ old('clinic') }}"
                                required
                            >
                            @error('clinic')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group-row">
                            <div class="form-group">
                                <label for="specialty" class="form-label">Specialty</label>
                                <input 
                                    type="text" 
                                    id="specialty" 
                                    name="specialty" 
                                    placeholder="Optometrist / Ophthalmologist"
                                    class="form-input"
                                    value="{{ old('specialty') }}"
                                    required
                                >
                                @error('specialty')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="license_number" class="form-label">License Number</label>
                                <input 
                                    type="text" 
                                    id="license_number" 
                                    name="license_number" 
                                    placeholder="Enter license number"
                                    class="form-input"
                                    value="{{ old('license_number') }}"
                                    required
                                >
                                @error('license_number')
                                    <div class="error-message">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="location" class="form-label">Location</label>
                            <input 
                                type="text" 
                                id="location" 
                                name="location" 
                                placeholder="City / Region"
                                class="form-input"
                                value="{{ old('location') }}"
                            >
                            @error('location')
                                <div class="error-message">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="button-row">
                            <button type="button" class="btn btn-secondary" onclick="prevStep()">
                                Back
                            </button>
                            <button type="submit" class="btn btn-primary">
                                Complete Setup
                            </button>
                        </div>
                    </div>

                    <input type="hidden" id="stepInput" name="step" value="account">
                </form>

                <div class="signin-link">
                    Already have an account?
                    <a href="{{ route('doctor.login') }}">Sign in</a>
                </div>
            </div>
        </div>
    </div>
    </section>

    <script>
        function togglePassword(fieldId) {
            const input = document.getElementById(fieldId);
            const icon = document.getElementById('passwordIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.textContent = '🙈';
            } else {
                input.type = 'password';
                icon.textContent = '👁️';
            }
        }

        function nextStep() {
            const firstName = document.getElementById('first_name').value.trim();
            const lastName = document.getElementById('last_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const passwordConfirm = document.getElementById('password_confirmation').value;

            if (!firstName || !lastName || !email || !password || !passwordConfirm) {
                alert('Please fill in all account fields');
                return;
            }

            if (password !== passwordConfirm) {
                alert('Passwords do not match');
                return;
            }

            showProfessionalTab();
        }

        function prevStep() {
            showAccountTab();
        }

        function showAccountTab() {
            document.getElementById('accountTab').classList.add('active');
            document.getElementById('professionalTab').classList.remove('active');
            document.getElementById('stepTitle').textContent = 'Create Account';
            document.getElementById('stepDescription').textContent = 'Set up your account to get started';
            document.getElementById('stepInput').value = 'account';
            updateStepIndicators('account');
        }

        function showProfessionalTab() {
            document.getElementById('accountTab').classList.remove('active');
            document.getElementById('professionalTab').classList.add('active');
            document.getElementById('stepTitle').textContent = 'Professional Information';
            document.getElementById('stepDescription').textContent = 'Complete your profile details';
            document.getElementById('stepInput').value = 'professional';
            updateStepIndicators('professional');
        }

        function updateStepIndicators(step) {
            const circles = document.querySelectorAll('.step-circle');
            const line = document.querySelector('.step-line');

            if (step === 'account') {
                circles[0].classList.add('active');
                circles[0].classList.remove('inactive');
                circles[1].classList.add('inactive');
                circles[1].classList.remove('active');
                line.classList.add('inactive');
                line.classList.remove('active');
            } else {
                circles[0].classList.add('active');
                circles[0].classList.remove('inactive');
                circles[1].classList.add('active');
                circles[1].classList.remove('inactive');
                line.classList.add('active');
                line.classList.remove('inactive');
            }
        }

        const heroSection = document.querySelector('.hero-section');
        const cardContainer = document.querySelector('.card-container');
        const bgLumiText = document.querySelector('.bg-lumi-text');

        if (heroSection && cardContainer && bgLumiText) {
            const toggleLumiDark = (event) => {
                bgLumiText.classList.toggle('hero-dark', !cardContainer.contains(event.target));
            };

            heroSection.addEventListener('mousemove', toggleLumiDark);
            heroSection.addEventListener('mouseleave', () => {
                bgLumiText.classList.remove('hero-dark');
            });
            cardContainer.addEventListener('mouseenter', () => {
                bgLumiText.classList.remove('hero-dark');
            });
            cardContainer.addEventListener('mouseleave', () => {
                bgLumiText.classList.add('hero-dark');
            });
        }
    </script>
</body>
</html>
