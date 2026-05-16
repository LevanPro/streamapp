// ReactBits — GradientText (vendored, MIT). Animated gradient fill.
export default function GradientText({
    children,
    className = '',
    colors = ['#34d399', '#10b981', '#2dd4bf', '#34d399'],
    animationSpeed = 8,
}) {
    return (
        <span
            className={`inline-block bg-clip-text text-transparent ${className}`}
            style={{
                backgroundImage: `linear-gradient(90deg, ${colors.join(', ')})`,
                backgroundSize: '300% 100%',
                WebkitBackgroundClip: 'text',
                animation: `rb-gradient ${animationSpeed}s ease infinite`,
            }}
        >
            {children}
        </span>
    );
}
