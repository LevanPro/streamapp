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
        .timeline-preview {
            margin-top: 10px;
            position: relative;
            padding: 16px 0 4px;
            touch-action: none;
            user-select: none;
        }
        .timeline-track {
            position: relative;
            height: 10px;
            border-radius: 999px;
            border: 1px solid #a6bac9;
            background: #d5e2ea;
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
            width: 15px;
            height: 15px;
            border-radius: 50%;
            border: 2px solid #ffffff;
            background: #0f766e;
            transform: translate(-50%, -50%);
            box-shadow: 0 1px 6px rgba(7, 14, 20, 0.25);
            pointer-events: none;
        }
        .timeline-hover {
            position: absolute;
            bottom: 24px;
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

            const timelineRoot = document.getElementById('lessonTimelinePreview');
            const timelineTrack = document.getElementById('timelineTrack');
            const timelineProgress = document.getElementById('timelineProgress');
            const timelineThumb = document.getElementById('timelineThumb');
            const timelineHover = document.getElementById('timelineHover');
            const timelineHoverImage = document.getElementById('timelineHoverImage');
            const timelineHoverTime = document.getElementById('timelineHoverTime');
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
            let previewManifest = null;
            let timelinePointerDown = false;

            const clamp = (value, min, max) => Math.min(max, Math.max(min, value));

            const formatClock = (seconds) => {
                const total = Math.max(0, Math.floor(seconds || 0));
                const h = Math.floor(total / 3600);
                const m = Math.floor((total % 3600) / 60);
                const s = total % 60;

                if (h > 0) {
                    return `${h}:${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
                }

                return `${m}:${String(s).padStart(2, '0')}`;
            };

            const resolveDuration = () => {
                if (Number.isFinite(player.duration) && player.duration > 0) {
                    return player.duration;
                }

                if (previewManifest && Number.isFinite(previewManifest.duration_seconds) && previewManifest.duration_seconds > 0) {
                    return previewManifest.duration_seconds;
                }

                return 0;
            };

            const setTimelineProgress = (seconds) => {
                if (!timelineTrack || !timelineProgress || !timelineThumb) {
                    return;
                }

                const duration = resolveDuration();
                const ratio = duration > 0 ? clamp(seconds / duration, 0, 1) : 0;
                const percent = ratio * 100;

                timelineProgress.style.width = `${percent}%`;
                timelineThumb.style.left = `${percent}%`;
                timelineTrack.setAttribute('aria-valuemax', String(Math.max(0, Math.floor(duration))));
                timelineTrack.setAttribute('aria-valuenow', String(Math.max(0, Math.floor(seconds))));
            };

            const timeFromClientX = (clientX) => {
                if (!timelineTrack) {
                    return 0;
                }

                const rect = timelineTrack.getBoundingClientRect();
                const ratio = rect.width > 0 ? clamp((clientX - rect.left) / rect.width, 0, 1) : 0;
                const duration = resolveDuration();

                return duration > 0 ? ratio * duration : 0;
            };

            const syncPreviewBubble = (seconds, clientX) => {
                if (!previewManifest || !timelineTrack || !timelineHover || !timelineHoverImage || !timelineHoverTime) {
                    return;
                }

                const frameCount = Number(previewManifest.frame_count);
                const columns = Number(previewManifest.columns);
                const frameWidth = Number(previewManifest.frame_width);
                const frameHeight = Number(previewManifest.frame_height);
                const interval = Number(previewManifest.interval_seconds);
                const spriteUrl = previewManifest.sprite_url;

                if (!Number.isFinite(frameCount) || frameCount < 1 || !Number.isFinite(columns) || columns < 1) {
                    return;
                }
                if (!Number.isFinite(frameWidth) || frameWidth < 1 || !Number.isFinite(frameHeight) || frameHeight < 1) {
                    return;
                }
                if (!Number.isFinite(interval) || interval <= 0 || typeof spriteUrl !== 'string' || spriteUrl === '') {
                    return;
                }

                const frameIndex = clamp(Math.floor(seconds / interval), 0, frameCount - 1);
                const column = frameIndex % columns;
                const row = Math.floor(frameIndex / columns);
                const x = column * frameWidth;
                const y = row * frameHeight;

                timelineHoverImage.style.width = `${frameWidth}px`;
                timelineHoverImage.style.height = `${frameHeight}px`;
                timelineHoverImage.style.backgroundImage = `url("${spriteUrl}")`;
                timelineHoverImage.style.backgroundPosition = `-${x}px -${y}px`;

                timelineHoverTime.textContent = formatClock(seconds);
                timelineHover.style.display = 'block';

                const rect = timelineTrack.getBoundingClientRect();
                const left = clamp(clientX - rect.left, 0, rect.width);
                timelineHover.style.left = `${left}px`;
            };

            const hidePreviewBubble = () => {
                if (timelineHover) {
                    timelineHover.style.display = 'none';
                }
            };

            const attachTimelineInteractions = () => {
                if (!timelineTrack) {
                    return;
                }

                timelineTrack.addEventListener('pointerdown', (event) => {
                    timelinePointerDown = true;
                    timelineTrack.setPointerCapture(event.pointerId);
                    const seconds = timeFromClientX(event.clientX);
                    player.currentTime = seconds;
                    setTimelineProgress(seconds);
                    syncPreviewBubble(seconds, event.clientX);
                });

                timelineTrack.addEventListener('pointermove', (event) => {
                    const seconds = timeFromClientX(event.clientX);
                    if (timelinePointerDown) {
                        player.currentTime = seconds;
                        setTimelineProgress(seconds);
                    }
                    syncPreviewBubble(seconds, event.clientX);
                });

                timelineTrack.addEventListener('pointerup', (event) => {
                    timelinePointerDown = false;
                    if (timelineTrack.hasPointerCapture(event.pointerId)) {
                        timelineTrack.releasePointerCapture(event.pointerId);
                    }
                    const seconds = timeFromClientX(event.clientX);
                    player.currentTime = seconds;
                    setTimelineProgress(seconds);
                });

                timelineTrack.addEventListener('pointerleave', () => {
                    if (!timelinePointerDown) {
                        hidePreviewBubble();
                    }
                });

                timelineTrack.addEventListener('keydown', (event) => {
                    const duration = resolveDuration();
                    if (duration <= 0) {
                        return;
                    }

                    const key = event.key;
                    const step = key === 'ArrowLeft' || key === 'ArrowDown'
                        ? -5
                        : key === 'ArrowRight' || key === 'ArrowUp'
                            ? 5
                            : key === 'PageDown'
                                ? -15
                                : key === 'PageUp'
                                    ? 15
                                    : key === 'Home'
                                        ? -duration
                                        : key === 'End'
                                            ? duration
                                            : 0;

                    if (step === 0) {
                        return;
                    }

                    event.preventDefault();
                    const target = clamp(player.currentTime + step, 0, duration);
                    player.currentTime = target;
                    setTimelineProgress(target);
                });
            };

            const initializeTimelinePreview = async () => {
                if (!timelineRoot || !timelineTrack || !timelineProgress || !timelineThumb) {
                    return;
                }

                const manifestUrl = timelineRoot.getAttribute('data-manifest-url');
                if (!manifestUrl) {
                    return;
                }

                try {
                    const response = await fetch(manifestUrl, {
                        headers: { Accept: 'application/json' },
                        credentials: 'same-origin',
                    });
                    if (!response.ok) {
                        return;
                    }

                    const payload = await response.json();
                    if (!payload || typeof payload !== 'object') {
                        return;
                    }

                    previewManifest = payload;
                    timelineRoot.hidden = false;
                    attachTimelineInteractions();
                    setTimelineProgress(player.currentTime || 0);
                } catch (_) {
                    // Ignore preview failures and keep native controls.
                }
            };

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
            player.addEventListener('timeupdate', () => setTimelineProgress(player.currentTime || 0));
            player.addEventListener('loadedmetadata', () => setTimelineProgress(player.currentTime || 0));
            player.addEventListener('ended', saveProgress);
            window.addEventListener('beforeunload', saveProgress);
            void initializeTimelinePreview();
        })();
    </script>
@endsection
