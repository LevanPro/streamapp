// ReactBits — StarBorder (vendored, MIT). Animated travelling glow border.
export default function StarBorder({
    as: Component = 'button',
    className = '',
    color = 'var(--c-accent-strong)',
    speed = '6s',
    children,
    ...rest
}) {
    return (
        <Component
            className={`relative inline-block overflow-hidden rounded-[14px] py-[1px] ${className}`}
            {...rest}
        >
            <span
                className="absolute -bottom-3 right-[-25%] z-0 h-1/2 w-[60%] rounded-full opacity-60"
                style={{
                    background: `radial-gradient(circle, ${color}, transparent 12%)`,
                    animation: `rb-star-bottom ${speed} linear infinite alternate`,
                }}
            />
            <span
                className="absolute -top-3 left-[-25%] z-0 h-1/2 w-[60%] rounded-full opacity-60"
                style={{
                    background: `radial-gradient(circle, ${color}, transparent 12%)`,
                    animation: `rb-star-top ${speed} linear infinite alternate`,
                }}
            />
            <span className="relative z-10 block rounded-[13px] border border-line bg-panel/80 px-5 py-2.5 text-center backdrop-blur">
                {children}
            </span>
        </Component>
    );
}
