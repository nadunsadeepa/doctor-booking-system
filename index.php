<?php
/**
 * index.php  (PROJECT ROOT)
 * -----------------------------------------------------------
 * Landing page — visitor picks which portal to sign in to.
 * -----------------------------------------------------------
 */
require_once __DIR__ . '/config/app_config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Doctor Booking System</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <!-- Font Awesome 6 (free) -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />

    <style>
        /* ===== CSS VARIABLES (Light & Dark) ===== */
        :root {
            --bg-body: #f6fbfa;
            --bg-hero: linear-gradient(135deg, #0b3d3a 0%, #1a6b64 40%, #2a9d8f 70%, #4ecdc4 100%);
            --hero-text: #fff;
            --hero-shadow: 0 12px 40px rgba(20, 76, 73, 0.25);
            --bg-section: #f6fbfa;
            --bg-card: #ffffff;
            --card-shadow: 0 8px 30px rgba(20, 76, 73, 0.08);
            --card-border: rgba(42, 157, 143, 0.08);
            --card-hover-shadow: 0 16px 48px rgba(20, 76, 73, 0.15);
            --text-primary: #0b3d3a;
            --text-secondary: #5e7a77;
            --text-muted: #6c8a86;
            --bg-soft: #e9f4f2;
            --bg-testimonial: #f9fcfb;
            --testimonial-border: #e9f4f2;
            --footer-bg: #0b3d3a;
            --footer-text: rgba(255,255,255,0.85);
            --footer-border: rgba(255,255,255,0.08);
            --footer-link: rgba(255,255,255,0.7);
            --footer-link-hover: #f4a261;
            --toggle-bg: #2a9d8f;
            --toggle-icon: #fff;
        }

        body.dark-mode {
            --bg-body: #121e1c;
            --bg-hero: linear-gradient(135deg, #082a27 0%, #0f4a44 40%, #1a6b64 70%, #2a9d8f 100%);
            --hero-text: #e6f7f5;
            --hero-shadow: 0 12px 40px rgba(0,0,0,0.6);
            --bg-section: #121e1c;
            --bg-card: #1e2e2b;
            --card-shadow: 0 8px 30px rgba(0,0,0,0.4);
            --card-border: rgba(42, 157, 143, 0.2);
            --card-hover-shadow: 0 16px 48px rgba(0,0,0,0.5);
            --text-primary: #d4ece8;
            --text-secondary: #a0c2bc;
            --text-muted: #8aa9a3;
            --bg-soft: #1e2e2b;
            --bg-testimonial: #1a2a27;
            --testimonial-border: #2a403c;
            --footer-bg: #0a1f1c;
            --footer-text: rgba(255,255,255,0.8);
            --footer-border: rgba(255,255,255,0.06);
            --footer-link: rgba(255,255,255,0.6);
            --footer-link-hover: #f4a261;
            --toggle-bg: #f4a261;
            --toggle-icon: #0b3d3a;
        }

        /* ===== GLOBAL ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            font-family: 'Inter', 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg-body);
            color: var(--text-primary);
            overflow-x: hidden;
            transition: background 0.4s ease, color 0.4s ease;
        }

        /* ===== DARK MODE TOGGLE ===== */
        .dark-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            background: var(--toggle-bg);
            color: var(--toggle-icon);
            border: none;
            border-radius: 50%;
            width: 48px;
            height: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.4rem;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(0,0,0,0.2);
            transition: background 0.3s, transform 0.2s;
            border: 2px solid rgba(255,255,255,0.3);
        }

        .dark-toggle:hover {
            transform: scale(1.05);
        }

        .dark-toggle:focus {
            outline: 2px solid #f4a261;
        }

        /* ===== HERO / TOP COLORFUL SECTION ===== */
        .hero {
            position: relative;
            background: var(--bg-hero);
            padding: 80px 0 70px;
            overflow: hidden;
            border-bottom: 6px solid #f4a261;
            box-shadow: var(--hero-shadow);
            transition: background 0.4s ease;
        }

        .hero .shape {
            position: absolute;
            border-radius: 50%;
            opacity: 0.15;
            pointer-events: none;
            animation: floatShape 16s infinite alternate ease-in-out;
        }

        .hero .shape-1 {
            width: 260px;
            height: 260px;
            background: #f4a261;
            top: -60px;
            right: -40px;
            animation-duration: 18s;
        }
        .hero .shape-2 {
            width: 180px;
            height: 180px;
            background: #e9c46a;
            bottom: -50px;
            left: 10%;
            animation-duration: 14s;
            animation-delay: 0.5s;
        }
        .hero .shape-3 {
            width: 120px;
            height: 120px;
            background: #ffffff;
            top: 30px;
            left: 25%;
            animation-duration: 12s;
            animation-delay: 1s;
        }
        .hero .shape-4 {
            width: 80px;
            height: 80px;
            background: #2a9d8f;
            bottom: 30px;
            right: 20%;
            animation-duration: 10s;
            animation-delay: 0.3s;
        }

        @keyframes floatShape {
            0% {
                transform: translate(0, 0) scale(1) rotate(0deg);
            }
            100% {
                transform: translate(40px, -30px) scale(1.1) rotate(12deg);
            }
        }

        .hero-wave {
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 100%;
            height: 60px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 60'%3E%3Cpath d='M0,30 C300,70 600,0 900,30 C1050,45 1150,30 1200,30 L1200,60 L0,60Z' fill='%23f6fbfa'/%3E%3C/svg%3E") no-repeat bottom / 100% 100%;
            pointer-events: none;
            transition: filter 0.4s ease;
        }

        body.dark-mode .hero-wave {
            filter: invert(1) hue-rotate(180deg);
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            color: var(--hero-text);
            transition: color 0.4s;
        }

        .hero-content .badge-top {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(4px);
            padding: 6px 20px;
            border-radius: 40px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.25);
            margin-bottom: 20px;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 800;
            letter-spacing: -0.02em;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .hero-content h1 i {
            color: #f4a261;
            margin-right: 8px;
        }

        .hero-content p.lead {
            font-size: 1.25rem;
            font-weight: 400;
            opacity: 0.92;
            max-width: 600px;
            margin: 12px auto 0;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 48px;
            margin-top: 36px;
            flex-wrap: wrap;
        }

        .hero-stats .stat-item {
            text-align: center;
        }

        .hero-stats .stat-item .number {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1;
        }

        .hero-stats .stat-item .label {
            font-size: 0.85rem;
            opacity: 0.8;
            font-weight: 400;
            margin-top: 4px;
        }

        /* ===== PORTAL CARDS SECTION ===== */
        .portal-section {
            padding: 60px 0 80px;
            background: var(--bg-section);
            transition: background 0.4s ease;
        }

        .section-tag {
            display: inline-block;
            background: var(--bg-soft);
            color: var(--text-primary);
            font-weight: 600;
            font-size: 0.75rem;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            padding: 6px 20px;
            border-radius: 30px;
            margin-bottom: 12px;
            transition: background 0.4s, color 0.4s;
        }

        .portal-section h2 {
            font-weight: 700;
            color: var(--text-primary);
            letter-spacing: -0.01em;
            transition: color 0.4s;
        }

        .portal-section h2 span {
            color: #2a9d8f;
        }

        .portal-section .subhead {
            color: var(--text-secondary);
            max-width: 500px;
            margin: 8px auto 0;
            transition: color 0.4s;
        }

        .portal-card {
            border: none;
            border-radius: 20px;
            background: var(--bg-card);
            box-shadow: var(--card-shadow);
            transition: transform 0.25s ease, box-shadow 0.3s ease, background 0.4s;
            padding: 2rem 1.5rem 1.8rem;
            height: 100%;
            position: relative;
            overflow: hidden;
            border: 1px solid var(--card-border);
        }

        .portal-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #2a9d8f, #4ecdc4, #f4a261);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .portal-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--card-hover-shadow);
        }

        .portal-card:hover::after {
            opacity: 1;
        }

        .portal-card .icon-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            border-radius: 16px;
            font-size: 2rem;
            color: #fff;
            margin-bottom: 18px;
            transition: transform 0.25s ease;
        }

        .portal-card:hover .icon-wrap {
            transform: scale(1.05) rotate(-2deg);
        }

        .icon-admin {
            background: linear-gradient(135deg, #0b3d3a, #1a6b64);
        }
        .icon-doctor {
            background: linear-gradient(135deg, #2a9d8f, #4ecdc4);
        }
        .icon-patient {
            background: linear-gradient(135deg, #f4a261, #e9c46a);
        }

        .portal-card h4 {
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 6px;
            transition: color 0.4s;
        }

        .portal-card .card-text {
            color: var(--text-secondary);
            font-size: 0.95rem;
            margin-bottom: 20px;
            line-height: 1.5;
            transition: color 0.4s;
        }

        .portal-card .btn-portal {
            border-radius: 40px;
            padding: 10px 28px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            border: 2px solid transparent;
            width: 100%;
        }

        .portal-card .btn-portal i {
            margin-right: 6px;
        }

        .btn-admin {
            background: #0b3d3a;
            color: #fff;
            border-color: #0b3d3a;
        }
        .btn-admin:hover {
            background: #144c49;
            border-color: #144c49;
            color: #fff;
            transform: scale(1.01);
            box-shadow: 0 8px 20px rgba(11, 61, 58, 0.25);
        }

        body.dark-mode .btn-admin {
            background: #1a6b64;
            border-color: #1a6b64;
        }
        body.dark-mode .btn-admin:hover {
            background: #2a9d8f;
            border-color: #2a9d8f;
        }

        .btn-doctor {
            background: #2a9d8f;
            color: #fff;
            border-color: #2a9d8f;
        }
        .btn-doctor:hover {
            background: #21867a;
            border-color: #21867a;
            color: #fff;
            transform: scale(1.01);
            box-shadow: 0 8px 20px rgba(42, 157, 143, 0.25);
        }

        body.dark-mode .btn-doctor {
            background: #3ab0a0;
            border-color: #3ab0a0;
        }
        body.dark-mode .btn-doctor:hover {
            background: #2a9d8f;
            border-color: #2a9d8f;
        }

        .btn-patient {
            background: #f4a261;
            color: #fff;
            border-color: #f4a261;
        }
        .btn-patient:hover {
            background: #e08f4a;
            border-color: #e08f4a;
            color: #fff;
            transform: scale(1.01);
            box-shadow: 0 8px 20px rgba(244, 162, 97, 0.25);
        }

        body.dark-mode .btn-patient {
            background: #e08f4a;
            border-color: #e08f4a;
        }
        body.dark-mode .btn-patient:hover {
            background: #f4a261;
            border-color: #f4a261;
        }

        /* ===== FEATURES SECTION ===== */
        .features-section {
            padding: 60px 0 70px;
            background: var(--bg-card);
            transition: background 0.4s;
        }

        .features-section .feature-item {
            text-align: center;
            padding: 20px 16px;
            border-radius: 20px;
            transition: transform 0.2s, box-shadow 0.3s, background 0.4s;
        }

        .features-section .feature-item:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 32px rgba(20, 76, 73, 0.06);
        }

        .features-section .feature-icon {
            font-size: 2.8rem;
            color: #2a9d8f;
            margin-bottom: 16px;
        }

        .features-section .feature-item h5 {
            font-weight: 700;
            color: var(--text-primary);
            transition: color 0.4s;
        }

        .features-section .feature-item p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            transition: color 0.4s;
        }

        /* ===== HOW IT WORKS ===== */
        .how-it-works {
            padding: 60px 0 70px;
            background: var(--bg-section);
            transition: background 0.4s;
        }

        .step-circle {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: linear-gradient(135deg, #2a9d8f, #4ecdc4);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 700;
            margin: 0 auto 16px;
            box-shadow: 0 6px 20px rgba(42, 157, 143, 0.25);
        }

        .step-item h5 {
            font-weight: 700;
            color: var(--text-primary);
            transition: color 0.4s;
        }
        .step-item p {
            color: var(--text-secondary);
            font-size: 0.95rem;
            max-width: 260px;
            margin: 0 auto;
            transition: color 0.4s;
        }

        /* ===== TESTIMONIALS ===== */
        .testimonials-section {
            padding: 60px 0 70px;
            background: var(--bg-card);
            transition: background 0.4s;
        }

        .testimonial-card {
            background: var(--bg-testimonial);
            border-radius: 20px;
            padding: 28px 24px;
            border: 1px solid var(--testimonial-border);
            transition: transform 0.2s, background 0.4s, border 0.4s;
            height: 100%;
        }

        .testimonial-card:hover {
            transform: scale(1.01);
        }

        .testimonial-card .quote-icon {
            color: #2a9d8f;
            font-size: 1.8rem;
            opacity: 0.4;
            margin-bottom: 8px;
        }

        .testimonial-card .testimonial-text {
            font-style: italic;
            color: var(--text-primary);
            font-size: 1rem;
            transition: color 0.4s;
        }

        .testimonial-card .testimonial-author {
            font-weight: 600;
            color: var(--text-primary);
            margin-top: 12px;
            transition: color 0.4s;
        }

        .testimonial-card .testimonial-role {
            font-size: 0.85rem;
            color: var(--text-secondary);
            transition: color 0.4s;
        }

        /* ===== FOOTER ===== */
        .main-footer {
            background: var(--footer-bg);
            color: var(--footer-text);
            padding: 40px 0 20px;
            border-top: 4px solid #f4a261;
            transition: background 0.4s, color 0.4s;
        }

        .main-footer h5 {
            color: #fff;
            font-weight: 700;
            margin-bottom: 16px;
        }

        .main-footer a {
            color: var(--footer-link);
            text-decoration: none;
            transition: color 0.2s;
        }

        .main-footer a:hover {
            color: var(--footer-link-hover);
        }

        .main-footer .footer-links li {
            margin-bottom: 6px;
            list-style: none;
        }

        .main-footer .social-icons a {
            display: inline-block;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            text-align: center;
            line-height: 36px;
            color: #fff;
            margin-right: 8px;
            transition: background 0.2s;
        }

        .main-footer .social-icons a:hover {
            background: #f4a261;
            color: #0b3d3a;
        }

        .footer-bottom {
            border-top: 1px solid var(--footer-border);
            padding-top: 16px;
            margin-top: 24px;
            font-size: 0.85rem;
            text-align: center;
            color: rgba(255,255,255,0.5);
            transition: border-color 0.4s;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .hero {
                padding: 50px 0 50px;
            }
            .hero-content h1 {
                font-size: 2.4rem;
            }
            .hero-content p.lead {
                font-size: 1rem;
            }
            .hero-stats {
                gap: 24px;
            }
            .hero-stats .stat-item .number {
                font-size: 1.6rem;
            }
            .hero .shape-1 {
                width: 140px;
                height: 140px;
                top: -30px;
                right: -20px;
            }
            .hero .shape-2 {
                width: 100px;
                height: 100px;
                bottom: -20px;
                left: 5%;
            }
            .hero .shape-3 {
                display: none;
            }
            .portal-section {
                padding: 40px 0 60px;
            }
            .portal-card {
                padding: 1.5rem 1.2rem;
            }
            .features-section, .how-it-works, .testimonials-section {
                padding: 40px 0 50px;
            }
            .step-circle {
                width: 60px;
                height: 60px;
                font-size: 1.4rem;
            }
            .dark-toggle {
                width: 40px;
                height: 40px;
                font-size: 1.1rem;
                top: 12px;
                right: 12px;
            }
        }

        @media (max-width: 480px) {
            .hero-content h1 {
                font-size: 1.9rem;
            }
            .hero-stats {
                gap: 16px;
            }
            .hero-stats .stat-item .number {
                font-size: 1.3rem;
            }
            .hero-stats .stat-item .label {
                font-size: 0.7rem;
            }
            .portal-card .icon-wrap {
                width: 52px;
                height: 52px;
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>

    <!-- ====== DARK MODE TOGGLE ====== -->
    <button class="dark-toggle" id="darkModeToggle" aria-label="Toggle dark mode">
        <i class="fas fa-moon" id="toggleIcon"></i>
    </button>

    <!-- ====== HERO / TOP COLORFUL SECTION ====== -->
    <header class="hero">
        <!-- floating shapes -->
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
        <div class="shape shape-4"></div>

        <!-- wave SVG at bottom -->
        <div class="hero-wave"></div>

        <div class="container hero-content">
            <div class="badge-top">
                <i class="fas fa-stethoscope me-1"></i> Smart Healthcare
            </div>

            <h1>
                <i class="fas fa-heartbeat"></i> Doctor Booking
            </h1>
            <p class="lead">
                Seamless appointments, connected care — all in one place.
            </p>

            <!-- mini stats -->
            <div class="hero-stats">
                <div class="stat-item">
                    <div class="number">50+</div>
                    <div class="label">Doctors</div>
                </div>
                <div class="stat-item">
                    <div class="number">1.2k</div>
                    <div class="label">Appointments</div>
                </div>
                <div class="stat-item">
                    <div class="number">98%</div>
                    <div class="label">Satisfaction</div>
                </div>
            </div>
        </div>
    </header>

    <!-- ====== PORTAL CARDS ====== -->
    <section class="portal-section">
        <div class="container">
            <div class="text-center mb-5">
                <div class="section-tag"><i class="fas fa-sign-in-alt me-1"></i> Choose your portal</div>
                <h2>Sign in to <span>your dashboard</span></h2>
                <p class="subhead">Select the role that fits you best and manage your healthcare workflow.</p>
            </div>

            <div class="row g-4 justify-content-center">
                <!-- Admin -->
                <div class="col-md-4">
                    <div class="portal-card text-center">
                        <div class="icon-wrap icon-admin">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <h4>Admin</h4>
                        <p class="card-text">Manage doctors, categories, schedules &amp; generate reports.</p>
                        <a href="admin/login.php" class="btn btn-portal btn-admin">
                            <i class="fas fa-arrow-right-to-bracket"></i> Admin Login
                        </a>
                    </div>
                </div>

                <!-- Doctor -->
                <div class="col-md-4">
                    <div class="portal-card text-center">
                        <div class="icon-wrap icon-doctor">
                            <i class="fas fa-user-md"></i>
                        </div>
                        <h4>Doctor</h4>
                        <p class="card-text">View your daily patient queue, manage appointments &amp; notes.</p>
                        <a href="doctor/login.php" class="btn btn-portal btn-doctor">
                            <i class="fas fa-arrow-right-to-bracket"></i> Doctor Login
                        </a>
                    </div>
                </div>

                <!-- Patient -->
                <div class="col-md-4">
                    <div class="portal-card text-center">
                        <div class="icon-wrap icon-patient">
                            <i class="fas fa-user-injured"></i>
                        </div>
                        <h4>Patient</h4>
                        <p class="card-text">Book a doctor, track your appointments &amp; view your history.</p>
                        <a href="patient/login.php" class="btn btn-portal btn-patient">
                            <i class="fas fa-arrow-right-to-bracket"></i> Patient Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ====== FEATURES SECTION ====== -->
    <section class="features-section">
        <div class="container">
            <div class="text-center mb-5">
                <div class="section-tag"><i class="fas fa-star me-1"></i> Why Choose Us</div>
                <h2>Built for <span>efficiency &amp; care</span></h2>
                <p class="subhead">Everything you need to manage appointments, patients, and schedules.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-3 col-6">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-calendar-check"></i></div>
                        <h5>Easy Booking</h5>
                        <p>Book appointments in seconds with real-time availability.</p>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-user-md"></i></div>
                        <h5>Expert Doctors</h5>
                        <p>Access a network of qualified and experienced specialists.</p>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-lock"></i></div>
                        <h5>Secure &amp; Private</h5>
                        <p>Your data is protected with industry‑standard encryption.</p>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-mobile-alt"></i></div>
                        <h5>Mobile Ready</h5>
                        <p>Access your dashboard on any device, anywhere.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ====== HOW IT WORKS ====== -->
    <section class="how-it-works">
        <div class="container">
            <div class="text-center mb-5">
                <div class="section-tag"><i class="fas fa-route me-1"></i> How It Works</div>
                <h2>Start in <span>three simple steps</span></h2>
                <p class="subhead">From sign‑in to managing your workflow — it's that easy.</p>
            </div>

            <div class="row g-4 text-center">
                <div class="col-md-4">
                    <div class="step-item">
                        <div class="step-circle">1</div>
                        <h5>Choose Your Portal</h5>
                        <p>Select the role that fits you: Admin, Doctor, or Patient.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="step-item">
                        <div class="step-circle">2</div>
                        <h5>Sign In Securely</h5>
                        <p>Use your credentials to access your personalized dashboard.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="step-item">
                        <div class="step-circle">3</div>
                        <h5>Manage &amp; Book</h5>
                        <p>View queues, schedule appointments, and track patient history.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ====== TESTIMONIALS ====== -->
    <section class="testimonials-section">
        <div class="container">
            <div class="text-center mb-5">
                <div class="section-tag"><i class="fas fa-quote-right me-1"></i> Testimonials</div>
                <h2>What our <span>users say</span></h2>
                <p class="subhead">Real stories from healthcare professionals and patients.</p>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <div class="quote-icon"><i class="fas fa-quote-left"></i></div>
                        <p class="testimonial-text">
                            “This system has streamlined our clinic's operations. I can now manage appointments and staff schedules effortlessly.”
                        </p>
                        <div class="testimonial-author">Dr. Sarah Kim</div>
                        <div class="testimonial-role">Cardiologist, City Hospital</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <div class="quote-icon"><i class="fas fa-quote-left"></i></div>
                        <p class="testimonial-text">
                            “As a patient, I love how easy it is to book an appointment and receive my queue number instantly.”
                        </p>
                        <div class="testimonial-author">John Doe</div>
                        <div class="testimonial-role">Regular Patient</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <div class="quote-icon"><i class="fas fa-quote-left"></i></div>
                        <p class="testimonial-text">
                            “The admin dashboard gives me complete visibility into our practice's performance and patient flow.”
                        </p>
                        <div class="testimonial-author">Emily Chen</div>
                        <div class="testimonial-role">Practice Manager</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ====== FOOTER ====== -->
    <footer class="main-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <h5><i class="fas fa-heartbeat me-2" style="color:#f4a261;"></i> Doctor Booking</h5>
                    <p style="opacity:0.7; font-size:0.95rem;">Seamless appointment scheduling for doctors, patients, and administrators.</p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="col-md-2">
                    <h5>Quick Links</h5>
                    <ul class="footer-links p-0">
                        <li><a href="admin/login.php">Admin</a></li>
                        <li><a href="doctor/login.php">Doctor</a></li>
                        <li><a href="patient/login.php">Patient</a></li>
                        <li><a href="#">About</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Support</h5>
                    <ul class="footer-links p-0">
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Contact Us</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Contact</h5>
                    <ul class="footer-links p-0" style="opacity:0.7;">
                        <li><i class="fas fa-envelope me-2"></i> doctorbooking@gmail.com</li>
                        <li><i class="fas fa-phone me-2"></i> +94 4445122</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i> 123 Health Ave, Kurunegala</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                &copy; <?= date('Y') ?> Doctor Booking System. All rights reserved.
            </div>
        </div>
    </footer>

    <!-- ====== DARK MODE SCRIPT ====== -->
    <script>
        (function() {
            const toggleBtn = document.getElementById('darkModeToggle');
            const icon = document.getElementById('toggleIcon');
            const body = document.body;

            // Check localStorage for saved preference
            const savedMode = localStorage.getItem('darkMode');
            if (savedMode === 'enabled') {
                body.classList.add('dark-mode');
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            }

            // Toggle function
            function toggleDarkMode() {
                body.classList.toggle('dark-mode');
                const isDark = body.classList.contains('dark-mode');
                if (isDark) {
                    icon.classList.remove('fa-moon');
                    icon.classList.add('fa-sun');
                    localStorage.setItem('darkMode', 'enabled');
                } else {
                    icon.classList.remove('fa-sun');
                    icon.classList.add('fa-moon');
                    localStorage.setItem('darkMode', 'disabled');
                }
            }

            toggleBtn.addEventListener('click', toggleDarkMode);
        })();
    </script>

    <!-- Bootstrap JS (optional) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
    </script>
</body>
</html>