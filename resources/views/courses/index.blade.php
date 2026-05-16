@php($title = 'Courses | Course Library')
@extends('layouts.app')

@push('head')
    <style>
        .course-card {
            padding: 16px;
            display: grid;
            gap: 10px;
            background:
                radial-gradient(130% 120% at 100% 0%, rgba(14, 163, 149, 0.12), transparent 55%),
                #ffffff;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .course-card h2 {
            margin: 0;
            font-size: 1.06rem;
            line-height: 1.35;
        }
        .course-card-path {
            font-size: 0.82rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .hero-metrics {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        @media (hover: hover) {
            .course-card:hover {
                transform: translateY(-2px);
                box-shadow: 0 20px 34px rgba(16, 32, 54, 0.12);
            }
        }
    </style>
@endpush

@section('content')
    <section class="panel hero slide-up">
        <div class="hero-main">
            <div>
                <p class="kicker">Library</p>
                <h1 class="hero-title">Course Vault</h1>
                <p class="hero-subtitle">Browse imported courses and continue learning where you left off.</p>
            </div>
            <div class="hero-metrics">
                <span class="badge">{{ $courses->count() }} courses</span>
                <span class="badge badge-info">{{ $courses->sum('lessons_count') }} lessons</span>
                <span class="badge badge-warm">{{ $courses->sum('resources_count') }} resources</span>
            </div>
        </div>
    </section>

    @if($courses->isEmpty())
        <section class="panel empty-state slide-up">
            <p style="margin:0;">No courses found yet. Run:</p>
            <pre class="code-snippet">php artisan courses:scan {{ config('courses.root') }}</pre>
        </section>
    @else
        <section class="card-grid">
            @foreach($courses as $course)
                <article class="panel course-card slide-up">
                    <h2>{{ $course->display_title }}</h2>
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <span class="badge">{{ $course->lessons_count }} lessons</span>
                        <span class="badge badge-info">{{ $course->resources_count }} resources</span>
                    </div>
                    <div class="muted course-card-path">
                        {{ $course->relative_path }}
                    </div>
                    <div>
                        <a class="btn" href="{{ route('courses.show', $course) }}">Open Course</a>
                    </div>
                </article>
            @endforeach
        </section>
    @endif
@endsection
