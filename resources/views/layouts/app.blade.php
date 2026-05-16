<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Course Library') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f4f8ff;
            --bg-soft: #edf4ff;
            --bg-glow: #def7f4;
            --panel: #ffffff;
            --text: #102036;
            --muted: #5d6d84;
            --accent: #0ea395;
            --accent-dark: #0a756a;
            --line: #d3dff0;
            --line-strong: #bfd0e6;
            --danger: #be123c;
            --radius: 18px;
            --radius-sm: 12px;
            --shadow: 0 24px 50px rgba(16, 32, 54, 0.1);
            --focus-ring: 0 0 0 3px rgba(14, 163, 149, 0.24);
        }

        * { box-sizing: border-box; }
        html, body { min-height: 100%; }
        body {
            margin: 0;
            font-family: "Space Grotesk", system-ui, -apple-system, Segoe UI, sans-serif;
            color: var(--text);
            background:
                radial-gradient(46rem 46rem at 12% -5%, var(--bg-glow) 0%, transparent 58%),
                radial-gradient(38rem 38rem at 92% 2%, #dbe8ff 0%, transparent 60%),
                linear-gradient(180deg, #f7fbff 0%, var(--bg) 52%, var(--bg-soft) 100%);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }
        body::before,
        body::after {
            content: "";
            position: fixed;
            border-radius: 999px;
            pointer-events: none;
            z-index: -1;
            filter: blur(20px);
            opacity: 0.5;
        }
        body::before {
            width: 320px;
            height: 320px;
            right: -80px;
            bottom: 6vh;
            background: radial-gradient(circle at 35% 40%, rgba(14, 163, 149, 0.25), rgba(14, 163, 149, 0));
        }
        body::after {
            width: 260px;
            height: 260px;
            left: -90px;
            top: 28vh;
            background: radial-gradient(circle at 50% 50%, rgba(255, 166, 66, 0.18), rgba(255, 166, 66, 0));
        }
        a { color: inherit; text-decoration: none; }
        h1, h2, h3, h4 { font-family: "Sora", "Space Grotesk", sans-serif; }
        .app-shell { width: min(1320px, 95vw); margin: 0 auto; padding: 24px 0 38px; }
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            margin-bottom: 22px;
            padding: 14px 20px;
            border: 1px solid var(--line-strong);
            border-radius: var(--radius);
            background: linear-gradient(140deg, rgba(255, 255, 255, 0.94), rgba(255, 255, 255, 0.82));
            backdrop-filter: blur(10px);
            box-shadow: 0 14px 30px rgba(16, 32, 54, 0.08);
        }
        .brand { font-weight: 800; letter-spacing: 0.02em; font-family: "Sora", "Space Grotesk", sans-serif; }
        .brand span { color: var(--accent-dark); }
        .top-actions { display: flex; align-items: center; gap: 12px; }
        .muted { color: var(--muted); font-size: 0.92rem; }
        .kicker {
            display: inline-block;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 700;
            color: #0c5d80;
            margin: 0;
        }
        .hero-title {
            margin: 6px 0 8px;
            font-size: clamp(1.45rem, 3.4vw, 2.1rem);
            line-height: 1.18;
        }
        .hero-subtitle {
            margin: 0;
            color: var(--muted);
            max-width: 72ch;
        }
        .hero {
            padding: 20px 22px;
            margin-bottom: 16px;
        }
        .hero-main {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            flex-wrap: wrap;
        }
        .meta-row {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 12px;
        }
        .btn {
            border: 0;
            border-radius: 11px;
            background: linear-gradient(135deg, var(--accent), #0b8b80);
            color: #fff;
            padding: 9px 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.2s ease, filter 0.2s ease;
            box-shadow: 0 8px 20px rgba(14, 163, 149, 0.3);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn:hover { transform: translateY(-1px); filter: brightness(1.02); }
        .btn:active { transform: translateY(0); }
        .btn-outline {
            background: rgba(255, 255, 255, 0.66);
            color: var(--accent-dark);
            border: 1px solid rgba(10, 117, 106, 0.42);
            box-shadow: none;
        }
        .btn-outline:hover { background: rgba(225, 245, 242, 0.92); }
        .btn-link {
            background: transparent;
            border: 1px solid transparent;
            color: var(--accent-dark);
            box-shadow: none;
            padding: 6px 10px;
        }
        .btn-link:hover { background: rgba(12, 93, 128, 0.08); transform: none; }
        .panel {
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: var(--panel);
            box-shadow: var(--shadow);
        }
        .danger { color: var(--danger); }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 10px;
            border-radius: 999px;
            font-size: 0.78rem;
            background: #e7fbf8;
            color: #0f766e;
            font-weight: 600;
            border: 1px solid rgba(14, 163, 149, 0.22);
        }
        .badge-info {
            background: #e9efff;
            color: #27458a;
            border-color: rgba(39, 69, 138, 0.2);
        }
        .badge-warm {
            background: #fff2dc;
            color: #965d00;
            border-color: rgba(150, 93, 0, 0.2);
        }
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
            gap: 14px;
        }
        .field { display: grid; gap: 6px; }
        .input {
            width: 100%;
            padding: 11px 12px;
            border: 1px solid var(--line);
            border-radius: 10px;
            color: var(--text);
            background: #ffffff;
            font: inherit;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }
        .input:focus {
            outline: none;
            border-color: #66b8af;
            box-shadow: var(--focus-ring);
        }
        input[type="checkbox"] { accent-color: var(--accent-dark); }
        .empty-state { padding: 22px; }
        .code-snippet {
            margin: 10px 0 0;
            background: #0f1d2f;
            color: #ccf6de;
            padding: 12px;
            border-radius: 10px;
            overflow: auto;
            font-size: 0.9rem;
        }
        .link-back {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.9rem;
            color: #325486;
            font-weight: 600;
        }
        .link-back:hover { color: #1d3760; }
        .resource-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            border: 1px solid var(--line);
            border-radius: 11px;
            padding: 9px 11px;
            background: #fbfdff;
        }
        .resource-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }
        .link-ghost {
            font-size: 0.82rem;
            color: #27507f;
            font-weight: 600;
        }
        .link-ghost:hover { color: #173253; }
        .slide-up {
            animation: slideUp 0.45s ease both;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @media (max-width: 760px) {
            .topbar { flex-wrap: wrap; padding: 12px 14px; }
            .app-shell { width: min(1320px, 97vw); padding-top: 12px; }
            .top-actions { width: 100%; justify-content: space-between; }
            .hero { padding: 16px; }
            .card-grid { grid-template-columns: 1fr; }
        }
        @media (prefers-reduced-motion: reduce) {
            * { animation: none !important; transition: none !important; }
        }
    </style>
    @stack('head')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="app-shell">
    @auth
        <header class="topbar">
            <a href="{{ route('courses.index') }}" class="brand">Private <span>Course</span> Library</a>
            <div class="top-actions">
                <span class="muted">{{ auth()->user()->email }}</span>
                <form method="post" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline">Log out</button>
                </form>
            </div>
        </header>
    @endauth

    @yield('content')
</div>
</body>
</html>
