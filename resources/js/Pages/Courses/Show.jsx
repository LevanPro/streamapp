import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout';
import Card from '@/Components/ui/Card';
import Badge from '@/Components/ui/Badge';
import ResourceItem from '@/Components/ResourceItem';
import ResourcePreviewDialog from '@/Components/ResourcePreviewDialog';
import Reveal from '@/Components/effects/Reveal';
import { formatClock } from '@/lib/format';

export default function Show({ course, courseResources, sections, firstLessonId }) {
    const [previewResource, setPreviewResource] = useState(null);

    const lessonCount = sections.reduce((n, s) => n + s.lessons.length, 0);
    const resourceCount =
        courseResources.length +
        sections.reduce((n, s) => n + s.resources.length, 0);

    return (
        <AppLayout>
            <Head title={course.display_title} />

            <Reveal as={Card} className="mb-4 p-5">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <Link
                            href={route('courses.index')}
                            className="inline-flex items-center gap-1.5 text-sm font-semibold text-[#325486] hover:text-[#1d3760]"
                        >
                            ← All courses
                        </Link>
                        <h1 className="my-1.5 text-2xl font-extrabold">
                            {course.display_title}
                        </h1>
                        <div className="text-muted">{course.relative_path}</div>
                        <div className="mt-3 flex flex-wrap gap-2">
                            <Badge>{sections.length} sections</Badge>
                            <Badge tone="info">{lessonCount} lessons</Badge>
                            <Badge tone="warm">{resourceCount} resources</Badge>
                        </div>
                    </div>
                    {firstLessonId && (
                        <Link
                            href={route('lessons.show', firstLessonId)}
                            className="inline-flex items-center justify-center gap-2 rounded-soft bg-accent px-4 py-2 text-sm font-semibold text-white shadow-accent transition hover:brightness-105"
                        >
                            Start Watching
                        </Link>
                    )}
                </div>
            </Reveal>

            {courseResources.length > 0 && (
                <Card className="mb-3.5 p-4">
                    <h2 className="mb-2.5 text-base font-bold">
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

            <Card className="p-3.5">
                <h2 className="mx-2 mb-3 mt-1 text-lg font-bold">Sections</h2>
                <div className="grid gap-3">
                    {sections.map((section) => (
                        <article
                            key={section.id}
                            className="rounded-[14px] border border-line bg-white p-3 bg-[radial-gradient(120%_120%_at_100%_0%,rgba(39,69,138,0.08),transparent_55%)]"
                        >
                            <h3 className="mb-2.5 text-base font-bold">
                                {section.title}
                            </h3>

                            {section.lessons.length === 0 ? (
                                <p className="m-0 mb-2 text-muted">
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
                                            className="flex items-center justify-between gap-2.5 rounded-soft border border-line bg-[#fbfdff] px-3 py-2.5 transition hover:-translate-y-px hover:border-line-strong"
                                        >
                                            <span className="text-[0.93rem]">
                                                {lesson.display_title}
                                            </span>
                                            <span className="text-[0.85rem] text-muted">
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
                                    <div className="mb-1.5 text-[0.85rem] text-muted">
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
                        </article>
                    ))}
                </div>
            </Card>

            <ResourcePreviewDialog
                resource={previewResource}
                onClose={() => setPreviewResource(null)}
            />
        </AppLayout>
    );
}
