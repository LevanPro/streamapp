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

    const navBtn =
        'inline-flex items-center justify-center gap-2 rounded-soft border border-line-strong bg-panel/60 px-4 py-2 text-sm font-semibold text-ink backdrop-blur transition hover:border-accent/60 hover:text-accent';

    return (
        <AppLayout>
            <Head title={`${lesson.display_title} · ${course.display_title}`} />

            <div className="mb-3.5">
                <Link
                    href={route('courses.show', course.id)}
                    className="text-sm font-semibold text-muted transition hover:text-accent"
                >
                    ← {course.display_title}
                </Link>
            </div>

            <section className="grid items-start gap-5 lg:grid-cols-[minmax(0,1.9fr)_minmax(300px,1fr)]">
                <div>
                    <h1 className="mb-1 text-2xl font-extrabold leading-tight text-ink">
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
                                className={navBtn}
                            >
                                ← Previous
                            </Link>
                        )}
                        {nextLessonId && (
                            <Link
                                href={route('lessons.show', nextLessonId)}
                                className="inline-flex items-center justify-center gap-2 rounded-soft bg-accent px-4 py-2 text-sm font-semibold text-accent-ink shadow-accent transition hover:bg-accent-dark"
                            >
                                Next Lesson →
                            </Link>
                        )}
                    </div>

                    {activeResources.length > 0 && (
                        <Card className="mt-4 p-5">
                            <h2 className="mb-3 text-base font-bold text-ink">
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
                        </Card>
                    )}
                </div>

                <Card
                    as="aside"
                    className="sticky top-24 max-h-[calc(100vh-7rem)] overflow-auto p-3"
                >
                    {sidebar.map((section) => (
                        <section
                            key={section.id}
                            className="mb-3 border-b border-line pb-3 last:mb-0 last:border-none last:pb-0"
                        >
                            <h3 className="mb-2 px-1 text-[0.78rem] font-semibold uppercase tracking-[0.12em] text-muted">
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
                                            'mb-1.5 block rounded-soft border px-3 py-2.5 transition',
                                            active
                                                ? 'border-accent/60 bg-accent/15 text-ink'
                                                : 'border-transparent text-muted hover:border-line hover:bg-elevated/60 hover:text-ink',
                                        ].join(' ')}
                                    >
                                        <div
                                            className={[
                                                'text-[0.9rem]',
                                                active
                                                    ? 'font-bold'
                                                    : 'font-medium',
                                            ].join(' ')}
                                        >
                                            {sectionLesson.display_title}
                                        </div>
                                        <div className="text-[0.74rem] tabular-nums text-muted">
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
