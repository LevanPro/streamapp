@php($title = 'Login | Course Library')
@extends('layouts.app')

@section('content')
    <div style="display:grid; place-items:center; min-height:78vh;">
        <section class="panel" style="width:min(460px, 95vw); padding:30px;">
            <h1 style="margin:0 0 8px; font-size:1.7rem;">Sign In</h1>
            <p class="muted" style="margin:0 0 24px;">Private access only.</p>

            <form method="post" action="{{ route('login.store') }}" style="display:grid; gap:14px;">
                @csrf

                <label style="display:grid; gap:6px;">
                    <span>Email</span>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        style="padding:10px 12px; border:1px solid var(--line); border-radius:10px;"
                    >
                </label>
                @error('email')
                    <div class="danger" style="font-size:0.9rem;">{{ $message }}</div>
                @enderror

                <label style="display:grid; gap:6px;">
                    <span>Password</span>
                    <input
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        style="padding:10px 12px; border:1px solid var(--line); border-radius:10px;"
                    >
                </label>
                @error('password')
                    <div class="danger" style="font-size:0.9rem;">{{ $message }}</div>
                @enderror

                <label style="display:flex; align-items:center; gap:8px; font-size:0.92rem;">
                    <input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
                    <span>Remember me</span>
                </label>

                <button class="btn" type="submit" style="margin-top:8px;">Log in</button>
            </form>
        </section>
    </div>
@endsection
