<!DOCTYPE html>
<html lang="en" class="antialiased dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- Apply persisted theme before paint to avoid a flash. Dark is default. --}}
    <script>
        (function () {
            try {
                var t = localStorage.getItem('theme') || 'dark';
                var d = document.documentElement;
                d.classList.toggle('dark', t !== 'light');
                d.classList.toggle('light', t === 'light');
                d.style.colorScheme = t === 'light' ? 'light' : 'dark';
            } catch (e) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    <title inertia>{{ config('app.name', 'Course Library') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">

    @routes
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @inertiaHead
</head>
<body>
    @inertia
</body>
</html>
