const TONES = {
    default:
        'bg-[color-mix(in_srgb,var(--c-accent)_16%,transparent)] text-accent-dark border-[color-mix(in_srgb,var(--c-accent)_35%,transparent)]',
    info: 'bg-[rgba(56,130,246,0.16)] text-[#7cb0ff] border-[rgba(56,130,246,0.32)]',
    warm: 'bg-[rgba(245,158,11,0.16)] text-[#fbbf24] border-[rgba(245,158,11,0.32)]',
};

export default function Badge({ tone = 'default', className = '', ...props }) {
    return (
        <span
            className={[
                'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1',
                'text-[0.78rem] font-semibold',
                TONES[tone] ?? TONES.default,
                className,
            ].join(' ')}
            {...props}
        />
    );
}
