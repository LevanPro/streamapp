// ReactBits — SpotlightCard (vendored, MIT). Pointer-tracked radial glow.
import { useRef } from 'react';

export default function SpotlightCard({
    children,
    className = '',
    spotlightColor = 'rgba(16, 185, 129, 0.20)',
    as: Tag = 'div',
    ...rest
}) {
    const ref = useRef(null);

    const handleMove = (e) => {
        const el = ref.current;
        if (!el) return;
        const rect = el.getBoundingClientRect();
        el.style.setProperty('--rb-x', `${e.clientX - rect.left}px`);
        el.style.setProperty('--rb-y', `${e.clientY - rect.top}px`);
        el.style.setProperty('--rb-spot-opacity', '1');
    };

    const handleLeave = () => {
        ref.current?.style.setProperty('--rb-spot-opacity', '0');
    };

    return (
        <Tag
            ref={ref}
            onMouseMove={handleMove}
            onMouseLeave={handleLeave}
            className={[
                'relative overflow-hidden rounded-panel border border-line bg-panel shadow-panel',
                'transition-colors duration-300 hover:border-line-strong',
                'before:pointer-events-none before:absolute before:inset-0',
                'before:opacity-[var(--rb-spot-opacity,0)] before:transition-opacity before:duration-500',
                'before:bg-[radial-gradient(280px_circle_at_var(--rb-x)_var(--rb-y),var(--rb-spot),transparent_70%)]',
                'motion-reduce:before:hidden',
                className,
            ].join(' ')}
            style={{ '--rb-spot': spotlightColor }}
            {...rest}
        >
            {children}
        </Tag>
    );
}
