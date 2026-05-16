import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout';
import Card from '@/Components/ui/Card';
import ResourceItem from '@/Components/ResourceItem';
import ResourcePreviewDialog from '@/Components/ResourcePreviewDialog';
import GradientText from '@/Components/reactbits/GradientText';
import AnimatedContent from '@/Components/reactbits/AnimatedContent';
import { formatClock } from '@/lib/format';

export default function Show({ course, courseResources, sections, firstLessonId }) {
    const [previewResource, setPreviewResource] = useState(null);

    const lessonCount = sections.reduce((n, s) => n + s.lessons.length, 0);
    const resourceCount =
        courseResources.length +
        sections.reduce((n, s) => n + s.resources.length, 0);

    const chip =
        'rounded-full border border-line-strong bg-elevated/60 px-2.5 py-1 text-xs font-semibold text-muted';

    return (
        <AppLayout>
            <Head title={course.display_title} />

            <Card className="relative mb-5 overflow-hidden p-7">
                <div className="pointer-events-none absolute -right-20 -top-24 h-64 w-64 rounded-full bg-accent/15 blur-3xl" />
                <div className="relative flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <Link
                            href={route('courses.index')}
                            className="text-sm font-semibold text-muted transition hover:text-accent"
                        >
                            ← All courses
                        </Link>
                        <h1 className="mt-2 text-3xl font-extrabold">
                            <GradientText>{course.display_title}</GradientText>
                        </h1>
                        <div className="mt-1 text-sm text-muted">
                            {course.relative_path}
                        </div>
                        <div className="mt-4 flex flex-wrap gap-2">
                            <span className={chip}>
                                {sections.length} sections
                            </span>
                            <span className={chip}>{lessonCount} lessons</span>
                            <span className={chip}>
                                {resourceCount} resources
                            </span>
                        </div>
                    </div>
                    {firstLessonId && (
                        <Link
                            href={route('lessons.show', firstLessonId)}
                            className="inline-flex items-center gap-2 rounded-soft bg-accent px-5 py-2.5 text-sm font-semibold text-accent-ink shadow-accent transition hover:bg-accent-dark"
                        >
                            ▶ Start Watching
                        </Link>
                    )}
                </div>
            </Card>

            {courseResources.length > 0 && (
                <Card className="mb-4 p-5">
                    <h2 className="mb-3 text-base font-bold text-ink">
                        Course-level Resources
                    </h2>
                    <div className="grid gap-2">
                        {courseResources.map((resource) => (
                            <ResourceItem
                                key={resource.id}
                                resource={resource}
                                onPreview={setPreviewResource}
                            />
                        ))}
                    </div>
                </Card>
            )}

            <div className="grid gap-3">
                {sections.map((section, i) => (
                    <AnimatedContent
                        key={section.id}
                        distance={40}
                        duration={0.5}
                        delay={Math.min(i, 6) * 0.04}
                    >
                        <Card className="p-5">
                            <h3 className="mb-3 text-base font-bold text-ink">
                                {section.title}
                            </h3>

                            {section.lessons.length === 0 ? (
                                <p className="m-0 text-sm text-muted">
                                    No videos in this section.
                                </p>
                            ) : (
                                <div className="grid gap-2">
                                    {section.lessons.map((lesson) => (
                                        <Link
                                            key={lesson.id}
                                            href={route(
                                                'lessons.show',
                                                lesson.id,
                                            )}
                                            className="group flex items-center justify-between gap-2.5 rounded-soft border border-line bg-elevated/40 px-3.5 py-3 transition hover:border-accent/50 hover:bg-elevated/80"
                                        >
                                            <span className="flex items-center gap-2.5 text-[0.93rem] text-ink">
                                                <span className="text-accent opacity-60 transition group-hover:opacity-100">
                                                    ▶
                                                </span>
                                                {lesson.display_title}
                                            </span>
                                            <span className="text-[0.82rem] tabular-nums text-muted">
                                                {lesson.duration_seconds
                                                    ? formatClock(
                                                          lesson.duration_seconds,
                                                      )
                                                    : 'video'}
                                            </span>
                                        </Link>
                                    ))}
                                </div>
                            )}

                            {section.resources.length > 0 && (
                                <div className="mt-3">
                                    <div className="mb-1.5 text-[0.8rem] uppercase tracking-wide text-muted">
                                        Section resources
                                    </div>
                                    <div className="grid gap-1.5">
                                        {section.resources.map((resource) => (
                                            <ResourceItem
                                                key={resource.id}
                                                resource={resource}
                                                onPreview={setPreviewResource}
                                            />
                                        ))}
                                    </div>
                                </div>
                            )}
                        </Card>
                    </AnimatedContent>
                ))}
            </div>

            <ResourcePreviewDialog
                resource={previewResource}
                onClose={() => setPreviewResource(null)}
            />
        </AppLayout>
    );
}
