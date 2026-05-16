import { useCallback, useEffect, useRef, useState } from 'react';
import {
    MediaPlayer,
    MediaProvider,
    Poster,
} from '@vidstack/react';
import {
    defaultLayoutIcons,
    DefaultVideoLayout,
} from '@vidstack/react/player/layouts/default';
import '@vidstack/react/player/styles/default/theme.css';
import '@vidstack/react/player/styles/default/layouts/video.css';

const SAVE_INTERVAL_MS = 8000;

function csrfToken() {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

export default function LessonPlayer({
    title,
    src,
    mimeType,
    posterUrl,
    storyboardUrl,
    progressUrl,
    savedPosition,
}) {
    const player = useRef(null);
    const lastSavedAt = useRef(0);
    const [showResume, setShowResume] = useState(savedPosition > 10);

    const saveProgress = useCallback(
        (force = false) => {
            const instance = player.current;
            if (!instance) {
                return;
            }

            const current = instance.currentTime;
            if (!Number.isFinite(current) || current < 0.5) {
                return;
            }

            const now = Date.now();
            if (!force && now - lastSavedAt.current < SAVE_INTERVAL_MS) {
                return;
            }
            lastSavedAt.current = now;

            const duration = Number.isFinite(instance.duration)
                ? instance.duration
                : null;
            const percent =
                duration && duration > 0
                    ? Math.min(100, (current / duration) * 100)
                    : null;

            fetch(progressUrl, {
                method: 'POST',
                keepalive: true,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken(),
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    last_position_seconds: current,
                    duration_seconds: duration,
                    percent_watched: percent,
                }),
            }).catch(() => {});
        },
        [progressUrl],
    );

    useEffect(() => {
        const flush = () => saveProgress(true);
        const onVisibility = () =>
            document.visibilityState === 'hidden' && flush();

        window.addEventListener('beforeunload', flush);
        document.addEventListener('visibilitychange', onVisibility);
        return () => {
            window.removeEventListener('beforeunload', flush);
            document.removeEventListener('visibilitychange', onVisibility);
            saveProgress(true);
        };
    }, [saveProgress]);

    const resume = () => {
        const instance = player.current;
        if (instance) {
            instance.currentTime = savedPosition;
            instance.play().catch(() => {});
        }
        setShowResume(false);
    };

    return (
        <div className="grid gap-2.5">
            <div
                className="overflow-hidden rounded-panel border border-line-strong shadow-panel"
                style={{ '--media-brand': 'var(--c-accent)' }}
            >
                <MediaPlayer
                    ref={player}
                    title={title}
                    src={{ src, type: mimeType }}
                    playsInline
                    onTimeUpdate={() => saveProgress(false)}
                    onPause={() => saveProgress(true)}
                    onEnded={() => saveProgress(true)}
                    onPlay={() => setShowResume(false)}
                >
                    <MediaProvider>
                        {posterUrl && (
                            <Poster
                                className="vds-poster"
                                src={posterUrl}
                                alt={title}
                            />
                        )}
                    </MediaProvider>
                    <DefaultVideoLayout
                        icons={defaultLayoutIcons}
                        thumbnails={storyboardUrl || undefined}
                    />
                </MediaPlayer>
            </div>

            {showResume && (
                <div className="flex flex-wrap items-center justify-between gap-2.5 rounded-panel border border-accent/40 bg-accent/10 p-3 backdrop-blur">
                    <span className="text-sm text-ink">
                        Resume from your last position?
                    </span>
                    <button
                        type="button"
                        onClick={resume}
                        className="inline-flex items-center justify-center gap-2 rounded-soft bg-accent px-4 py-2 text-sm font-semibold text-accent-ink shadow-accent transition hover:bg-accent-dark"
                    >
                        Resume
                    </button>
                </div>
            )}
        </div>
    );
}
