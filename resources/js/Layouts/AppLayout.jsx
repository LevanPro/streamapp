import { Link, usePage } from '@inertiajs/react';
import { route } from 'ziggy-js';
import ThemeToggle from '@/Components/ThemeToggle';
import GradientText from '@/Components/reactbits/GradientText';

export default function AppLayout({ children }) {
    const user = usePage().props.auth?.user;

    return (
        <div className="mx-auto w-[min(1340px,94vw)] px-0 pb-16 pt-5">
            {user && (
                <header className="sticky top-3 z-30 mb-6 flex flex-wrap items-center justify-between gap-3 rounded-panel border border-line bg-panel/70 px-5 py-3 shadow-soft backdrop-blur-xl">
                    <Link
                        href={route('courses.index')}
                        className="text-lg font-extrabold tracking-tight"
                    >
                        <GradientText>Course</GradientText>
                        <span className="text-ink"> Library</span>
                    </Link>

                    <div className="flex items-center gap-3">
                        <span className="hidden text-sm text-muted sm:inline">
                            {user.email}
                        </span>
                        <ThemeToggle />
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            className="inline-flex items-center justify-center rounded-full border border-line bg-panel/60 px-4 py-2 text-sm font-semibold text-ink backdrop-blur transition hover:border-accent/60 hover:text-accent"
                        >
                            Log out
                        </Link>
                    </div>
                </header>
            )}

            <main className="w-full">{children}</main>
        </div>
    );
}
