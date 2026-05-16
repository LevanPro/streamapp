import { route } from 'ziggy-js';

export default function ResourceItem({ resource, onPreview }) {
    const streamUrl = route('stream.resources', resource.id);

    return (
        <div className="flex items-center justify-between gap-2.5 rounded-soft border border-line bg-[#fbfdff] px-3 py-2.5">
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
                            className="rounded-soft border border-accent-dark/40 bg-white/70 px-2.5 py-1 text-[0.82rem] font-semibold text-accent-dark hover:bg-bg-glow/70"
                        >
                            View
                        </button>
                        <a
                            href={streamUrl}
                            className="text-[0.82rem] font-semibold text-[#27507f] hover:text-[#173253]"
                        >
                            Download
                        </a>
                    </>
                )}

                {resource.kind === 'pdf' && (
                    <a
                        href={streamUrl}
                        target="_blank"
                        rel="noopener"
                        className="text-[0.82rem] font-semibold text-[#27507f] hover:text-[#173253]"
                    >
                        Open PDF
                    </a>
                )}

                {resource.kind === 'download' && (
                    <a
                        href={streamUrl}
                        className="text-[0.82rem] font-semibold text-[#27507f] hover:text-[#173253]"
                    >
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
