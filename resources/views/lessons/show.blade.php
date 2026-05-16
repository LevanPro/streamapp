@php($title = $lesson->display_title.' | '.$course->display_title)
@extends('layouts.app')

@push('head')
    <style>
        .lesson-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.85fr) minmax(290px, 1fr);
            gap: 16px;
            align-items: start;
        }
        .lesson-header {
            margin-bottom: 14px;
        }
        .lesson-main {
            padding: 16px;
            background:
                radial-gradient(120% 120% at 100% 0%, rgba(14, 163, 149, 0.12), transparent 56%),
                #ffffff;
        }
        .lesson-title {
            margin: 0 0 5px;
            font-size: clamp(1.25rem, 2.7vw, 1.62rem);
            line-height: 1.24;
        }
        .lesson-meta {
            margin-bottom: 13px;
            font-size: 0.92rem;
        }
        .video-shell {
            border: 1px solid var(--line);
            border-radius: 15px;
            overflow: hidden;
            background:
                radial-gradient(140% 100% at 100% 0%, rgba(14, 163, 149, 0.24), transparent 55%),
                #051623;
            box-shadow: 0 18px 28px rgba(5, 22, 35, 0.32);
        }
        .lesson-sidebar {
            position: sticky;
            top: 10px;
            max-height: calc(100vh - 34px);
            overflow: auto;
            padding: 12px;
            background: #fbfdff;
        }
        .section-head {
            font-size: 0.83rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #526072;
            margin: 0 0 8px;
        }
        .section-stack {
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #d8e2f0;
        }
        .section-stack:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .lesson-link {
            display: block;
            padding: 9px 10px;
            border-radius: 10px;
            border: 1px solid var(--line);
            margin-bottom: 6px;
            background: #ffffff;
            transition: border-color 0.16s ease, transform 0.16s ease;
        }
        .lesson-link.active {
            border-color: #11867b;
            background: #def8f4;
            font-weight: 700;
        }
        .lesson-link-title { font-size: 0.92rem; }
        .lesson-link-meta { font-size: 0.76rem; }
        @media (hover: hover) {
            .lesson-link:hover {
                transform: translateY(-1px);
                border-color: var(--line-strong);
            }
        }
        .timeline-preview {
            margin-top: 10px;
            position: relative;
            padding: 16px 0 4px;
            touch-action: none;
            user-select: none;
        }
        .timeline-track {
            position: relative;
            height: 11px;
            border-radius: 999px;
            border: 1px solid #9fb6ca;
            background: #cfdeea;
            overflow: hidden;
            cursor: pointer;
        }
        .timeline-progress {
            position: absolute;
            inset: 0 auto 0 0;
            width: 0;
            border-radius: 999px;
            background: linear-gradient(90deg, #14988b, #0f766e);
        }
        .timeline-thumb {
            position: absolute;
            top: 50%;
            left: 0;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            border: 2px solid #ffffff;
            background: #0f766e;
            transform: translate(-50%, -50%);
            box-shadow: 0 1px 6px rgba(7, 14, 20, 0.25);
            pointer-events: none;
        }
        .timeline-hover {
            position: absolute;
            bottom: 26px;
            left: 0;
            display: none;
            transform: translateX(-50%);
            border-radius: 10px;
            border: 1px solid rgba(16, 31, 43, 0.4);
            overflow: hidden;
            background: #08131a;
            box-shadow: 0 12px 28px rgba(4, 9, 14, 0.35);
            pointer-events: none;
            z-index: 2;
        }
        .timeline-hover-image {
            width: 160px;
            height: 90px;
            background-repeat: no-repeat;
            background-size: auto;
            background-position: 0 0;
            background-color: #06121a;
        }
        .timeline-hover-time {
            padding: 5px 8px;
            font-size: 0.72rem;
            text-align: center;
            color: #e3edf3;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            font-variant-numeric: tabular-nums;
        }
        .resume-banner {
            margin-top: 10px;
            padding: 10px;
            display: none;
            border-color: #89e6dc;
            background: linear-gradient(160deg, #f3fffd, #e2fbf7);
        }
        .resume-banner-body {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap;
        }
        .lesson-nav {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 12px;
        }
        .resource-section {
            margin-top: 16px;
        }
        .resource-title {
            margin: 0 0 8px;
            font-size: 1.02rem;
        }
        @media (max-width: 1100px) {
            .lesson-grid { grid-template-columns: 1fr; }
            .lesson-sidebar { position: static; max-height: none; }
        }
    </style>
@endpush

@section('content')
    @php($activeResources = $activeResources ?? collect())
    <div class="lesson-header">
        <a href="{{ route('courses.show', $course) }}" class="link-back">&larr; {{ $course->display_title }}</a>
    </div>

    <section class="lesson-grid">
        <article class="panel lesson-main">
            <h1 class="lesson-title">{{ $lesson->display_title }}</h1>
            <div class="muted lesson-meta">
                {{ $lesson->section?->display_title ?? 'General' }}
                @if($lesson->duration_seconds)
                    &middot; {{ gmdate('H:i:s', (int) $lesson->duration_seconds) }}
                @endif
            </div>

            <div class="video-shell">
                <video
                    id="lessonPlayer"
                    controls
                    preload="metadata"
                    playsinline
                    style="width:100%; height:auto;"
                    src="{{ route('stream.lessons', $lesson) }}"
                    data-progress-endpoint="{{ route('progress.store', $lesson) }}"
                    data-saved-position="{{ (float) ($progress?->last_position_seconds ?? 0) }}"
                    @if($lesson->thumbnail_path) poster="{{ route('lessons.thumbnail', $lesson) }}" @endif
                ></video>
            </div>
            <div
                id="lessonTimelinePreview"
                class="timeline-preview"
                data-manifest-url="{{ route('lessons.preview.manifest', $lesson) }}"
                aria-label="Timeline preview"
                hidden
            >
                <div id="timelineHover" class="timeline-hover">
                    <div id="timelineHoverImage" class="timeline-hover-image"></div>
                    <div id="timelineHoverTime" class="timeline-hover-time">00:00</div>
                </div>
                <div id="timelineTrack" class="timeline-track" role="slider" tabindex="0" aria-valuemin="0" aria-valuemax="0" aria-valuenow="0" aria-label="Seek lesson">
                    <div id="timelineProgress" class="timeline-progress"></div>
                    <div id="timelineThumb" class="timeline-thumb"></div>
                </div>
            </div>

            <div id="resumeBanner" class="panel resume-banner">
                <div class="resume-banner-body">
                    <span>Resume from your last position?</span>
                    <button id="resumeButton" class="btn" type="button">Resume</button>
                </div>
            </div>

            <div class="lesson-nav">
                @if($previousLesson)
                    <a class="btn btn-outline" href="{{ route('lessons.show', $previousLesson) }}">Previous Lesson</a>
                @endif
                @if($nextLesson)
                    <a class="btn" href="{{ route('lessons.show', $nextLesson) }}">Next Lesson</a>
                @endif
            </div>

            @if($activeResources->isNotEmpty())
                <section class="resource-section">
                    <h2 class="resource-title">Lesson Resources</h2>
                    <div style="display:grid; gap:7px;">
                        @foreach($activeResources as $resource)
                            @include('partials.resource-item', ['resource' => $resource])
                        @endforeach
                    </div>
                </section>
            @endif
        </article>

        <aside class="panel lesson-sidebar">
            @foreach($course->sections as $section)
                <section class="section-stack">
                    <h3 class="section-head">
                        {{ $section->relative_path === \App\Models\CourseSection::ROOT_RELATIVE_PATH ? 'General' : $section->display_title }}
                    </h3>
                    @foreach($section->lessons as $sectionLesson)
                        <a
                            href="{{ route('lessons.show', $sectionLesson) }}"
                            class="lesson-link {{ $sectionLesson->id === $lesson->id ? 'active' : '' }}"
                        >
                            <div class="lesson-link-title">{{ $sectionLesson->display_title }}</div>
                            <div class="muted lesson-link-meta">
                                @if($sectionLesson->duration_seconds)
                                    {{ gmdate('H:i:s', (int) $sectionLesson->duration_seconds) }}
                                @else
                                    video
                                @endif
                            </div>
                        </a>
                    @endforeach
                </section>
            @endforeach
        </aside>
    </section>

    @include('partials.resource-preview-modal')
@endsection
