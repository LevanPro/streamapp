@php($title = $course->display_title.' | Course Library')
@extends('layouts.app')

@push('head')
    <style>
        .section-card {
            border: 1px solid var(--line);
            border-radius: 14px;
            padding: 12px;
            background:
                radial-gradient(120% 120% at 100% 0%, rgba(39, 69, 138, 0.08), transparent 55%),
                #ffffff;
        }
        .lesson-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            border: 1px solid var(--line);
            padding: 10px 11px;
            border-radius: 10px;
            background: #fbfdff;
            transition: border-color 0.16s ease, transform 0.16s ease;
        }
        .lesson-row-name { font-size: 0.93rem; }
        .section-resources {
            margin-top: 12px;
        }
        .section-resources-head {
            margin-bottom: 6px;
            font-size: 0.85rem;
        }
        @media (hover: hover) {
            .lesson-row:hover {
                transform: translateY(-1px);
                border-color: var(--line-strong);
            }
        }
    </style>
@endpush

@section('content')
    <section class="panel hero slide-up">
        <div class="hero-main">
            <div>
                <a href="{{ route('courses.index') }}" class="link-back">&larr; All courses</a>
                <h1 class="hero-title">{{ $course->display_title }}</h1>
                <div class="muted">{{ $course->relative_path }}</div>
                <div class="meta-row">
                    <span class="badge">{{ $course->sections->count() }} sections</span>
                    <span class="badge badge-info">{{ $course->sections->sum(fn ($section) => $section->lessons->count()) }} lessons</span>
                    <span class="badge badge-warm">{{ $course->resources->count() + $course->sections->sum(fn ($section) => $section->resources->count()) }} resources</span>
                </div>
            </div>
            @if($firstLesson)
                <a class="btn" href="{{ route('lessons.show', $firstLesson) }}">Start Watching</a>
            @endif
        </div>
    </section>

    @if($course->resources->isNotEmpty())
        <section class="panel slide-up" style="padding:16px; margin-bottom:14px;">
            <h2 style="margin:0 0 10px; font-size:1rem;">Course-level Resources</h2>
            <div style="display:grid; gap:8px;">
                @foreach($course->resources as $resource)
                    @include('partials.resource-item', ['resource' => $resource])
                @endforeach
            </div>
        </section>
    @endif

    <section class="panel slide-up" style="padding:14px;">
        <h2 style="margin:4px 8px 12px;">Sections</h2>
        <div style="display:grid; gap:12px;">
            @foreach($course->sections as $section)
                <article class="section-card">
                    <h3 style="margin:0 0 10px; font-size:1rem;">
                        {{ $section->relative_path === \App\Models\CourseSection::ROOT_RELATIVE_PATH ? 'General' : $section->display_title }}
                    </h3>

                    @if($section->lessons->isEmpty())
                        <p class="muted" style="margin:0 0 8px;">No videos in this section.</p>
                    @else
                        <div style="display:grid; gap:8px;">
                            @foreach($section->lessons as $lesson)
                                <a href="{{ route('lessons.show', $lesson) }}" class="lesson-row">
                                    <span class="lesson-row-name">{{ $lesson->display_title }}</span>
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
                        <div class="section-resources">
                            <div class="muted section-resources-head">Section resources</div>
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
