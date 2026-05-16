import { useRef } from 'react';
import Card from '@/Components/ui/Card';

/**
 * ReactBits-style "Spotlight Card" — a soft radial highlight that tracks
 * the pointer. Dependency-free; the highlight only renders on hover so it
 * costs nothing at rest and degrades to a plain card on touch devices.
 */
export default function SpotlightCard({ className = '', children, ...props }) {
    const ref = useRef(null);

    const onMove = (event) => {
        const el = ref.current;
        if (!el) {
            return;
        }
        const rect = el.getBoundingClientRect();
        el.style.setProperty('--spot-x', `${event.clientX - rect.left}px`);
        el.style.setProperty('--spot-y', `${event.clientY - rect.top}px`);
        el.style.setProperty('--spot-opacity', '1');
    };

    const onLeave = () => {
        ref.current?.style.setProperty('--spot-opacity', '0');
    };

    return (
        <Card
            ref={ref}
            onMouseMove={onMove}
            onMouseLeave={onLeave}
            className={[
                'group relative overflow-hidden',
                'before:pointer-events-none before:absolute before:inset-0',
                'before:opacity-[var(--spot-opacity,0)] before:transition-opacity before:duration-300',
                'before:bg-[radial-gradient(220px_circle_at_var(--spot-x)_var(--spot-y),rgba(14,163,149,0.18),transparent_70%)]',
                'motion-reduce:before:hidden',
                className,
            ].join(' ')}
            {...props}
        >
            {children}
        </Card>
    );
}
