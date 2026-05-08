@php($title = $course->display_title.' | Course Library')
@extends('layouts.app')

@section('content')
    <section class="panel" style="padding:20px; margin-bottom:16px;">
        <div style="display:flex; justify-content:space-between; gap:10px; align-items:flex-start; flex-wrap:wrap;">
            <div>
                <a href="{{ route('courses.index') }}" class="muted">&larr; All courses</a>
                <h1 style="margin:8px 0 6px;">{{ $course->display_title }}</h1>
                <div class="muted">{{ $course->relative_path }}</div>
            </div>
            @if($firstLesson)
                <a class="btn" href="{{ route('lessons.show', $firstLesson) }}">Start Watching</a>
            @endif
        </div>
    </section>

    @if($course->resources->isNotEmpty())
        <section class="panel" style="padding:16px; margin-bottom:14px;">
            <h2 style="margin:0 0 10px; font-size:1rem;">Course-level Resources</h2>
            <div style="display:grid; gap:8px;">
                @foreach($course->resources as $resource)
                    @include('partials.resource-item', ['resource' => $resource])
                @endforeach
            </div>
        </section>
    @endif

    <section class="panel" style="padding:14px;">
        <h2 style="margin:4px 8px 12px;">Sections</h2>
        <div style="display:grid; gap:12px;">
            @foreach($course->sections as $section)
                <article style="border:1px solid var(--line); border-radius:12px; padding:12px;">
                    <h3 style="margin:0 0 10px; font-size:1rem;">
                        {{ $section->relative_path === \App\Models\CourseSection::ROOT_RELATIVE_PATH ? 'General' : $section->display_title }}
                    </h3>

                    @if($section->lessons->isEmpty())
                        <p class="muted" style="margin:0 0 8px;">No videos in this section.</p>
                    @else
                        <div style="display:grid; gap:8px;">
                            @foreach($section->lessons as $lesson)
                                <a href="{{ route('lessons.show', $lesson) }}" style="display:flex; align-items:center; justify-content:space-between; gap:10px; border:1px solid var(--line); padding:9px 10px; border-radius:10px;">
                                    <span>{{ $lesson->display_title }}</span>
                                    <span class="muted" style="font-size:0.85rem;">
                                        @if($lesson->duration_seconds)
                                            {{ gmdate('H:i:s', (int) $lesson->duration_seconds) }}
                                        @else
                                            video
                                        @endif
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    @if($section->resources->isNotEmpty())
                        <div style="margin-top:12px;">
                            <div class="muted" style="margin-bottom:6px; font-size:0.85rem;">Section resources</div>
                            <div style="display:grid; gap:6px;">
                                @foreach($section->resources as $resource)
                                    @include('partials.resource-item', ['resource' => $resource])
                                @endforeach
                            </div>
                        </div>
                    @endif
                </article>
            @endforeach
        </div>
    </section>

    @include('partials.resource-preview-modal')
@endsection
