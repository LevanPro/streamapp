<?php

return [
    'root' => env('COURSES_ROOT', '/srv/courses'),

    'internal_media_prefix' => env('COURSE_INTERNAL_MEDIA_PREFIX', '/_protected_media'),
    'stream_driver' => env('COURSE_STREAM_DRIVER', 'auto'),

    'preview_enabled' => (bool) env('COURSE_PREVIEW_ENABLED', true),
    'preview_directory' => env('COURSE_PREVIEW_DIRECTORY', 'course-previews'),
    'ffmpeg_binary' => env('COURSE_FFMPEG_BINARY', 'ffmpeg'),
    'ffprobe_binary' => env('COURSE_FFPROBE_BINARY', 'ffprobe'),

    'video_extensions' => array_values(array_filter(array_map(
        static fn (string $value): string => strtolower(trim($value)),
        explode(',', (string) env('COURSE_VIDEO_EXTENSIONS', 'mp4,mkv,webm,m4v,mov,avi,flv,ts,wmv'))
    ))),

    'resource_extensions' => array_values(array_filter(array_map(
        static fn (string $value): string => strtolower(trim($value)),
        explode(',', (string) env('COURSE_RESOURCE_EXTENSIONS', 'pdf,zip,rar,7z,tar,gz,doc,docx,ppt,pptx,xls,xlsx,txt,md,srt,vtt,jpg,jpeg,png,svg,webp,json,yaml,yml,xml,csv,sql,go,py,js,ts,tsx,jsx,java,kt,cs,cpp,c,h,hpp,php,sh,bat,ps1,proto'))
    ))),

    'exclude_directories' => array_values(array_filter(array_map(
        static fn (string $value): string => strtolower(trim($value)),
        explode(',', (string) env('COURSE_RESOURCE_EXCLUDE_DIRS', '.git,node_modules,vendor,dist,build,target,.idea,.vscode,__pycache__,.next,.nuxt,coverage'))
    ))),
];
