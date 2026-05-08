@php($title = 'Courses | Course Library')
@extends('layouts.app')

@section('content')
    <section class="panel" style="padding:20px; margin-bottom:16px;">
        <h1 style="margin:0;">Courses</h1>
        <p class="muted" style="margin:8px 0 0;">Browse imported courses and continue where you left off.</p>
    </section>

    @if($courses->isEmpty())
        <section class="panel" style="padding:20px;">
            <p style="margin:0;">No courses found yet. Run:</p>
            <pre style="margin:10px 0 0; background:#0f1720; color:#d2fbe5; padding:12px; border-radius:10px; overflow:auto;">php artisan courses:scan {{ config('courses.root') }}</pre>
        </section>
    @else
        <section style="display:grid; grid-template-columns:repeat(auto-fill,minmax(290px,1fr)); gap:14px;">
            @foreach($courses as $course)
                <article class="panel" style="padding:16px; display:grid; gap:10px;">
                    <h2 style="margin:0; font-size:1.1rem; line-height:1.35;">{{ $course->display_title }}</h2>
                    <div style="display:flex; gap:8px; flex-wrap:wrap;">
                        <span class="badge">{{ $course->lessons_count }} lessons</span>
                        <span class="badge" style="background:#eaf2ff; color:#1d4ed8;">{{ $course->resources_count }} resources</span>
                    </div>
                    <div class="muted" style="font-size:0.82rem; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
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
