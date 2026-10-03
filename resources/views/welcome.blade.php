<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="CredAI connects people, insurers, and admins with smarter protection, mobile-money balance checks, and AI-assisted insurance decisions.">
    <title>CredAI | Smarter insurance decisions</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --c-ink: #12342d;
            --c-green: #16805a;
            --c-mint: #eaf7d7;
            --c-lime: #c9ed77;
            --c-paper: #f5f7f2;
            --c-white: #ffffff;
            --c-muted: #5f6f69;
            --c-line: #dfe8df;
            --c-coral: #e77d62;
            --c-forest: #0f2d29;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; }

        .credai-home {
            background: var(--c-paper);
            color: var(--c-ink);
            font-family: 'DM Sans', sans-serif;
            min-height: 100vh;
            overflow-x: clip;
        }

        .home-wrap {
            max-width: 1240px;
            margin: 0 auto;
            padding-left: 28px;
            padding-right: 28px;
        }

        .language-switcher {
            display: flex;
            align-items: center;
            gap: 8px;
            padding-left: 16px;
            margin-left: 4px;
            border-left: 1px solid var(--c-line);
        }

        .language-switcher label {
            font-size: 0.64rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #7c8d86;
        }

        .language-switcher select {
            border: 0;
            background: transparent;
            color: var(--c-ink);
            font: 700 0.7rem 'DM Sans', sans-serif;
            cursor: pointer;
            outline: 0;
            padding: 4px 0;
        }

        .credai-home > header {
            position: sticky;
            top: 0;
            z-index: 50;
            background: rgba(245, 247, 242, 0.94);
            border-bottom: 1px solid rgba(223, 232, 223, 0.9);
            backdrop-filter: blur(14px);
        }

        .home-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 22px 0;
        }

        .logo {
            display: inline-flex;
            align-items: center;
            text-decoration: none;
            color: var(--c-ink);
        }

        .logo img {
            display: block;
            width: 96px;
            height: 58px;
            padding: 4px;
            border-radius: 12px;
            background: #fff;
            border: 2px solid var(--c-green);
            object-fit: contain;
            box-shadow: 0 10px 24px rgba(18, 52, 45, 0.08);
        }

        .home-nav-links {
            display: flex;
            align-items: center;
            gap: 28px;
        }

        .home-nav-links a,
        .home-login {
            color: #5d7169;
            font-size: 0.76rem;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .home-nav-links a:hover,
        .home-login:hover { color: var(--c-green); }

        .home-nav-actions {
            display: flex;
            align-items: center;
            gap: 17px;
        }

        .home-cta-small,
        .action-main,
        .action-quiet {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            border-radius: 999px;
            text-decoration: none;
            transition: transform 0.2s ease, background 0.2s ease;
        }

        .home-cta-small {
            background: var(--c-green);
            color: white;
            padding: 11px 16px;
            font-size: 0.74rem;
            font-weight: 700;
        }

        .home-cta-small:hover,
        .action-main:hover,
        .action-quiet:hover {
            transform: translateY(-1px);
        }

        .hero {
            display: grid;
            align-items: center;
            grid-template-columns: minmax(0, 1fr) 460px;
            gap: 72px;
            padding: 74px 0 82px;
        }

        .hero-copy { max-width: 680px; }

        .kicker {
            color: var(--c-green);
            font-size: 0.67rem;
            font-weight: 700;
            letter-spacing: 0.16em;
            text-transform: uppercase;
        }

        .hero h1 {
            margin: 18px 0 22px;
            font: 600 clamp(3.3rem, 6vw, 6.4rem)/0.9 'Space Grotesk', sans-serif;
            letter-spacing: -0.07em;
        }

        .hero h1 em {
            color: var(--c-green);
            font-style: normal;
        }

        .hero-description {
            color: var(--c-muted);
            font-size: 1.05rem;
            line-height: 1.75;
            max-width: 560px;
            margin: 0;
        }

        .credai-home .hero-copy .hero-description { color: var(--c-muted); }

        .hero-actions {
            display: flex;
            align-items: center;
            gap: 18px;
            margin-top: 30px;
        }

        .action-main {
            background: var(--c-green);
            color: #fff;
            padding: 15px 20px;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .action-quiet {
            color: var(--c-ink);
            border: 1px solid #bdd0c1;
            background: rgba(255, 255, 255, 0.3);
            padding: 15px 18px;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .hero-footnote {
            margin-top: 14px;
            color: #7a8b84;
            font-size: 0.71rem;
        }

        .credai-home .hero-copy .hero-footnote { color: #61766d; }

        .hero-art {
            position: relative;
            min-height: 492px;
            background: var(--c-forest);
            padding: 28px;
            overflow: hidden;
            border-radius: 26px;
        }

        .hero-art:before {
            content: '';
            position: absolute;
            left: -180px;
            top: 78px;
            width: 510px;
            height: 510px;
            border: 1px solid rgba(201, 237, 119, 0.35);
            border-radius: 50%;
        }

        .hero-art:after {
            content: '';
            position: absolute;
            top: -38px;
            right: -34px;
            width: 126px;
            height: 126px;
            background: var(--c-coral);
            border-radius: 50%;
            opacity: 0.94;
        }

        .art-title {
            position: relative;
            z-index: 1;
            color: var(--c-lime);
            font-size: 0.64rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
        }

        .readiness-card {
            position: relative;
            z-index: 1;
            max-width: 318px;
            margin: 78px 0 0 30px;
            padding: 24px 22px;
            background: #f6f9ee;
            border: 1px solid rgba(201, 237, 119, 0.4);
            border-radius: 18px;
        }

        .readiness-card small {
            display: block;
            color: #6a7d77;
            font-size: 0.64rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .readiness-card strong {
            display: block;
            margin: 12px 0 8px;
            font: 600 4.1rem/0.94 'Space Grotesk', sans-serif;
            letter-spacing: -0.09em;
        }

        .readiness-card strong span {
            color: var(--c-green);
            font-size: 1.35rem;
            letter-spacing: 0;
        }

        .readiness-card p {
            margin: 0;
            color: #72857d;
            font-size: 0.72rem;
            line-height: 1.6;
        }

        .art-line {
            position: absolute;
            left: 28px;
            right: 28px;
            bottom: 26px;
            display: flex;
            gap: 10px;
        }

        .art-line span {
            flex: 1;
            font-size: 0.65rem;
            color: #c7d7cf;
            border-top: 1px solid rgba(255,255,255,0.18);
            padding-top: 10px;
        }

        .art-line b {
            display: block;
            margin-bottom: 5px;
            color: var(--c-lime);
            font-size: 0.6rem;
        }

        .trust-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            padding: 22px 0;
            border-top: 1px solid var(--c-line);
            border-bottom: 1px solid var(--c-line);
        }

        .trust-bar p {
            margin: 0;
            color: #7b8d86;
            font-size: 0.7rem;
        }

        .trust-points {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
            color: var(--c-ink);
            font: 600 0.75rem 'Space Grotesk', sans-serif;
        }

        .trust-points b {
            color: var(--c-green);
            margin-right: 6px;
        }

        .section {
            padding: 110px 0 84px;
        }

        .section-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 30px;
        }

        .section-title {
            margin-top: 12px;
            max-width: 590px;
            font: 600 clamp(2.2rem, 4vw, 3.8rem)/0.98 'Space Grotesk', sans-serif;
            letter-spacing: -0.06em;
        }

        .section-intro {
            max-width: 330px;
            color: var(--c-muted);
            line-height: 1.7;
        }

        .audience-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 14px;
            margin-top: 48px;
        }

        .audience-card {
            position: relative;
            min-height: 320px;
            padding: 28px 26px;
            background: var(--c-white);
            border: 1px solid var(--c-line);
            border-radius: 22px;
        }

        .audience-card--dark {
            background: var(--c-forest);
            color: white;
        }

        .audience-card--mint {
            background: var(--c-mint);
            border-color: #d1e8b7;
        }

        .audience-number {
            color: var(--c-green);
            font: 700 0.67rem 'Space Grotesk', sans-serif;
        }

        .audience-card--dark .audience-number { color: var(--c-lime); }

        .audience-card h3 {
            margin: 70px 0 12px;
            max-width: 220px;
            font: 600 1.42rem/1.1 'Space Grotesk', sans-serif;
        }

        .audience-card p {
            max-width: 255px;
            margin: 0;
            color: var(--c-muted);
            font-size: 0.78rem;
            line-height: 1.68;
        }

        .audience-card--dark p { color: #c2d7cc; }

        .audience-link {
            position: absolute;
            left: 26px;
            bottom: 26px;
            color: var(--c-green);
            text-decoration: none;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .feature-band {
            margin-top: 14px;
            padding: 72px 56px;
            background: var(--c-forest);
            color: white;
            border-radius: 28px;
        }

        .feature-layout {
            display: grid;
            grid-template-columns: 0.85fr 1.15fr;
            gap: 80px;
            align-items: center;
        }

        .feature-band .kicker { color: var(--c-lime); }

        .feature-band h2 {
            margin: 12px 0 18px;
            font: 600 clamp(2.2rem, 3.6vw, 3.8rem)/0.98 'Space Grotesk', sans-serif;
            letter-spacing: -0.06em;
        }

        .feature-band p {
            color: #bad0c1;
            line-height: 1.75;
            max-width: 390px;
            font-size: 0.82rem;
        }

        .feature-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0;
        }

        .feature-item {
            padding: 22px 18px 22px 0;
            border-top: 1px solid rgba(255,255,255,0.18);
        }

        .feature-item:nth-child(odd) {
            padding-right: 24px;
            border-right: 1px solid rgba(255,255,255,0.12);
        }

        .feature-item:nth-child(even) {
            padding-left: 24px;
        }

        .feature-item b {
            display: block;
            margin-bottom: 8px;
            color: var(--c-lime);
            font: 600 1.5rem 'Space Grotesk', sans-serif;
        }

        .feature-item strong {
            display: block;
            margin-bottom: 6px;
            font-size: 0.83rem;
        }

        .feature-item span {
            color: #a8c0b0;
            font-size: 0.7rem;
            line-height: 1.6;
        }

        .process-section {
            padding-top: 110px;
            padding-bottom: 110px;
        }

        .process-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-top: 50px;
        }

        .process-step {
            border-top: 2px solid var(--c-green);
            padding-top: 20px;
        }

        .process-step:nth-child(2) { border-color: var(--c-coral); }
        .process-step:nth-child(3) { border-color: #a8c95f; }
        .process-step:nth-child(4) { border-color: var(--c-ink); }

        .process-step b {
            color: var(--c-green);
            font: 700 0.67rem 'Space Grotesk', sans-serif;
        }

        .process-step h3 {
            margin: 26px 0 8px;
            font: 600 1.08rem 'Space Grotesk', sans-serif;
        }

        .process-step p {
            margin: 0;
            max-width: 210px;
            color: var(--c-muted);
            font-size: 0.72rem;
            line-height: 1.65;
        }

        .cta-panel {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
            padding: 36px 42px;
            background: var(--c-mint);
            border: 1px solid #d1e8b7;
            border-radius: 22px;
        }

        .cta-panel h2 {
            margin: 0 0 8px;
            font: 600 clamp(1.7rem, 3vw, 2.55rem)/1 'Space Grotesk', sans-serif;
            letter-spacing: -0.05em;
        }

        .cta-panel p {
            margin: 0;
            color: #60776d;
            font-size: 0.76rem;
        }

        .home-footer {
            background: var(--c-forest);
            color: #f4f9f0;
            padding-top: 52px;
        }

        .footer-grid {
            display: grid;
            grid-template-columns: 1.7fr repeat(3, minmax(0, 1fr));
            gap: 30px;
            padding-bottom: 42px;
        }

        .footer-brand p {
            margin: 18px 0 18px;
            max-width: 220px;
            color: #d0e0d3;
            font-size: 0.75rem;
            line-height: 1.6;
        }

        .footer-brand a {
            display: block;
            text-decoration: none;
            font-size: 0.74rem;
            font-weight: 600;
        }

        .footer-phone,
        .footer-email {
            color: #fff;
        }

        .footer-column {
            display: flex;
            flex-direction: column;
            gap: 11px;
        }

        .footer-column strong {
            color: var(--c-lime);
            font: 600 0.78rem 'Space Grotesk', sans-serif;
            margin-bottom: 6px;
        }

        .footer-column a,
        .footer-column span {
            color: #cfddd3;
            text-decoration: none;
            font-size: 0.73rem;
        }

        .footer-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 18px 0 28px;
            border-top: 1px solid rgba(201, 237, 119, 0.18);
            color: #d0e0d3;
            font-size: 0.68rem;
        }

        @media (max-width: 900px) {
            .home-nav-links { display: none; }
            .hero { grid-template-columns: 1fr; gap: 42px; }
            .hero-art { max-width: 540px; }
            .section-top { align-items: flex-start; flex-direction: column; }
            .audience-grid { grid-template-columns: 1fr; }
            .feature-layout { grid-template-columns: 1fr; gap: 42px; }
            .process-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .footer-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        @media (max-width: 560px) {
            .home-wrap { padding-left: 18px; padding-right: 18px; }
            .home-login { display: none; }
            .language-switcher { border-left: 0; margin-left: 0; padding-left: 0; }
            .language-switcher label { display: none; }
            .home-cta-small { padding: 9px 12px; }
            .hero { padding-top: 56px; padding-bottom: 62px; }
            .hero h1 { font-size: clamp(3.2rem, 15vw, 5rem); }
            .hero-actions { flex-direction: column; align-items: stretch; max-width: 230px; }
            .hero-art { min-height: 380px; padding: 21px; }
            .readiness-card { margin-left: 0; margin-top: 72px; }
            .art-line { left: 21px; right: 21px; }
            .trust-bar { flex-direction: column; align-items: flex-start; gap: 16px; }
            .section { padding: 78px 0 62px; }
            .feature-band { padding: 44px 24px; }
            .feature-list { grid-template-columns: 1fr; }
            .feature-item:nth-child(odd) { padding-right: 0; border-right: none; }
            .feature-item:nth-child(even) { padding-left: 0; }
            .process-grid { grid-template-columns: 1fr; }
            .cta-panel { flex-direction: column; align-items: flex-start; padding: 28px 22px; }
            .footer-grid { grid-template-columns: 1fr; }
            .footer-bottom { flex-direction: column; align-items: flex-start; }
        }
    </style>
</head>
<body class="credai-home">
    <header class="home-wrap">
        <nav class="home-nav" aria-label="Main navigation">
            <a href="{{ url('/') }}" class="logo">
                <img src="{{ route('brand.logo') }}" alt="CredAI logo">
            </a>

            <div class="home-nav-links">
                <a href="#platform">{{ __('Platform') }}</a>
                <a href="#how-it-works">{{ __('How it works') }}</a>
                <a href="#roles">{{ __('For providers') }}</a>
                <a href="#roles">{{ __('For hospitals') }}</a>
                <a href="{{ route('ai.index') }}">{{ __('AI guide') }}</a>
            </div>

            <div class="home-nav-actions">
                <div class="language-switcher">
                    <label for="language">{{ __('Language') }}</label>
                    <select id="language" onchange="window.location.href = this.value">
                        <option value="{{ route('language.switch', 'en') }}" @selected(app()->getLocale() === 'en')>{{ __('English') }}</option>
                        <option value="{{ route('language.switch', 'sw') }}" @selected(app()->getLocale() === 'sw')>{{ __('Swahili') }}</option>
                    </select>
                </div>

                @auth
                    <a class="home-cta-small" href="{{ route('dashboard') }}">{{ __('Open dashboard') }}</a>
                @else
                    <a class="home-login" href="{{ route('login') }}">{{ __('Log in') }}</a>
                    <a class="home-cta-small" href="{{ route('register') }}">{{ __('Get started') }}</a>
                @endauth
            </div>
        </nav>
    </header>

    <main class="home-wrap">
        <section class="hero">
            <div class="hero-copy">
                <span class="kicker">Protection built on real mobile value</span>
                <h1>CredAI</h1>
                <p class="hero-description">CredAI helps people, families, and insurers use mobile-money balance intelligence and AI guidance to make insurance decisions that are fairer, clearer, and easier to trust.</p>

                <div class="hero-actions">
                    <a class="action-main" href="{{ route('register') }}">Get my smart quote <span aria-hidden="true">&rarr;</span></a>
                    <a class="action-quiet" href="#how-it-works">Explore the platform</a>
                </div>

                <p class="hero-footnote">Coverage is matched to the balance available in the customer’s SIM account and the protection goal they choose.</p>
            </div>

            <div class="hero-art">
                <span class="art-title">CredAI protection index</span>
                <div class="readiness-card">
                    <small>Estimated monthly premium</small>
                    <strong>3,500<span>TZS</span></strong>
                    <p>Based on a healthy mobile balance and a family protection plan.</p>
                </div>

                <div class="art-line">
                    <span><b>01</b>Balance check</span>
                    <span><b>02</b>AI review</span>
                    <span><b>03</b>Policy match</span>
                </div>
            </div>
        </section>

        <section class="trust-bar">
            <p>A connected protection system built around clarity, trust, and accountability.</p>
            <div class="trust-points">
                <span><b>01</b> SIM-based balance</span>
                <span><b>02</b> AI underwriting</span>
                <span><b>03</b> Faster policy decisions</span>
            </div>
        </section>

        <section class="section" id="roles">
            <div class="section-top">
                <div>
                    <span class="kicker">One platform, three roles</span>
                    <h2 class="section-title">Everyone sees the next right step.</h2>
                </div>
                <p class="section-intro">CredAI brings customers, providers, and admins into one secure protection flow with clear decision support.</p>
            </div>

            <div class="audience-grid">
                <article class="audience-card">
                    <span class="audience-number">01 / NORMAL USER</span>
                    <h3>Protect what matters most.</h3>
                    <p>Check your SIM balance, explore affordable cover, and choose a plan that fits your real monthly value.</p>
                    <a class="audience-link" href="{{ route('register') }}">Create your profile &rarr;</a>
                </article>

                <article class="audience-card audience-card--dark">
                    <span class="audience-number">02 / INSURANCE PROVIDER</span>
                    <h3>Price faster and smarter.</h3>
                    <p>Review customer risk, compare balance-backed eligibility, and use AI suggestions to support underwriting.</p>
                    <a class="audience-link" href="{{ route('register') }}">Join as insurer &rarr;</a>
                </article>

                <article class="audience-card audience-card--mint">
                    <span class="audience-number">03 / ADMIN</span>
                    <h3>Keep the network healthy.</h3>
                    <p>Monitor policies, control platform standards, and keep every insurance decision transparent and compliant.</p>
                    <a class="audience-link" href="{{ route('register') }}">Open admin access &rarr;</a>
                </article>
            </div>
        </section>

        <section class="feature-band" id="platform">
            <div class="feature-layout">
                <div>
                    <span class="kicker">The CredAI platform</span>
                    <h2>Smarter insurance decisions start with stronger evidence.</h2>
                    <p>From the first balance check to final approval, CredAI keeps customer data, policy needs, and recommendations in one secure system. That means better coverage matches for users and stronger oversight for providers and admins.</p>
                    <a class="action-main" href="{{ route('register') }}">See your protection fit <span aria-hidden="true">&rarr;</span></a>
                </div>

                <div class="feature-list">
                    <div class="feature-item">
                        <b>01</b>
                        <strong>SIM balance intelligence</strong>
                        <span>Use live account value to guide safer premium and coverage decisions.</span>
                    </div>
                    <div class="feature-item">
                        <b>02</b>
                        <strong>AI underwriting support</strong>
                        <span>Review recommendations that clearly explain the best-fit plan.</span>
                    </div>
                    <div class="feature-item">
                        <b>03</b>
                        <strong>Provider review</strong>
                        <span>Help insurers evaluate risk with transparent customer data and account history.</span>
                    </div>
                    <div class="feature-item">
                        <b>04</b>
                        <strong>Admin oversight</strong>
                        <span>Monitor health, quality, and compliance from a single command layer.</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="process-section" id="how-it-works">
            <div class="section-top">
                <div>
                    <span class="kicker">How it works</span>
                    <h2 class="section-title">From SIM balance to secure cover.</h2>
                </div>
                <p class="section-intro">A simple flow that helps people buy protection with confidence and helps providers price fairly.</p>
            </div>

            <div class="process-grid">
                <article class="process-step">
                    <b>01 / CHECK</b>
                    <h3>Review the customer balance.</h3>
                    <p>Assess the real mobile-money balance that supports monthly premium capacity.</p>
                </article>
                <article class="process-step">
                    <b>02 / PROFILE</b>
                    <h3>Match a protection goal.</h3>
                    <p>Choose a cover category such as family protection or health support.</p>
                </article>
                <article class="process-step">
                    <b>03 / AI</b>
                    <h3>Receive a smart recommendation.</h3>
                    <p>CredAI suggests the right premium, risk level, and coverage value using a transparent model.</p>
                </article>
                <article class="process-step">
                    <b>04 / APPROVE</b>
                    <h3>Issue the policy.</h3>
                    <p>Providers and admins review the recommendation and move the policy forward with confidence.</p>
                </article>
            </div>
        </section>

        <section class="cta-panel">
            <div>
                <h2>Ready to build a smarter protection experience?</h2>
                <p>Start with a live balance check, generate a quote, and see how AI supports fairer insurance.</p>
            </div>
            <a class="action-main" href="{{ route('register') }}">Get started free <span aria-hidden="true">&rarr;</span></a>
        </section>
    </main>

    <footer class="home-footer">
        <div class="home-wrap footer-grid">
            <div class="footer-brand">
                <a href="{{ url('/') }}" class="logo">
                    <img src="{{ route('brand.logo') }}" alt="CredAI logo">
                </a>
                <p>Verified activity. Responsible capital. Stronger businesses.</p>
                <a class="footer-phone" href="tel:+255652455040">+255 652 455 040</a>
                <a class="footer-email" href="mailto:hello@credai.co.tz">hello@credai.co.tz</a>
            </div>

            <div class="footer-column">
                <strong>Explore</strong>
                <a href="#platform">{{ __('Platform') }}</a>
                <a href="#how-it-works">{{ __('How it works') }}</a>
                <a href="#roles">{{ __('For providers') }}</a>
                <a href="#roles">{{ __('For hospitals') }}</a>
            </div>

            <div class="footer-column">
                <strong>{{ __('Support') }}</strong>
                <a href="{{ route('login') }}">{{ __('Sign in') }}</a>
                <a href="{{ route('register') }}">{{ __('Join the network') }}</a>
                <a href="mailto:support@credai.co.tz">Help centre</a>
                <a href="tel:+255652455040">{{ __('Contact') }}</a>
            </div>

            <div class="footer-column">
                <strong>Visit us</strong>
                <span>CredAI Tanzania</span>
                <span>Dar es Salaam, Tanzania</span>
                <span>Mon - Fri, 08:00 - 17:00 EAT</span>
            </div>
        </div>

        <div class="home-wrap footer-bottom">
            <p>&copy; {{ date('Y') }} credAI. Funding with a clearer conscience.</p>
            <span>Built for trust across Tanzania</span>
        </div>
    </footer>

    @include('shared.ai-assistant')
</body>
</html>
