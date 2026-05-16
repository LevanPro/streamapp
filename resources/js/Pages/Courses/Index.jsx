import { Head, Link } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout';
import Card from '@/Components/ui/Card';
import GradientText from '@/Components/reactbits/GradientText';
import CountUp from '@/Components/reactbits/CountUp';
import SpotlightCard from '@/Components/reactbits/SpotlightCard';
import AnimatedContent from '@/Components/reactbits/AnimatedContent';

function Metric({ value, label }) {
    return (
        <div className="rounded-soft border border-line bg-elevated/40 px-4 py-3 text-center">
            <div className="text-2xl font-extrabold text-ink">
                <CountUp to={value} />
            </div>
            <div className="text-[0.72rem] uppercase tracking-[0.12em] text-muted">
                {label}
            </div>
        </div>
    );
}

export default function Index({ courses, coursesRoot }) {
    const totalLessons = courses.reduce((n, c) => n + c.lessons_count, 0);
    const totalResources = courses.reduce((n, c) => n + c.resources_count, 0);

    return (
        <AppLayout>
            <Head title="Courses" />

            <Card className="relative mb-6 overflow-hidden p-8">
                <div className="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-accent/20 blur-3xl" />
                <div className="relative flex flex-wrap items-end justify-between gap-6">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.18em] text-accent">
                            Library
                        </p>
                        <h1 className="mt-2 text-4xl font-extrabold leading-tight">
                            <GradientText>Course Vault</GradientText>
                        </h1>
                        <p className="mt-2 max-w-[60ch] text-muted">
                            Browse your imported courses and continue learning
                            where you left off.
                        </p>
                    </div>
                    <div className="grid grid-cols-3 gap-3">
                        <Metric value={courses.length} label="Courses" />
                        <Metric value={totalLessons} label="Lessons" />
                        <Metric value={totalResources} label="Resources" />
                    </div>
                </div>
            </Card>

            {courses.length === 0 ? (
                <Card className="p-8">
                    <p className="m-0 text-muted">No courses found yet. Run:</p>
                    <pre className="mt-3 overflow-auto rounded-soft border border-line bg-black/40 p-3 text-sm text-accent-dark">
                        php artisan courses:scan {coursesRoot}
                    </pre>
                </Card>
            ) : (
                <div className="grid grid-cols-[repeat(auto-fill,minmax(300px,1fr))] gap-4">
                    {courses.map((course, index) => (
                        <AnimatedContent
                            key={course.id}
                            distance={50}
                            duration={0.6}
                            delay={Math.min(index, 8) * 0.05}
                        >
                            <SpotlightCard
                                as="article"
                                className="flex h-full flex-col gap-3 p-5"
                            >
                                <h2 className="text-lg font-bold leading-snug text-ink">
                                    {course.display_title}
                                </h2>
                                <div className="flex flex-wrap gap-2 text-xs">
                                    <span className="rounded-full border border-accent/30 bg-accent/10 px-2.5 py-1 font-semibold text-accent-dark">
                                        {course.lessons_count} lessons
                                    </span>
                                    <span className="rounded-full border border-line-strong bg-elevated/60 px-2.5 py-1 font-semibold text-muted">
                                        {course.resources_count} resources
                                    </span>
                                </div>
                                <div
                                    className="truncate text-[0.8rem] text-muted"
                                    title={course.relative_path}
                                >
                                    {course.relative_path}
                                </div>
                                <div className="mt-auto pt-2">
                                    <Link
                                        href={route('courses.show', course.id)}
                                        className="inline-flex items-center gap-2 rounded-soft bg-accent px-4 py-2 text-sm font-semibold text-accent-ink shadow-accent transition hover:bg-accent-dark"
                                    >
                                        Open Course →
                                    </Link>
                                </div>
                            </SpotlightCard>
                        </AnimatedContent>
                    ))}
                </div>
            )}
        </AppLayout>
    );
}
