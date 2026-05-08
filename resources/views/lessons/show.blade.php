@php($title = $lesson->display_title.' | '.$course->display_title)
@extends('layouts.app')

@push('head')
    <style>
        .lesson-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.9fr) minmax(290px, 1fr);
            gap: 14px;
            align-items: start;
        }
        .video-shell {
            border: 1px solid var(--line);
            border-radius: 14px;
            overflow: hidden;
            background: #06121a;
        }
        .lesson-sidebar {
            position: sticky;
            top: 10px;
            max-height: calc(100vh - 34px);
            overflow: auto;
        }
        .section-head {
            font-size: 0.83rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #526072;
            margin: 0 0 8px;
        }
        .lesson-link {
            display: block;
            padding: 9px 10px;
            border-radius: 8px;
            border: 1px solid var(--line);
            margin-bottom: 6px;
        }
        .lesson-link.active {
            border-color: #11867b;
            background: #e2f7f4;
            font-weight: 700;
        }
        @media (max-width: 1100px) {
            .lesson-grid { grid-template-columns: 1fr; }
            .lesson-sidebar { position: static; max-height: none; }
        }
    </style>
@endpush

@section('content')
    @php($activeResources = $activeResources ?? collect())
    <div style="margin-bottom:14px;">
        <a href="{{ route('courses.show', $course) }}" class="muted">&larr; {{ $course->display_title }}</a>
    </div>

    <section class="lesson-grid">
        <article class="panel" style="padding:14px;">
            <h1 style="margin:0 0 4px; font-size:1.35rem;">{{ $lesson->display_title }}</h1>
            <div class="muted" style="margin-bottom:12px;">
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
                    @if($lesson->thumbnail_path) poster="{{ route('lessons.thumbnail', $lesson) }}" @endif
                ></video>
            </div>

            <div id="resumeBanner" class="panel" style="margin-top:10px; padding:10px; display:none; border-color:#a5f3eb;">
                <div style="display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap;">
                    <span>Resume from your last position?</span>
                    <button id="resumeButton" class="btn" type="button">Resume</button>
                </div>
            </div>

            <div style="display:flex; gap:8px; flex-wrap:wrap; margin-top:12px;">
                @if($previousLesson)
                    <a class="btn btn-outline" href="{{ route('lessons.show', $previousLesson) }}">Previous Lesson</a>
                @endif
                @if($nextLesson)
                    <a class="btn" href="{{ route('lessons.show', $nextLesson) }}">Next Lesson</a>
                @endif
            </div>

            @if($activeResources->isNotEmpty())
                <section style="margin-top:16px;">
                    <h2 style="margin:0 0 8px; font-size:1.02rem;">Lesson Resources</h2>
                    <div style="display:grid; gap:7px;">
                        @foreach($activeResources as $resource)
                            @include('partials.resource-item', ['resource' => $resource])
                        @endforeach
                    </div>
                </section>
            @endif
        </article>

        <aside class="panel lesson-sidebar" style="padding:12px;">
            @foreach($course->sections as $section)
                <section style="margin-bottom:12px;">
                    <h3 class="section-head" style="margin-top:0;">
                        {{ $section->relative_path === \App\Models\CourseSection::ROOT_RELATIVE_PATH ? 'General' : $section->display_title }}
                    </h3>
                    @foreach($section->lessons as $sectionLesson)
                        <a
                            href="{{ route('lessons.show', $sectionLesson) }}"
                            class="lesson-link {{ $sectionLesson->id === $lesson->id ? 'active' : '' }}"
                        >
                            <div style="font-size:0.92rem;">{{ $sectionLesson->display_title }}</div>
                            <div class="muted" style="font-size:0.76rem;">
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

    <script>
        (() => {
            const player = document.getElementById('lessonPlayer');
            if (!player) return;

            const resumeBanner = document.getElementById('resumeBanner');
            const resumeButton = document.getElementById('resumeButton');
            const endpoint = @json(route('progress.store', $lesson));
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
            const savedPosition = Number(@json((float) ($progress?->last_position_seconds ?? 0)));

            if (savedPosition > 10) {
                resumeBanner.style.display = 'block';
                resumeButton.addEventListener('click', () => {
                    player.currentTime = savedPosition;
                    resumeBanner.style.display = 'none';
                    player.play().catch(() => {});
                });
            }

            let lastSavedAt = 0;

            const saveProgress = () => {
                if (!Number.isFinite(player.currentTime) || player.currentTime < 0.5) return;
                const now = Date.now();
                if (now - lastSavedAt < 8000) return;
                lastSavedAt = now;

                const duration = Number.isFinite(player.duration) ? player.duration : null;
                const percent = duration && duration > 0 ? Math.min(100, (player.currentTime / duration) * 100) : null;

                fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        last_position_seconds: player.currentTime,
                        duration_seconds: duration,
                        percent_watched: percent,
                    }),
                }).catch(() => {});
            };

            player.addEventListener('pause', saveProgress);
            player.addEventListener('timeupdate', saveProgress);
            player.addEventListener('ended', saveProgress);
            window.addEventListener('beforeunload', saveProgress);
        })();
    </script>
@endsection
