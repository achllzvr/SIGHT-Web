<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LUMI - Eye Health</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @include('partials.ds-head')
    <style>
        :root {
            --lumi-green: #eef9f1;
            --lumi-mint: #dcfce7;
            --lumi-purple: #a855f7;
            --lumi-pink: #fbcfe8;
            --text-main: var(--ds-text);
        }

        body {
            font-family: var(--ds-font-sans), sans-serif;
            background-color: var(--ds-bg);
            margin: 0;
            overflow-x: hidden;
            color: var(--text-main);
        }

        /* Large LUMI Background Text */
        .bg-lumi-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 30vw;
            font-weight: 900;
            color: #ecfdf5;
            z-index: -1;
            letter-spacing: 25px;
            user-select: none;
            transition: all 0.6s ease;
            -webkit-text-stroke: 1.5px rgba(255, 255, 255, 0.2);
            text-shadow:
                5px 15px 30px rgba(0, 0, 0, 0.05),
                -1px -1px 0 rgba(255, 255, 255, 0.4);
        }

        /* Show text and mascots when hovered outside the phone (on hero section) */
        .hero-section:hover:not(:has(.phone-character:hover)) .bg-lumi-text {
            color: #C4DDB9;
            opacity: 1;
            -webkit-text-stroke: 1.5px rgba(255, 255, 255, 0.5);
        }

        .hero-section:hover:not(:has(.phone-character:hover)) .mascot-side {
            opacity: 1;
            transform: scale(1) translate(0, 0);
        }

        /* Hide mascots when specifically hovering the phone character */
        .hero-section:has(.phone-character:hover) .mascot-side {
            opacity: 0;
        }

        .hero-section {
            position: relative;
            min-height: 100vh;
            background: 
                radial-gradient(circle at 90% 50%, rgba(91, 154, 122, 0.14) 0%, transparent 35%),
                radial-gradient(circle at 50% 50%, #ecfdf5 0%, transparent 60%),
                radial-gradient(circle at 15% 20%, rgba(245, 158, 11, 0.12) 0%, transparent 20%);

            display: flex;
            align-items: center;
            justify-content: center;
            padding-top: 80px;
        }

        /* Semi-Circle Decoration */
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

        /* Small Separate Gradient Glow */
        .hero-section::after {
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

        /* Glow centered on the Sign In button */
        .button-glow {
            position: absolute;
            bottom: 200px;
            left: 55%;
            transform: translate(-50%, 50%);
            width: 500px;
            height: 400px;
            border-radius: 100%;
            background: radial-gradient(circle, rgba(168, 85, 247, 0.2) 10%, transparent 70%);
            z-index: 9; /* Lowered z-index to be behind the phone-wrapper */
            pointer-events: none;
        }

        /* Top Navigation Overlay */
        .top-nav {
            position: absolute;
            top: 30px;
            right: 50px;
            z-index: 100;
        }
        .top-nav a {
            text-decoration: none;
            color: #94a3b8;
            font-weight: 600;
            margin-left: 20px;
            transition: color 0.3s;
        }
        .top-nav a:hover { color: var(--lumi-purple); }

        /* The Phone Centerpiece */
        .phone-wrapper {
            position: relative;
            width: 700px;
            height: 600px;
            z-index: 10;
            text-align: center;
            margin-top: -400px;
        }

       

        .phone-header {
            padding-top: 20px;
            text-align: center;
        }

        .phone-welcome {
            font-weight: 700;
            font-size: 1.5rem;
            margin-bottom: 5px;
        }

        .btn-phone-start {
            border: 2px solid #000;
            background: white;
            border-radius: 20px;
            padding: 4px 20px;
            font-size: 0.8rem;
            font-weight: 700;
            margin-top: 10px;
            transition: transform 0.2s;
        }

        .phone-character {
            width: 100%;
            display: block;
            margin-top: 20px;
            transition: transform 0.2s cubic-bezier(0.23, 1, 0.32, 1);
        }

        .mascot-image {
            width: 100%;
            font-size: 120px;
            line-height: 1;
            margin-bottom: 10px;
        }

        /* Mascot Side Elements */
        .mascot-side {
            position: absolute;
            width: 220px;
            text-align: center;
            transition: opacity 0.4s ease, transform 0.5s cubic-bezier(0.34, 1.3, 0.64, 1); /* Lowered intensity of the pop up */
        }
        @keyframes mascotTilt {
            0%, 100% { transform: rotate(-3deg) translateY(0); }
            50% { transform: rotate(3deg) translateY(-8px); }
        }
        .mascot-side img {
            position: relative;
            z-index: 5;
            animation: mascotTilt 4s ease-in-out infinite;
            transform-origin: center bottom;
            transition: transform 0.3s ease-out; /* Smooth transition for hover effect */
        }
        .mascot-side img:hover {
            transform: scale(1.1) rotate(0deg); /* Scale up and become more upright on hover */
            animation: none; /* Stop continuous tilt animation on hover */
        }
        .mascot-left {
            left: 9%; /* Moved right (closer to center) */
            top: 15%; /* Moved downward */
            width: 150px;
            opacity: 0; /* Hidden by default */
            transform: scale(0) translate(calc(55vw + 350px), calc(20vh - 250px)); /* Start animation from the right side of the phone */
        }
        .mascot-left-near {
            left: 25%; /* Positioned right next to the phone */
            top: 45%;
            width: 150px;
            opacity: 0; /* Hidden by default */
            transform: scale(0) translate(25vw, calc(-5vh - 200px)); /* Origin adjusted for pop from phone */
        }
        .mascot-left-far {
            left: 18%; /* Positioned to the left of 10.png */
            top: 60%;
            width: 100px;
            opacity: 0; /* Hidden by default */
            transform: scale(0) translate(32vw, calc(-20vh - 200px)); /* Origin adjusted for pop from phone */
        }
        .mascot-right-upper {
            right: 12%; /* Upper right position */
            top: 15%;
            width: 180px; /* Made 9.png bigger */
            opacity: 0; /* Hidden by default */
            transform: scale(0) translate(calc(-38vw), calc(35vh - 400px)); /* Starts from phone's center */
        }
        .mascot-right-lower {
            right: 18%; /* Lower right position */
            top: 48%;
            width: 130px; /* Made 8.png bigger */
            opacity: 0; /* Hidden by default */
            transform: scale(0) translate(calc(-32vw), calc(2vh - 400px)); /* Starts from phone's center */
            z-index: 11;
        }
        .mascot-right {
            right: 10%;
            top: 20%;
            width: 250px;
        }

        .speech-bubble {
            background: white;
            padding: 150px 20px 40px;
            border-radius: 0 50px 0 0;
            font-size: 0.9rem;
            line-height: 1.4;
            box-shadow: 0 10px 25px rgba(0,0,0,0.03);
            margin-top: -150px;
            text-align: left;
            position: relative;
            z-index: 4;
        }

        /* Bottom Pill Shape */
        .bottom-pill {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            width: 160px;
            height: 40px;
            background: #527267;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            border-radius: 30px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            z-index: 100;
        }

        .bottom-pill:hover {
            background: var(--lumi-purple);
            color: white;
            transform: translateX(-50%) translateY(-5px);
            box-shadow: 0 8px 25px rgba(168, 85, 247, 0.3);
        }

        /* Scroll Animation Classes */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes fadeInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .scroll-animate {
            opacity: 0;
            transform: translateY(30px);
        }

        .scroll-animate.animate {
            animation: fadeInUp 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        .scroll-animate-left {
            opacity: 0;
            transform: translateX(-30px);
        }

        .scroll-animate-left.animate {
            animation: fadeInLeft 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        .scroll-animate-right {
            opacity: 0;
            transform: translateX(30px);
        }

        .scroll-animate-right.animate {
            animation: fadeInRight 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        .scroll-animate-scale {
            opacity: 0;
            transform: scale(0.95);
        }

        .scroll-animate-scale.animate {
            animation: scaleIn 0.8s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        @media (max-width: 992px) {
            .mascot-side { display: none; }
            .bg-lumi-text { font-size: 40vw; top: -28%; }
        }
    </style>
</head>
<body>

    <section class="hero-section">
        <div class="bg-lumi-text">LUMI</div>

        <div class="mascot-side mascot-left">
            <img src="{{ asset('assets/7.png') }}" alt="Mascot Left" style="width: 100%; height: auto;">
        </div>

        <div class="mascot-side mascot-left-far">
            <img src="{{ asset('assets/11.png') }}" alt="Mascot Far Left" style="width: 100%; height: auto;">
        </div>

        <div class="mascot-side mascot-left-near">
            <img src="{{ asset('assets/10.png') }}" alt="Mascot Near Left" style="width: 100%; height: auto;">
        </div>

        <div class="mascot-side mascot-right-upper">
            <img src="{{ asset('assets/9.png') }}" alt="Mascot Upper Right" style="width: 100%; height: auto;">
        </div>

        <div class="mascot-side mascot-right-lower">
            <img src="{{ asset('assets/8.png') }}" alt="Mascot Lower Right" style="width: 100%; height: auto;">
        </div>

        <div class="phone-wrapper">
            <img src="{{ asset('assets/phone.png') }}" class="phone-character" alt="Phone Mascot" style="width: 100%; height: auto;">
        </div>

      

        <div class="button-glow"></div>
        <a href="{{ Route::has('login') ? route('login') : '#' }}" class="bottom-pill">Sign In</a>
    </section>

    <div class="container-fluid px-4 px-md-5 py-5">
        <div class="row g-4 mb-5">
            <!-- Large Left Card -->
            <div class="col-md-6 scroll-animate-left">
                <div class="p-5 rounded-4 h-100 position-relative overflow-hidden" style="background: var(--lumi-mint); display: flex; flex-direction: column; justify-content: center; min-height: 240px;">
                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 500px; height: 500px; background: radial-gradient(circle, rgba(255, 182, 249, 0.7) 0%, transparent 70%); border-radius: 50%; pointer-events: none;"></div>
                    <img src="{{ asset('assets/characters.png') }}" alt="LUMI Character" style="width: 680px; height: auto; position: absolute; bottom: 0; left: 0; z-index: 1;">
                    <div style="position: absolute; top: 3rem; left: 3rem; z-index: 2; text-align: left;">
                        <h4 class="text-white fw-bold mb-0" style="font-family: 'Poppins', sans-serif;">Gamified mascot <br> encourages safe <br>screen habits</h4>
                    </div>
                </div>
            </div>

            <!-- Tall Middle Card -->
          <div class="col-md-3 scroll-animate">
    <div class="rounded-4 h-100 d-flex align-items-end justify-content-center" 
         style="background: #527267; min-height: 360px;">
        
        <div class="p-4 rounded-4 text-start mb-3 d-flex flex-column justify-content-end"
             style="background: #ffffffa1; width: 80%; height:55%;">
             
            <h4 class="fw-semi bold fs-6">SMART ALERTS</h4>
            <p class="text-muted mb-0">
                Gentle reminders when eyes need rest or screen is too close.
            </p>
        </div>
    </div>
</div>

            <!-- Large Right Card -->
           <div class="col-md-3 scroll-animate-right">
    <div class="p-5 rounded-4 h-100 d-flex align-items-center justify-content-center"
         style="background:#E3F2E6; min-height:240px;">

        <div style="
    writing-mode: vertical-rl;
    transform: rotate(180deg);
    position: relative;">
    <h6 class="fw mb-2" style="position: absolute; left: 5rem; top: -3rem;">HOW LUMI WORKS </h6>

    <p class="text-muted mb-0 small"  style=" right: 1rem; top: 0;" >
        When unsafe behavior is detected, <br>
        LUMI gently reminds users to rest, <br>
        blink, or adjust distance.
    </p>
</div>

    </div>
</div>

        <!-- Wide Bottom Card -->
        <div class="row mt-5">
            <div class="col-12 scroll-animate-scale">
                <div class="rounded-4 text-center overflow-hidden position-relative" style="background: #B2C3B5; min-height: 240px;">
                    <div style="position: absolute; top: 3rem; left: 3rem; z-index: 10;">
                        <h4 class=" mb-0 text-white">GUARDIAN DASHBOARD</h4>
                    </div>
                    <div style="position: absolute; bottom: 3rem; right: 3rem; z-index: 10; text-align: right;">
                        <p class="text-white mb-0">Parents monitor child's screen habits.</p>
                        <p class="text-white mb-0">View blink rate, distance, and usage reports.</p>
                    </div>
                    <img src="{{ asset('assets/parentsphone.png') }}" alt="Parents" style="width: 30%; height: 240px; object-fit: cover; display: block; margin: 0 auto;">
                </div>
            </div>
        </div>
    </div>

    <footer class="text-center py-4 text-muted border-top">
        <p>&copy; 2026 LUMI Eye Health. All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Scroll animation observer
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate');
                    observer.unobserve(entry.target);
                }
            });
        }, observerOptions);

        const animatedElements = document.querySelectorAll(
            '.scroll-animate, .scroll-animate-left, .scroll-animate-right, .scroll-animate-scale'
        );
        
        animatedElements.forEach(element => {
            observer.observe(element);
        });

        // Enhanced Phone Character Interaction
        const phoneCharacter = document.querySelector('.phone-character');
        const phoneWrapper = document.querySelector('.phone-wrapper');
        let intensity = 5;

        phoneWrapper.addEventListener('mouseenter', () => {
            intensity = 15; // Increase tilt intensity on hover
        });

        phoneWrapper.addEventListener('mouseleave', () => {
            intensity = 5; // Revert to subtle tilt
        });
        
        // Parallax effect on mouse move
        document.addEventListener('mousemove', (e) => {
            if (phoneCharacter && window.innerWidth > 992) {
                const x = (e.clientX / window.innerWidth) * 2 - 1;
                const y = (e.clientY / window.innerHeight) * 2 - 1;
                
                // Apply dynamic 3D tilt and slight scale-up when hovered
                phoneCharacter.style.transform = `perspective(1000px) rotateX(${y * intensity}deg) rotateY(${x * intensity}deg) scale(${intensity > 5 ? 1.05 : 1})`;
            }
        });

        // Reset on mouse leave
        document.addEventListener('mouseleave', () => {
            if (phoneCharacter) {
                phoneCharacter.style.transform = 'perspective(1000px) rotateX(0) rotateY(0)';
            }
        });

        // Add depth shadow effect
        if (phoneCharacter) {
            phoneCharacter.style.filter = 'drop-shadow(0 20px 60px rgba(0, 0, 0, 0.1))';
        }
    </script>
</body>