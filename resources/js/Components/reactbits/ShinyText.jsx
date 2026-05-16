// ReactBits — ShinyText (vendored, MIT). A moving sheen over text.
export default function ShinyText({
    text,
    disabled = false,
    speed = 5,
    className = '',
}) {
    return (
        <span
            className={`inline-block bg-clip-text text-transparent ${className}`}
            style={{
                backgroundImage:
                    'linear-gradient(120deg, var(--c-ink) 40%, var(--c-accent-strong) 50%, var(--c-ink) 60%)',
                backgroundSize: '200% 100%',
                WebkitBackgroundClip: 'text',
                animation: disabled
                    ? 'none'
                    : `rb-shine ${speed}s linear infinite`,
            }}
        >
            {text}
        </span>
    );
}
