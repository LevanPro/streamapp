const VARIANTS = {
    primary:
        'bg-accent text-accent-ink shadow-accent hover:bg-accent-dark active:translate-y-px',
    outline:
        'border border-line-strong bg-panel/60 text-ink backdrop-blur hover:border-accent/60 hover:text-accent',
    ghost:
        'bg-transparent text-muted hover:bg-elevated hover:text-ink',
    danger:
        'bg-danger text-white hover:brightness-110',
};

export default function Button({
    variant = 'primary',
    className = '',
    type = 'button',
    ...props
}) {
    return (
        <button
            type={type}
            className={[
                'inline-flex items-center justify-center gap-2 rounded-soft px-4 py-2',
                'text-sm font-semibold transition-[transform,filter,background-color,color] duration-150',
                'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/40',
                'disabled:cursor-not-allowed disabled:opacity-60',
                VARIANTS[variant] ?? VARIANTS.primary,
                className,
            ].join(' ')}
            {...props}
        />
    );
}
