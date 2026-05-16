const TONES = {
    default: 'bg-[#e7fbf8] text-[#0f766e] border-[rgba(14,163,149,0.22)]',
    info: 'bg-[#e9efff] text-[#27458a] border-[rgba(39,69,138,0.2)]',
    warm: 'bg-[#fff2dc] text-[#965d00] border-[rgba(150,93,0,0.2)]',
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
