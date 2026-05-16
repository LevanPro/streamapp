export default function Card({ as: Tag = 'div', className = '', ...props }) {
    return (
        <Tag
            className={[
                'rounded-panel border border-line bg-panel/80 shadow-panel backdrop-blur-sm',
                className,
            ].join(' ')}
            {...props}
        />
    );
}
