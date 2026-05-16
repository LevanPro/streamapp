import { route } from 'ziggy-js';

export default function ResourceItem({ resource, onPreview }) {
    const streamUrl = route('stream.resources', resource.id);
    const linkCls =
        'text-[0.82rem] font-semibold text-accent-dark transition hover:text-accent';

    return (
        <div className="flex items-center justify-between gap-2.5 rounded-soft border border-line bg-elevated/50 px-3 py-2.5 transition hover:border-line-strong">
            <span
                className="truncate text-sm text-muted"
                title={resource.display_title}
            >
                {resource.display_title}
            </span>

            <div className="flex flex-shrink-0 items-center gap-2.5">
                {resource.kind === 'text' && (
                    <>
                        <button
                            type="button"
                            onClick={() => onPreview(resource)}
                            className="rounded-soft border border-line-strong bg-panel/60 px-2.5 py-1 text-[0.82rem] font-semibold text-ink transition hover:border-accent/60 hover:text-accent"
                        >
                            View
                        </button>
                        <a href={streamUrl} className={linkCls}>
                            Download
                        </a>
                    </>
                )}

                {resource.kind === 'pdf' && (
                    <a
                        href={streamUrl}
                        target="_blank"
                        rel="noopener"
                        className={linkCls}
                    >
                        Open PDF
                    </a>
                )}

                {resource.kind === 'download' && (
                    <a href={streamUrl} className={linkCls}>
                        Download
                    </a>
                )}

                <span className="text-[0.78rem] text-muted">
                    {resource.size_mb.toFixed(2)} MB
                </span>
            </div>
        </div>
    );
}
