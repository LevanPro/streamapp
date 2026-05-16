import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { route } from 'ziggy-js';
import AppLayout from '@/Layouts/AppLayout';
import Card from '@/Components/ui/Card';
import LessonPlayer from '@/Components/LessonPlayer';
import ResourceItem from '@/Components/ResourceItem';
import ResourcePreviewDialog from '@/Components/ResourcePreviewDialog';
import { formatClock } from '@/lib/format';

export default function Show({
    course,
    lesson,
    savedPosition,
    previousLessonId,
    nextLessonId,
    activeResources,
    sidebar,
}) {
    const [previewResource, setPreviewResource] = useState(null);

    return (
        <AppLayout>
            <Head title={`${lesson.display_title} · ${course.display_title}`} />

            <div className="mb-3.5">
                <Link
                    href={route('courses.show', course.id)}
                    className="inline-flex items-center gap-1.5 text-sm font-semibold text-[#325486] hover:text-[#1d3760]"
                >
                    ← {course.display_title}
                </Link>
            </div>

            <section className="grid items-start gap-4 lg:grid-cols-[minmax(0,1.85fr)_minmax(290px,1fr)]">
                <Card className="p-4 bg-[radial-gradient(120%_120%_at_100%_0%,rgba(14,163,149,0.12),transparent_56%)]">
                    <h1 className="mb-1 text-2xl font-extrabold leading-tight">
                        {lesson.display_title}
                    </h1>
                    <div className="mb-3 text-sm text-muted">
                        {lesson.section_title}
                        {lesson.duration_seconds
                            ? ` · ${formatClock(lesson.duration_seconds)}`
                            : ''}
                    </div>

                    <LessonPlayer
                        title={lesson.display_title}
                        src={route('stream.lessons', lesson.id)}
                        mimeType={lesson.mime_type}
                        posterUrl={
                            lesson.has_poster
                                ? route('lessons.thumbnail', lesson.id)
                                : null
                        }
                        storyboardUrl={
                            lesson.has_storyboard
                                ? route('lessons.preview.storyboard', lesson.id)
                                : null
                        }
                        progressUrl={route('progress.store', lesson.id)}
                        savedPosition={savedPosition}
                    />

                    <div className="mt-3 flex flex-wrap gap-2">
                        {previousLessonId && (
                            <Link
                                href={route('lessons.show', previousLessonId)}
                                className="inline-flex items-center justify-center gap-2 rounded-soft border border-accent-dark/40 bg-white/70 px-4 py-2 text-sm font-semibold text-accent-dark hover:bg-bg-glow/70"
                            >
                                Previous Lesson
                            </Link>
                        )}
                        {nextLessonId && (
                            <Link
                                href={route('lessons.show', nextLessonId)}
                                className="inline-flex items-center justify-center gap-2 rounded-soft bg-accent px-4 py-2 text-sm font-semibold text-white shadow-accent transition hover:brightness-105"
                            >
                                Next Lesson
                            </Link>
                        )}
                    </div>

                    {activeResources.length > 0 && (
                        <section className="mt-4">
                            <h2 className="mb-2 text-base font-bold">
                                Lesson Resources
                            </h2>
                            <div className="grid gap-1.5">
                                {activeResources.map((resource) => (
                                    <ResourceItem
                                        key={resource.id}
                                        resource={resource}
                                        onPreview={setPreviewResource}
                                    />
                                ))}
                            </div>
                        </section>
                    )}
                </Card>

                <Card
                    as="aside"
                    className="sticky top-2.5 max-h-[calc(100vh-34px)] overflow-auto bg-[#fbfdff] p-3 lg:block"
                >
                    {sidebar.map((section) => (
                        <section
                            key={section.id}
                            className="mb-3 border-b border-dashed border-[#d8e2f0] pb-2.5 last:mb-0 last:border-none last:pb-0"
                        >
                            <h3 className="mb-2 text-[0.83rem] font-semibold uppercase tracking-[0.08em] text-[#526072]">
                                {section.title}
                            </h3>
                            {section.lessons.map((sectionLesson) => {
                                const active = sectionLesson.id === lesson.id;
                                return (
                                    <Link
                                        key={sectionLesson.id}
                                        href={route(
                                            'lessons.show',
                                            sectionLesson.id,
                                        )}
                                        className={[
                                            'mb-1.5 block rounded-soft border px-2.5 py-2.5 transition hover:-translate-y-px',
                                            active
                                                ? 'border-[#11867b] bg-[#def8f4] font-bold'
                                                : 'border-line bg-white hover:border-line-strong',
                                        ].join(' ')}
                                    >
                                        <div className="text-[0.92rem]">
                                            {sectionLesson.display_title}
                                        </div>
                                        <div className="text-[0.76rem] text-muted">
                                            {sectionLesson.duration_seconds
                                                ? formatClock(
                                                      sectionLesson.duration_seconds,
                                                  )
                                                : 'video'}
                                        </div>
                                    </Link>
                                );
                            })}
                        </section>
                    ))}
                </Card>
            </section>

            <ResourcePreviewDialog
                resource={previewResource}
                onClose={() => setPreviewResource(null)}
            />
        </AppLayout>
    );
}
