/**
 * ReactBits-style "Aurora" — a slow drifting colour wash. Implemented as a
 * blurred CSS gradient (no WebGL/Three.js) so it stays light and the global
 * prefers-reduced-motion rule freezes it. Sits behind page content.
 */
export default function AuroraBackground() {
    return (
        <div
            aria-hidden="true"
            className="pointer-events-none fixed inset-0 -z-10 overflow-hidden"
        >
            <div className="aurora-blob absolute -top-1/3 left-1/2 h-[80vh] w-[80vh] -translate-x-1/2 rounded-full opacity-50 blur-3xl" />
            <style>{`
                .aurora-blob {
                    background: conic-gradient(
                        from 0deg,
                        rgba(14,163,149,0.35),
                        rgba(39,69,138,0.28),
                        rgba(255,166,66,0.22),
                        rgba(14,163,149,0.35)
                    );
                    animation: aurora-spin 26s linear infinite;
                }
                @keyframes aurora-spin {
                    to { transform: translateX(-50%) rotate(360deg); }
                }
                @media (prefers-reduced-motion: reduce) {
                    .aurora-blob { animation: none; }
                }
            `}</style>
        </div>
    );
}
