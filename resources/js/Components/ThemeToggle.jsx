import { useEffect, useState } from 'react';

function currentTheme() {
    if (typeof document === 'undefined') return 'dark';
    return document.documentElement.classList.contains('light')
        ? 'light'
        : 'dark';
}

function apply(theme) {
    const d = document.documentElement;
    d.classList.toggle('dark', theme !== 'light');
    d.classList.toggle('light', theme === 'light');
    d.style.colorScheme = theme === 'light' ? 'light' : 'dark';
    try {
        localStorage.setItem('theme', theme);
    } catch {
        /* ignore */
    }
}

export default function ThemeToggle({ className = '' }) {
    const [theme, setTheme] = useState(currentTheme);

    useEffect(() => {
        setTheme(currentTheme());
    }, []);

    const toggle = () => {
        const next = theme === 'light' ? 'dark' : 'light';
        apply(next);
        setTheme(next);
    };

    const isLight = theme === 'light';

    return (
        <button
            type="button"
            onClick={toggle}
            aria-label={isLight ? 'Switch to dark mode' : 'Switch to light mode'}
            title={isLight ? 'Dark mode' : 'Light mode'}
            className={[
                'group relative grid h-9 w-9 place-items-center rounded-full',
                'border border-line bg-panel/60 text-ink backdrop-blur',
                'transition hover:border-accent/50 hover:text-accent',
                'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent/40',
                className,
            ].join(' ')}
        >
            <span className="relative h-[18px] w-[18px]">
                {/* Sun */}
                <svg
                    viewBox="0 0 24 24"
                    className={[
                        'absolute inset-0 h-[18px] w-[18px] transition-all duration-300',
                        isLight
                            ? 'rotate-0 scale-100 opacity-100'
                            : '-rotate-90 scale-0 opacity-0',
                    ].join(' ')}
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                >
                    <circle cx="12" cy="12" r="4" />
                    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41" />
                </svg>
                {/* Moon */}
                <svg
                    viewBox="0 0 24 24"
                    className={[
                        'absolute inset-0 h-[18px] w-[18px] transition-all duration-300',
                        isLight
                            ? 'rotate-90 scale-0 opacity-0'
                            : 'rotate-0 scale-100 opacity-100',
                    ].join(' ')}
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                >
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
                </svg>
            </span>
        </button>
    );
}
