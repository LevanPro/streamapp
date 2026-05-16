export default function Card({ as: Tag = 'div', className = '', ...props }) {
    return (
        <Tag
            className={[
                'rounded-panel border border-line bg-panel shadow-panel',
                className,
            ].join(' ')}
            {...props}
        />
    );
}
