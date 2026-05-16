// ReactBits — CountUp (vendored, MIT). Spring-animated number.
import { useEffect, useRef, useState } from 'react';
import { useInView, useMotionValue, useSpring } from 'framer-motion';

export default function CountUp({
    to,
    from = 0,
    duration = 1.6,
    className = '',
}) {
    const ref = useRef(null);
    const motionValue = useMotionValue(from);
    const spring = useSpring(motionValue, {
        damping: 22,
        stiffness: 90,
        duration,
    });
    const inView = useInView(ref, { once: true, margin: '0px' });
    const [display, setDisplay] = useState(from);

    useEffect(() => {
        if (inView) motionValue.set(to);
    }, [inView, to, motionValue]);

    useEffect(() => {
        const unsub = spring.on('change', (v) =>
            setDisplay(Math.round(v)),
        );
        return unsub;
    }, [spring]);

    return (
        <span ref={ref} className={className}>
            {display.toLocaleString()}
        </span>
    );
}
