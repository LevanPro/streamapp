const VARIANTS = {
    primary:
        'bg-accent text-white shadow-accent hover:brightness-105 active:translate-y-px',
    outline:
        'bg-white/70 text-accent-dark border border-accent-dark/40 hover:bg-bg-glow/70',
    ghost:
        'bg-transparent text-accent-dark hover:bg-accent-dark/10',
    danger:
        'bg-danger text-white hover:brightness-105',
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
                'text-sm font-semibold transition-[transform,filter,background-color] duration-150',
                'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/40',
                'disabled:cursor-not-allowed disabled:opacity-60',
                VARIANTS[variant] ?? VARIANTS.primary,
                className,
            ].join(' ')}
            {...props}
        />
    );
}
