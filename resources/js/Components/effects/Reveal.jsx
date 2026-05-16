import { useEffect, useState } from 'react';

/**
 * ReactBits-style "Animated Content" — fade + slide-up on mount.
 * Pure CSS transition (no animation lib); the global
 * prefers-reduced-motion rule in app.css collapses it to an instant show.
 */
export default function Reveal({
    children,
    delay = 0,
    as: Tag = 'div',
    className = '',
}) {
    const [shown, setShown] = useState(false);

    useEffect(() => {
        const id = window.setTimeout(() => setShown(true), delay);
        return () => window.clearTimeout(id);
    }, [delay]);

    return (
        <Tag
            className={[
                'transition-all duration-500 ease-out will-change-[opacity,transform] motion-reduce:transition-none',
                shown
                    ? 'opacity-100 translate-y-0'
                    : 'opacity-0 translate-y-2',
                className,
            ].join(' ')}
        >
            {children}
        </Tag>
    );
}
