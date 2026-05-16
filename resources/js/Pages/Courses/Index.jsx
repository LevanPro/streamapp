import { Head, Link } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout';
import Card from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import Reveal from '@/Components/effects/Reveal';
import SpotlightCard from '@/Components/effects/SpotlightCard';

export default function Index({ courses, coursesRoot }) {
    const totalLessons = courses.reduce((n, c) => n + c.lessons_count, 0);
    const totalResources = courses.reduce((n, c) => n + c.resources_count, 0);

    return (
        <AppLayout>
            <Head title="Courses" />

            <Reveal as={Card} className="mb-4 p-5">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p className="text-xs font-bold uppercase tracking-[0.1em] text-[#0c5d80]">
                            Library
                        </p>
                        <h1 className="my-1.5 text-2xl font-extrabold">
                            Course Vault
                        </h1>
                        <p className="m-0 max-w-[72ch] text-muted">
                            Browse imported courses and continue learning where
                            you left off.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Badge>{courses.length} courses</Badge>
                        <Badge tone="info">{totalLessons} lessons</Badge>
                        <Badge tone="warm">{totalResources} resources</Badge>
                    </div>
                </div>
            </Reveal>

            {courses.length === 0 ? (
                <Card className="p-6">
                    <p className="m-0">No courses found yet. Run:</p>
                    <pre className="mt-2.5 overflow-auto rounded-soft bg-[#0f1d2f] p-3 text-sm text-[#ccf6de]">
                        php artisan courses:scan {coursesRoot}
                    </pre>
                </Card>
            ) : (
                <div className="grid grid-cols-[repeat(auto-fill,minmax(290px,1fr))] gap-3.5">
                    {courses.map((course, index) => (
                        <Reveal key={course.id} delay={Math.min(index, 8) * 60}>
                            <SpotlightCard
                                as="article"
                                className="grid h-full gap-2.5 p-4 transition-[transform,box-shadow] duration-200 hover:-translate-y-0.5 hover:shadow-[0_20px_34px_rgba(16,32,54,0.12)] bg-[radial-gradient(130%_120%_at_100%_0%,rgba(14,163,149,0.12),transparent_55%)]"
                            >
                                <h2 className="relative m-0 text-[1.06rem] font-bold leading-snug">
                                    {course.display_title}
                                </h2>
                                <div className="relative flex flex-wrap gap-2">
                                    <Badge>{course.lessons_count} lessons</Badge>
                                    <Badge tone="info">
                                        {course.resources_count} resources
                                    </Badge>
                                </div>
                                <div
                                    className="relative truncate text-[0.82rem] text-muted"
                                    title={course.relative_path}
                                >
                                    {course.relative_path}
                                </div>
                                <div className="relative">
                                    <Link
                                        href={route('courses.show', course.id)}
                                        className="inline-flex items-center justify-center gap-2 rounded-soft bg-accent px-4 py-2 text-sm font-semibold text-white shadow-accent transition hover:brightness-105"
                                    >
                                        Open Course
                                    </Link>
                                </div>
                            </SpotlightCard>
                        </Reveal>
                    ))}
                </div>
            )}
        </AppLayout>
    );
}
