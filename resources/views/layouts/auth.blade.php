<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>@yield('title', 'Admin Login') | Kids-e-Dental</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="description" content="Kids-e-Dental Admin Portal - Pediatric Dental Solutions" />
    <meta name="author" content="Kids-e-Dental" />

    <!-- App favicon: Crisp Kids-e-Dental Crown Icon -->
    <link rel="icon" type="image/png" sizes="32x32"
        href="{{ asset('backend/assets/images/kids-dental-favicon.png') }}?v={{ time() }}" />
    <link rel="icon" type="image/png" sizes="16x16"
        href="{{ asset('backend/assets/images/kids-dental-favicon.png') }}?v={{ time() }}" />
    <link rel="shortcut icon" type="image/png"
        href="{{ asset('backend/assets/images/kids-dental-favicon.png') }}?v={{ time() }}" />
    <link rel="apple-touch-icon" sizes="180x180"
        href="{{ asset('backend/assets/images/kids-dental-favicon.png') }}?v={{ time() }}">

    <!-- Google Font: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Theme Config Js -->
    <script src="{{ asset('backend/assets/js/config.js') }}"></script>

    <!-- Vendor css -->
    <link href="{{ asset('backend/assets/css/vendors.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- App css -->
    <link id="app-style" href="{{ asset('backend/assets/css/app.min.css') }}" rel="stylesheet" type="text/css" />

    <!-- Tabler Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@2.36.0/tabler-icons.min.css" />

    <style>
        :root {
            --brand-yellow: #ffcc00;
            --brand-yellow-hover: #f0be00;
            --brand-yellow-active: #dca800;
            --brand-yellow-soft: rgba(255, 204, 0, 0.14);
            --brand-yellow-glow: rgba(255, 204, 0, 0.35);
            --brand-dark: #121418;
            --brand-dark-panel: #1a1c22;
            --brand-dark-text: #0f1013;
            --font-family-base: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }

        body.auth-body {
            font-family: var(--font-family-base);
            min-height: 100vh;
            background: #0f1115;
            background-image:
                radial-gradient(at 10% 20%, rgba(255, 204, 0, 0.12) 0px, transparent 50%),
                radial-gradient(at 90% 80%, rgba(255, 204, 0, 0.08) 0px, transparent 50%),
                radial-gradient(at 50% 50%, rgba(30, 33, 42, 0.9) 0px, #0c0d10 100%);
            position: relative;
            overflow-x: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        /* Ambient Glow Spheres */
        .ambient-glow-1 {
            position: fixed;
            top: -120px;
            right: -100px;
            width: 420px;
            height: 420px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 204, 0, 0.15) 0%, rgba(255, 204, 0, 0) 70%);
            filter: blur(40px);
            pointer-events: none;
            z-index: 0;
        }

        .ambient-glow-2 {
            position: fixed;
            bottom: -150px;
            left: -120px;
            width: 500px;
            height: 500px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 204, 0, 0.12) 0%, rgba(255, 204, 0, 0) 70%);
            filter: blur(50px);
            pointer-events: none;
            z-index: 0;
        }

        /* Modern Split Card Container */
        .auth-split-card {
            background: #ffffff;
            border-radius: 1.5rem;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.08);
            position: relative;
            z-index: 1;
        }

        /* Standard Auth Card (for Single-Card pages like Login & Forgot Password) */
        .auth-card {
            background: #ffffff;
            border-radius: 1.35rem;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.65), 0 0 0 1px rgba(255, 255, 255, 0.08);
            max-width: 440px;
            margin: 0 auto;
            width: 100%;
            position: relative;
            z-index: 1;
        }

        /* Left Hero Showcase */
        .auth-brand-side {
            background: linear-gradient(155deg, #171920 0%, #1f222b 60%, #121318 100%);
            color: #ffffff;
            position: relative;
            overflow: hidden;
        }

        .auth-brand-side::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 80% 20%, rgba(255, 204, 0, 0.22) 0%, transparent 60%);
            pointer-events: none;
        }

        .brand-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.35rem 0.85rem;
            background: rgba(255, 204, 0, 0.15);
            border: 1px solid rgba(255, 204, 0, 0.35);
            color: var(--brand-yellow);
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .highlight-yellow {
            color: var(--brand-yellow);
            position: relative;
            display: inline-block;
        }

        .auth-brand-heading {
            font-size: 1.85rem;
            font-weight: 800;
            line-height: 1.25;
            letter-spacing: -0.02em;
        }

        .auth-brand-subtext {
            color: #a0aec0;
            font-size: 0.935rem;
            line-height: 1.6;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 0.85rem;
        }

        .feature-icon-box {
            width: 38px;
            height: 38px;
            min-width: 38px;
            border-radius: 10px;
            background: rgba(255, 204, 0, 0.15);
            border: 1px solid rgba(255, 204, 0, 0.3);
            color: var(--brand-yellow);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
        }

        .feature-title {
            font-size: 0.925rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.15rem;
        }

        .feature-desc {
            font-size: 0.82rem;
            color: #94a3b8;
            margin-bottom: 0;
            line-height: 1.4;
        }

        .brand-trust-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 0.85rem 1rem;
        }

        /* Right Form Side */
        .auth-form-side {
            background: #ffffff;
        }

        .auth-logo-img {
            max-height: 52px;
            width: auto;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .auth-logo-img:hover {
            transform: scale(1.02);
        }

        .auth-form-title {
            font-size: 1.45rem;
            font-weight: 800;
            color: #1a1d24;
            letter-spacing: -0.02em;
        }

        /* Modern Input Styling */
        .input-group-modern {
            position: relative;
        }

        .input-group-modern .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #8c93a3;
            font-size: 1.15rem;
            pointer-events: none;
            transition: color 0.2s ease;
            z-index: 4;
        }

        .form-control-modern {
            height: 48px;
            padding-left: 42px;
            padding-right: 14px;
            border-radius: 10px;
            border: 1.5px solid #e2e8f0;
            background-color: #f8fafc;
            color: #1e293b;
            font-size: 0.925rem;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
        }

        .form-control-modern:focus {
            background-color: #ffffff;
            border-color: var(--brand-yellow) !important;
            box-shadow: 0 0 0 4px var(--brand-yellow-soft) !important;
            color: #0f172a;
        }

        .form-control-modern:focus+.input-icon,
        .input-group-modern:focus-within .input-icon {
            color: #d4a300;
        }

        .form-control-modern.is-invalid {
            border-color: #ef4444 !important;
            background-color: #fef2f2;
        }

        .form-control-modern.is-invalid:focus {
            box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.15) !important;
        }

        .input-group-modern:has(.is-invalid) .input-icon {
            color: #ef4444 !important;
        }

        .password-toggle-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            color: #8c93a3;
            cursor: pointer;
            padding: 0.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: color 0.2s ease;
            z-index: 5;
        }

        .password-toggle-btn:focus {
            outline: none !important;
            box-shadow: none !important;
        }

        .password-toggle-btn:hover {
            color: #1e293b;
        }

        /* Checkbox with #ffcc00 */
        .form-check-input:checked {
            background-color: var(--brand-yellow);
            border-color: var(--brand-yellow);
        }

        .form-check-input:focus {
            border-color: var(--brand-yellow);
            box-shadow: 0 0 0 0.25rem var(--brand-yellow-soft);
        }

        /* Buttons with #ffcc00 Brand Color */
        .btn-admin-login,
        .btn-brand-yellow {
            background-color: var(--brand-yellow) !important;
            border: 1px solid var(--brand-yellow) !important;
            color: var(--brand-dark-text) !important;
            font-weight: 800;
            font-size: 0.95rem;
            letter-spacing: 0.2px;
            padding: 0.78rem 1.5rem;
            border-radius: 10px;
            box-shadow: 0 8px 22px var(--brand-yellow-glow);
            transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-admin-login:hover,
        .btn-brand-yellow:hover {
            background-color: var(--brand-yellow-hover) !important;
            border-color: var(--brand-yellow-hover) !important;
            color: #000000 !important;
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(255, 204, 0, 0.48);
        }

        .btn-admin-login:active,
        .btn-brand-yellow:active {
            transform: translateY(0);
            background-color: var(--brand-yellow-active) !important;
            box-shadow: 0 4px 14px var(--brand-yellow-glow);
        }

        .brand-link {
            color: #715800;
            font-weight: 600;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .brand-link:hover {
            color: #b8860b;
            text-decoration: underline;
        }
    </style>
    @stack('styles')
</head>

<body class="auth-body">
    <div class="ambient-glow-1"></div>
    <div class="ambient-glow-2"></div>

    <div class="container position-relative z-1">
        @yield('content')
    </div>

    <!-- Vendor js -->
    <script src="{{ asset('backend/assets/js/vendors.min.js') }}"></script>
    <!-- App js -->
    <script src="{{ asset('backend/assets/js/app.js') }}"></script>
    @stack('scripts')
</body>

</html>
