@php($title = 'Login | Course Library')
@extends('layouts.app')

@push('head')
    <style>
        .auth-wrap {
            display: grid;
            place-items: center;
            min-height: 78vh;
        }
        .auth-shell {
            width: min(960px, 96vw);
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(340px, 0.9fr);
            gap: 14px;
        }
        .auth-intro {
            padding: 24px;
            background:
                radial-gradient(140% 140% at 100% 0%, rgba(14, 163, 149, 0.16), transparent 58%),
                linear-gradient(160deg, #f8fcff 0%, #eef8ff 100%);
        }
        .auth-intro h1 {
            margin: 8px 0 10px;
            font-size: clamp(1.45rem, 3vw, 2rem);
            line-height: 1.2;
        }
        .auth-intro p {
            margin: 0;
            color: var(--muted);
            line-height: 1.5;
        }
        .auth-card { padding: 24px; }
        .auth-form { display: grid; gap: 14px; }
        .remember-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.92rem;
        }
        @media (max-width: 840px) {
            .auth-shell { grid-template-columns: 1fr; }
            .auth-intro { padding: 18px; }
            .auth-card { padding: 18px; }
        }
    </style>
@endpush

@section('content')
    <div class="auth-wrap">
        <section class="auth-shell slide-up">
            <aside class="panel auth-intro">
                <p class="kicker">Private Access</p>
                <h1>Welcome back to your course vault</h1>
                <p>Sign in to continue streaming lessons, download resources, and track your learning progress.</p>
            </aside>

            <article class="panel auth-card">
                <h2 style="margin:0 0 6px; font-size:1.35rem;">Sign In</h2>
                <p class="muted" style="margin:0 0 18px;">Private access only.</p>

                <form method="post" action="{{ route('login.store') }}" class="auth-form">
                    @csrf

                    <label class="field">
                        <span>Email</span>
                        <input
                            class="input"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="email"
                            placeholder="you@example.com"
                        >
                    </label>
                    @error('email')
                        <div class="danger" style="font-size:0.9rem;">{{ $message }}</div>
                    @enderror

                    <label class="field">
                        <span>Password</span>
                        <input
                            class="input"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                        >
                    </label>
                    @error('password')
                        <div class="danger" style="font-size:0.9rem;">{{ $message }}</div>
                    @enderror

                    <label class="remember-row">
                        <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                        <span>Remember me</span>
                    </label>

                    <button class="btn" type="submit" style="margin-top:8px;">Log in</button>
                </form>
            </article>
        </section>
    </div>
@endsection
