<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Course Library') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f2f5f7;
            --panel: #ffffff;
            --text: #0f1720;
            --muted: #596473;
            --accent: #0e9488;
            --accent-dark: #0a6d64;
            --line: #d9e3ea;
            --danger: #be123c;
            --radius: 14px;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Space Grotesk", system-ui, -apple-system, Segoe UI, sans-serif;
            color: var(--text);
            background: radial-gradient(circle at 15% 20%, #e3f9f5 0, #f2f5f7 40%), linear-gradient(180deg, #f7fafc 0%, #eef3f7 100%);
            min-height: 100vh;
        }
        a { color: inherit; text-decoration: none; }
        .app-shell { width: min(1300px, 96vw); margin: 0 auto; padding: 20px 0 36px; }
        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 18px;
            padding: 14px 18px;
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(7px);
        }
        .brand { font-weight: 700; letter-spacing: 0.02em; }
        .brand span { color: var(--accent-dark); }
        .top-actions { display: flex; align-items: center; gap: 12px; }
        .muted { color: var(--muted); font-size: 0.92rem; }
        .btn {
            border: 0;
            border-radius: 12px;
            background: var(--accent);
            color: #fff;
            padding: 9px 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.18s ease;
        }
        .btn:hover { background: var(--accent-dark); }
        .btn-outline {
            background: transparent;
            color: var(--accent-dark);
            border: 1px solid var(--accent-dark);
        }
        .btn-outline:hover { background: #e2f5f2; }
        .panel {
            border: 1px solid var(--line);
            border-radius: var(--radius);
            background: var(--panel);
            box-shadow: 0 12px 30px rgba(15, 23, 32, 0.05);
        }
        .danger { color: var(--danger); }
        .badge {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 0.78rem;
            background: #e6fffb;
            color: #0f766e;
            font-weight: 600;
        }
        @media (max-width: 760px) {
            .topbar { flex-wrap: wrap; }
            .app-shell { width: min(1300px, 98vw); padding-top: 12px; }
            .top-actions { width: 100%; justify-content: space-between; }
        }
    </style>
    @stack('head')
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
