import { Link, usePage } from '@inertiajs/react';
import { route } from 'ziggy-js';

export default function AppLayout({ children }) {
    const user = usePage().props.auth?.user;

    return (
        <div className="mx-auto w-[min(1320px,95vw)] px-0 pt-6 pb-10">
            {user && (
                <header className="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-panel border border-line-strong bg-white/90 px-5 py-3.5 shadow-soft backdrop-blur">
                    <Link
                        href={route('courses.index')}
                        className="text-lg font-extrabold tracking-tight"
                    >
                        Private <span className="text-accent-dark">Course</span> Library
                    </Link>

                    <div className="flex items-center gap-3">
                        <span className="text-sm text-muted">{user.email}</span>
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            className="inline-flex items-center justify-center gap-2 rounded-soft border border-accent-dark/40 bg-white/70 px-4 py-2 text-sm font-semibold text-accent-dark transition hover:bg-bg-glow/70"
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
