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

const initLessonPlayer = () => {
    const player = document.getElementById('lessonPlayer');
    if (!player) {
        return;
    }

    const config = {
        progressEndpoint: player.dataset.progressEndpoint ?? '',
        savedPosition: Number(player.dataset.savedPosition ?? 0),
        manifestUrl: '',
    };

    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

    const timelineRoot = document.getElementById('lessonTimelinePreview');
    const timelineTrack = document.getElementById('timelineTrack');
    const timelineProgress = document.getElementById('timelineProgress');
    const timelineThumb = document.getElementById('timelineThumb');
    const timelineHover = document.getElementById('timelineHover');
    const timelineHoverImage = document.getElementById('timelineHoverImage');
    const timelineHoverTime = document.getElementById('timelineHoverTime');
    const resumeBanner = document.getElementById('resumeBanner');
    const resumeButton = document.getElementById('resumeButton');

    if (timelineRoot) {
        config.manifestUrl = timelineRoot.getAttribute('data-manifest-url') ?? '';
    }

    if (config.savedPosition > 10 && resumeBanner && resumeButton) {
        resumeBanner.style.display = 'block';
        resumeButton.addEventListener('click', () => {
            player.currentTime = config.savedPosition;
            resumeBanner.style.display = 'none';
            player.play().catch(() => {});
        });
    }

    let lastSavedAt = 0;
    let previewManifest = null;
    let timelinePointerDown = false;

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
        if (!config.manifestUrl) {
            return;
        }

        try {
            const response = await fetch(config.manifestUrl, {
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
        if (!config.progressEndpoint) {
            return;
        }
        if (!Number.isFinite(player.currentTime) || player.currentTime < 0.5) {
            return;
        }
        const now = Date.now();
        if (now - lastSavedAt < 8000) {
            return;
        }
        lastSavedAt = now;

        const duration = Number.isFinite(player.duration) ? player.duration : null;
        const percent = duration && duration > 0 ? Math.min(100, (player.currentTime / duration) * 100) : null;

        fetch(config.progressEndpoint, {
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
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLessonPlayer);
} else {
    initLessonPlayer();
}
