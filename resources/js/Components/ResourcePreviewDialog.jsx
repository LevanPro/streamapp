import { useEffect, useState } from 'react';
import { route } from 'ziggy-js';

export default function ResourcePreviewDialog({ resource, onClose }) {
    const [state, setState] = useState({ status: 'loading' });

    useEffect(() => {
        if (!resource) {
            return;
        }

        let active = true;
        setState({ status: 'loading' });

        fetch(route('resources.preview', resource.id), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        })
            .then(async (response) => {
                const payload = await response.json().catch(() => ({}));
                if (!active) {
                    return;
                }
                if (!response.ok) {
                    setState({
                        status: 'error',
                        message:
                            payload.error || 'Unable to preview this file.',
                    });
                    return;
                }
                setState({
                    status: 'ready',
                    content: payload.content || '',
                    meta: payload.error
                        ? payload.error
                        : payload.truncated
                          ? 'Showing first 512 KB of file.'
                          : 'Full file preview.',
                });
            })
            .catch(() => {
                active && setState({ status: 'error', message: 'Preview request failed.' });
            });

        return () => {
            active = false;
        };
    }, [resource]);

    useEffect(() => {
        const onKey = (event) => event.key === 'Escape' && onClose();
        document.addEventListener('keydown', onKey);
        return () => document.removeEventListener('keydown', onKey);
    }, [onClose]);

    if (!resource) {
        return null;
    }

    return (
        <div
            className="fixed inset-0 z-[1200] bg-[rgba(3,11,18,0.66)] p-[18px]"
            onClick={(event) => event.target === event.currentTarget && onClose()}
            role="dialog"
            aria-modal="true"
            aria-label={resource.display_title}
        >
            <div className="mx-auto flex max-h-[95vh] w-[min(960px,98vw)] flex-col rounded-panel border border-line bg-panel shadow-panel">
                <div className="flex items-center justify-between gap-2.5 border-b border-line px-3.5 py-3">
                    <strong className="truncate">{resource.display_title}</strong>
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-soft border border-accent-dark/40 bg-white/70 px-2.5 py-1.5 text-sm font-semibold text-accent-dark hover:bg-bg-glow/70"
                    >
                        Close
                    </button>
                </div>
                <div className="min-h-[240px] overflow-auto px-3.5 py-3">
                    <pre className="m-0 whitespace-pre-wrap break-words text-[0.9rem] leading-relaxed">
                        {state.status === 'loading'
                            ? 'Loading…'
                            : state.status === 'error'
                              ? state.message
                              : state.content}
                    </pre>
                </div>
                {state.status === 'ready' && (
                    <div className="px-3.5 pb-3 pt-2 text-[0.8rem] text-muted">
                        {state.meta}
                    </div>
                )}
            </div>
        </div>
    );
}
