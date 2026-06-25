<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ $direction ?? 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <meta name="description" content="CESA — Portal Aplikasi Internal Complete Selular">
    <title>CESA — Portal Aplikasi Internal</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        html, body { height: 100%; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: #EFF6FF;
            color: #111827;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* ===== LAYOUT ===== */
        .page-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main-content {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-top: 32px;
            padding-bottom: 32px;
        }

        .container {
            width: 100%;
            max-width: 896px; /* max-w-4xl */
            margin: 0 auto;
            padding-left: 16px;
            padding-right: 16px;
        }

        /* ===== NAV BAR ===== */
        .nav-bar {
            padding-top: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #E5E7EB;
            background: #fff;
        }

        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .nav-brand-logo {
            width: 36px;
            height: 36px;
            background: #2563EB;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 800;
            color: #fff;
            flex-shrink: 0;
        }

        .nav-brand-name {
            font-size: 15px;
            font-weight: 700;
            color: #1F2937;
            letter-spacing: -0.2px;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 18px;
            font-size: 14px;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .btn-primary {
            background: #2563EB;
            color: #fff;
        }

        .btn-primary:hover {
            background: #1D4ED8;
        }

        .btn-ghost {
            background: #fff;
            color: #374151;
            border: 1px solid #D1D5DB;
        }

        .btn-ghost:hover {
            background: #F3F4F6;
        }

        .btn svg {
            width: 14px;
            height: 14px;
            flex-shrink: 0;
        }

        /* ===== APPS GRID ===== */
        .apps-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 16px;
        }

        /* ===== APP CARD (icon tile) ===== */
        .app-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            background: #fff;
            border: 1px solid #E5E7EB;
            border-radius: 12px;
            padding: 20px 12px 16px;
            text-decoration: none;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            transition: background-color 0.15s ease, border-color 0.15s ease;
            cursor: pointer;
        }

        .app-card:hover {
            background-color: #F9FAFB;
            border-color: #D1D5DB;
        }

        /* Icon */
        .app-card-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .app-card-icon svg {
            width: 26px;
            height: 26px;
            color: #fff;
        }

        .app-card-image {
            width: 52px;
            height: 52px;
            object-fit: contain;
            flex-shrink: 0;
        }

        /* Name */
        .app-card-name {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            text-align: center;
            line-height: 1.3;
            word-break: break-word;
            transition: color 0.15s ease;
        }

        .app-card:hover .app-card-name {
            color: #111827;
        }

        /* Icon color variants */
        .ic-blue    { background: #2563EB; }
        .ic-teal    { background: #0D9488; }
        .ic-purple  { background: #7C3AED; }
        .ic-pink    { background: #DB2777; }
        .ic-green   { background: #16A34A; }
        .ic-yellow  { background: #D97706; }
        .ic-red     { background: #DC2626; }
        .ic-slate   { background: #64748B; }
        .ic-orange  { background: #EA580C; }
        .ic-amber   { background: #CA8A04; }
        .ic-indigo  { background: #4F46E5; }
        .ic-navy    { background: #1E3A8A; }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            background: #fff;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            padding: 48px 24px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }

        .empty-icon {
            width: 48px;
            height: 48px;
            background: #F3F4F6;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .empty-icon svg {
            width: 24px;
            height: 24px;
            color: #9CA3AF;
        }

        .empty-title {
            font-size: 15px;
            font-weight: 600;
            color: #374151;
            margin-bottom: 4px;
        }

        .empty-desc {
            font-size: 13px;
            color: #6B7280;
            max-width: 300px;
            margin: 0 auto 16px;
        }

        /* ===== FOOTER ===== */
        .footer {
            padding: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-top: 1px solid #E5E7EB;
        }

        .footer-text {
            font-size: 12px;
            color: #6B7280;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 960px) {
            .apps-grid { grid-template-columns: repeat(4, 1fr); }
        }

        @media (max-width: 640px) {
            .apps-grid { grid-template-columns: repeat(3, 1fr); gap: 12px; }
            .app-card { padding: 16px 8px 12px; }
            .app-card-icon { width: 44px; height: 44px; border-radius: 12px; }
            .app-card-icon svg { width: 22px; height: 22px; }
            .app-card-image { width: 44px; height: 44px; }
            .app-card-name { font-size: 12px; }
            .nav-brand-name { display: none; }
        }

        /* ===== FLOATING WHATSAPP BUTTON (CIRCLE WIDGET) ===== */
        .whatsapp-btn {
            position: fixed;
            bottom: 40px;
            right: 24px;
            width: 56px;
            height: 56px;
            background-color: #ffffff;
            border: 1px solid #E5E7EB;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 9999;
            text-decoration: none;
        }

        .whatsapp-btn:hover {
            transform: scale(1.08) translateY(-3px);
            box-shadow: 0 12px 32px rgba(0, 0, 0, 0.16);
            border-color: #D1D5DB;
        }

        .whatsapp-btn svg {
            width: 28px;
            height: 28px;
        }

        .whatsapp-badge {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 12px;
            height: 12px;
            background-color: #EF4444;
            border: 2px solid #ffffff;
            border-radius: 50%;
            animation: whatsapp-pulse 2s infinite;
        }

        @keyframes whatsapp-pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7);
            }
            70% {
                box-shadow: 0 0 0 8px rgba(239, 68, 68, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0);
            }
        }

        @media (max-width: 640px) {
            .whatsapp-btn {
                bottom: 32px;
                right: 16px;
                width: 48px;
                height: 48px;
            }
            .whatsapp-btn svg {
                width: 24px;
                height: 24px;
            }
            .whatsapp-badge {
                width: 10px;
                height: 10px;
                top: 1px;
                right: 1px;
            }
        }
    </style>
</head>
<body>
<div class="page-wrapper">

    <!-- HEADER -->
    <header class="nav-bar">
        <div class="container" style="display:flex;align-items:center;justify-content:space-between;">
            <a href="/" class="nav-brand">
                @if(file_exists(public_path('images/logo.png')))
                    <img src="{{ asset('images/logo.png') }}" alt="CESA" style="height:36px;width:auto;border-radius:8px;">
                @else
                    <div class="nav-brand-logo">C</div>
                    <span class="nav-brand-name">CESA</span>
                @endif
            </a>

            <div class="nav-actions">
                @auth
                    <a href="/admin" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                            <rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>
                        </svg>
                        Dashboard
                    </a>
                @else
                    <a href="/admin/login" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/>
                        </svg>
                        Masuk
                    </a>
                @endauth
            </div>
        </div>
    </header>

    <!-- MAIN (centered) -->
    <main class="main-content">
        <div class="container">
            @if(count($apps) > 0)
                <div class="apps-grid">
                    @foreach($apps as $app)
                        @php
                            $isExternal = false;
                            if (! empty($app['url'])) {
                                $urlHost = parse_url($app['url'], PHP_URL_HOST);
                                if ($urlHost && $urlHost !== request()->getHost()) {
                                    $isExternal = true;
                                }
                                if ($app['key'] === 'sam') {
                                    $isExternal = true;
                                }
                            }
                        @endphp
                        <a
                            href="{{ $app['url'] }}"
                            class="app-card"
                            title="{{ $app['description'] }}"
                            id="app-{{ $app['key'] }}"
                            @if ($isExternal)
                                target="_blank"
                                rel="noopener noreferrer"
                            @endif
                        >
                            @if(file_exists(public_path('svg/' . $app['key'] . '.svg')))
                                <img src="{{ asset('svg/' . $app['key'] . '.svg') }}" class="app-card-image" alt="{{ $app['name'] }}">
                            @else
                                <div class="app-card-icon ic-{{ $app['color'] }}">
                                    @include('cesa-home-icons', ['icon' => $app['icon']])
                                </div>
                            @endif
                            <span class="app-card-name">{{ $app['name'] }}</span>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <div class="empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                    </div>
                    <p class="empty-title">Belum ada plugin aktif</p>
                    <p class="empty-desc">Instal plugin CESA untuk menampilkan aplikasi di sini.</p>
                    <a href="/admin/plugins" class="btn btn-primary">Kelola Plugin</a>
                </div>
            @endif
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="footer">
        <span class="footer-text">© {{ date('Y') }} IT Support — CESA</span>
    </footer>

    <!-- FLOATING WHATSAPP BUTTON -->
    <a href="https://wa.me/{{ env('SUPPORT_WHATSAPP_NUMBER', '628112444444') }}" class="whatsapp-btn" target="_blank" rel="noopener noreferrer" title="Hubungi IT Assistant via WhatsApp">
        <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" fill="#25D366"/>
        </svg>
        <div class="whatsapp-badge"></div>
    </a>
</div>
</body>
</html>
