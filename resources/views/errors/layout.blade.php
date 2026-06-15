<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Error') — {{ config('app.name') }}</title>
    @include('partials.cdn-assets')
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body { height: 100%; font-family: 'Inter', sans-serif; }

        body {
            display: flex;
            min-height: 100vh;
            background: #fafaf9;
        }

        /* ── Left dark pane ── */
        .err-left {
            display: none;
            flex-direction: column;
            justify-content: space-between;
            width: 420px;
            flex-shrink: 0;
            padding: 40px 48px;
            background: linear-gradient(160deg, #1c1917 0%, #3d0808 60%, #0f0f0e 100%);
            position: relative;
            overflow: hidden;
        }
        @media (min-width: 1024px) { .err-left { display: flex; } }

        .err-left::before {
            content: '';
            position: absolute;
            top: -120px; right: -120px;
            width: 380px; height: 380px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(153,27,27,0.35) 0%, transparent 70%);
            pointer-events: none;
        }
        .err-left::after {
            content: '';
            position: absolute;
            bottom: -80px; left: -80px;
            width: 280px; height: 280px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(127,29,29,0.2) 0%, transparent 70%);
            pointer-events: none;
        }

        .err-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
            z-index: 1;
        }
        .err-brand-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, #991b1b, #7f1d1d);
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 4px 16px rgba(0,0,0,0.4);
            border: 1px solid rgba(255,255,255,0.1);
            flex-shrink: 0;
        }
        .err-brand-icon svg { width: 20px; height: 20px; color: #fca5a5; }
        .err-brand-name { font-size: 18px; font-weight: 700; color: #fff; letter-spacing: -0.02em; }

        .err-left-body {
            position: relative;
            z-index: 1;
        }
        .err-big-code {
            font-size: 120px;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -0.06em;
            color: rgba(255,255,255,0.06);
            margin-bottom: 32px;
            user-select: none;
        }
        .err-left-title {
            font-size: 28px;
            font-weight: 700;
            color: #fff;
            line-height: 1.25;
            margin-bottom: 12px;
            letter-spacing: -0.02em;
        }
        .err-left-sub {
            font-size: 14px;
            color: rgba(255,255,255,0.45);
            line-height: 1.65;
        }

        .err-left-footer {
            position: relative;
            z-index: 1;
            font-size: 12px;
            color: rgba(255,255,255,0.25);
        }

        /* ── Right content pane ── */
        .err-right {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 32px;
            background: #fafaf9;
            position: relative;
        }

        /* subtle dot grid */
        .err-right::before {
            content: '';
            position: absolute;
            inset: 0;
            background-image: radial-gradient(circle, #d6d3d1 1px, transparent 1px);
            background-size: 28px 28px;
            opacity: 0.5;
            pointer-events: none;
        }

        .err-right-inner {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            text-align: center;
        }

        /* icon circle */
        .err-icon-wrap {
            width: 80px; height: 80px;
            margin: 0 auto 28px;
            border-radius: 24px;
            background: linear-gradient(135deg, #991b1b, #7f1d1d);
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 8px 32px rgba(127,29,29,0.35), 0 2px 8px rgba(0,0,0,0.12);
            animation: bob 4s ease-in-out infinite;
        }
        .err-icon-wrap svg { width: 36px; height: 36px; color: #fecaca; }

        @keyframes bob {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-6px); }
        }

        /* mobile-only code */
        .err-mobile-code {
            display: block;
            font-size: 72px;
            font-weight: 800;
            letter-spacing: -0.05em;
            line-height: 1;
            background: linear-gradient(135deg, #1c1917 0%, #991b1b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }
        @media (min-width: 1024px) { .err-mobile-code { display: none; } }

        .err-heading {
            font-size: 22px;
            font-weight: 700;
            color: #1c1917;
            letter-spacing: -0.02em;
            margin-bottom: 10px;
        }
        .err-desc {
            font-size: 14px;
            color: #78716c;
            line-height: 1.7;
            margin-bottom: 32px;
        }

        .err-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }

        .btn-ghost {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 20px;
            border-radius: 12px;
            border: 1.5px solid #e7e5e4;
            background: #fff;
            font-size: 13px; font-weight: 600;
            color: #44403c;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s, color 0.15s;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        .btn-ghost:hover { background: #f5f5f4; border-color: #d6d3d1; color: #1c1917; }
        .btn-ghost svg { width: 15px; height: 15px; }

        .btn-primary {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 10px 20px;
            border-radius: 12px;
            border: none;
            background: linear-gradient(135deg, #991b1b, #7f1d1d);
            font-size: 13px; font-weight: 600;
            color: #fff;
            text-decoration: none;
            cursor: pointer;
            transition: opacity 0.15s, transform 0.15s;
            box-shadow: 0 4px 14px rgba(127,29,29,0.35);
        }
        .btn-primary:hover { opacity: 0.9; transform: translateY(-1px); }
        .btn-primary svg { width: 15px; height: 15px; }

        .err-divider {
            width: 40px; height: 3px;
            background: linear-gradient(90deg, #991b1b, #7f1d1d);
            border-radius: 99px;
            margin: 0 auto 20px;
        }

        .err-help {
            margin-top: 40px;
            font-size: 12px;
            color: #a8a29e;
        }
        .err-help a { color: #991b1b; font-weight: 600; text-decoration: none; }
        .err-help a:hover { text-decoration: underline; }

        /* mobile top bar */
        .err-mobile-bar {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 40px;
        }
        @media (min-width: 1024px) { .err-mobile-bar { display: none; } }
    </style>
</head>
<body>

    {{-- ── LEFT PANE ── --}}
    <div class="err-left">
        <div class="err-brand">
            <div class="err-brand-icon">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                </svg>
            </div>
            <span class="err-brand-name">{{ config('app.name') }}</span>
        </div>

        <div class="err-left-body">
            <div class="err-big-code">@yield('code')</div>
            <div class="err-left-title">@yield('heading')</div>
            <div class="err-left-sub">@yield('description')</div>
        </div>

        <div class="err-left-footer">© {{ date('Y') }} {{ config('app.name') }} Application</div>
    </div>

    {{-- ── RIGHT PANE ── --}}
    <div class="err-right">
        <div class="err-right-inner">

            {{-- Mobile brand --}}
            <div class="err-mobile-bar">
                <div class="err-brand-icon">
                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                    </svg>
                </div>
                <span style="font-size:16px;font-weight:700;color:#1c1917;">{{ config('app.name') }}</span>
            </div>

            {{-- Floating icon --}}
            <div class="err-icon-wrap">
                @yield('icon')
            </div>

            {{-- Mobile code --}}
            <span class="err-mobile-code">@yield('code')</span>

            <div class="err-divider"></div>

            <h1 class="err-heading">@yield('heading')</h1>
            <p class="err-desc">@yield('description')</p>

            <div class="err-actions">
                @yield('actions')
            </div>

            <p class="err-help">
                Need help? <a href="mailto:support@ScanOCR.com">Contact support</a>
            </p>

        </div>
    </div>

</body>
</html>
