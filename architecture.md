# Architecture

Private Laravel application for browsing and streaming a locally-stored video course library. Laravel owns auth, metadata, and access control; Nginx serves the file bytes directly via an internal `X-Accel-Redirect` location so video bytes never pass through PHP.

## 1. High-level flow

```
Browser ──► Nginx ──► PHP-FPM (Laravel) ──► MySQL
   ▲          │            │
   │          │            └─► resolves lesson/resource → returns 200 + X-Accel-Redirect header
   │          │
   └──────────┴─── Nginx intercepts header and streams bytes from /srv/courses (read-only)
```

1. User logs in → session created.
2. User opens a lesson page → HTML5 `<video>` requests `/stream/lessons/{id}`.
3. `StreamController` authorises the request, resolves the lesson's path safely, and replies with `X-Accel-Redirect: /_protected_media/<encoded-path>`.
4. Nginx sees the header, serves the file from its `internal` location with full byte-range support — no PHP in the bytes path.
5. The player periodically POSTs to `/progress/{lesson}` to persist playback position.

A `laravel` driver is also available for `php artisan serve` (streams bytes through PHP via `response()->file()`); the `accel` driver is used in Docker/production.

## 2. Tech stack

- **PHP** 8.4 / **Laravel** 13.7
- **MySQL** 8.4 (metadata + sessions + cache + queue)
- **Nginx** 1.27-alpine (reverse proxy + protected media)
- **Frontend**: Blade + Tailwind CSS 4 via Vite 8 (no SPA framework)
- **Media tooling**: optional `ffmpeg` / `ffprobe` for duration, posters, and timeline-preview sprites
- **Auth**: stock Laravel session auth (no Breeze/Jetstream/Sanctum) — custom `AuthenticatedSessionController`

Key composer packages: `laravel/framework`, `laravel/tinker`, `laravel/pail`, `phpunit/phpunit`, `mockery/mockery`, `fakerphp/faker`.

## 3. Repository layout

```
app/
├── Console/Commands/CoursesScanCommand.php   # php artisan courses:scan
├── Http/Controllers/
│   ├── Auth/AuthenticatedSessionController.php
│   ├── CourseController.php                  # index + show
│   ├── LessonController.php                  # player page + nav
│   ├── LessonProgressController.php          # POST /progress/{lesson}
│   └── StreamController.php                  # media delivery, previews, text preview
├── Models/                                   # Course, CourseSection, Lesson, CourseResource, LessonProgress, User
└── Services/Courses/
    ├── CourseScanner.php                     # walks filesystem, upserts metadata
    ├── MediaProbeService.php                 # ffprobe/ffmpeg integration
    └── ScanSummary.php                       # scan result DTO
config/                                       # standard Laravel config
courses/                                      # mounted read-only to /srv/courses in Docker
database/migrations/                          # users/cache/jobs + create_course_library_tables
docker/
├── nginx/default.conf                        # /_protected_media internal location
└── php/                                      # Dockerfile + entrypoint (installs ffmpeg)
docker-compose.yml                            # app (php-fpm) + nginx + db
resources/views/                              # Blade templates (auth, courses, lessons, partials)
routes/web.php                                # all routes (no api.php in active use)
storage/app/private/course-previews/          # generated posters, sprites, manifests
```

## 4. Data model

Single domain migration: `database/migrations/2026_05_07_000100_create_course_library_tables.php`.

| Table | Purpose | Key columns / notes |
|---|---|---|
| `courses` | Top-level course folder | `display_title`, hashed relative path, `is_visible`, `is_missing`, `last_seen_at` |
| `course_sections` | Chapter/section folder under a course | `belongs_to courses`; root-level content uses sentinel path `__root__` (`CourseSection::ROOT_RELATIVE_PATH`) |
| `lessons` | A single video file | `mime`, `extension`, `duration_seconds`, `thumbnail_path`, `preview_manifest_path`; `belongs_to course + section` |
| `resources` (model `CourseResource`) | Non-video file (pdf, zip, txt, code…) | optionally `belongs_to lesson`; otherwise scoped to course/section |
| `lesson_progress` | Per-user playback state | `last_position_seconds`, `duration_seconds`, `percent_watched`, `last_watched_at`; unique per (user, lesson) |
| `users` | Auth subjects | Standard Laravel user |

Relationships:

- `Course` `hasMany` sections, lessons, resources
- `CourseSection` `belongsTo` course; `hasMany` lessons + resources
- `Lesson` `belongsTo` course + section; `hasMany` resources + progress
- `CourseResource` `belongsTo` course, section, lesson (nullable)
- `LessonProgress` `belongsTo` user + lesson
- A `visible()` scope on `Course`, `Lesson`, `CourseResource` hides missing/hidden records from the UI

Soft-delete by flag: when a file disappears from disk, the scanner sets `is_missing=true` rather than deleting the row, preserving history and manual display-title edits.

## 5. Routes

All defined in `routes/web.php`. Everything except login is behind `auth` middleware.

| Method | URI | Handler |
|---|---|---|
| GET / POST | `/login` | `AuthenticatedSessionController::create` / `store` |
| POST | `/logout` | `AuthenticatedSessionController::destroy` |
| GET | `/courses` | `CourseController::index` |
| GET | `/courses/{course}` | `CourseController::show` |
| GET | `/lessons/{lesson}` | `LessonController::show` |
| POST | `/progress/{lesson}` | `LessonProgressController::store` |
| GET | `/stream/lessons/{lesson}` | `StreamController::lesson` |
| GET | `/stream/resources/{resource}` | `StreamController::resource` |
| GET | `/resources/{resource}/preview` | `StreamController::previewTextResource` (`.txt` / `.go`, ≤512 KB JSON) |
| GET | `/lessons/{lesson}/thumbnail` | `StreamController::lessonThumbnail` |
| GET | `/lessons/{lesson}/preview-manifest` | `StreamController::lessonPreviewManifest` |
| GET | `/lessons/{lesson}/preview-sprite` | `StreamController::lessonPreviewSprite` |

## 6. Streaming subsystem

`app/Http/Controllers/StreamController.php` is the heart of media delivery.

- **Driver selection** via `COURSE_STREAM_DRIVER` (`accel` | `laravel` | `auto`). `auto` picks `accel` outside local env.
- **Path safety**: `resolveSafeAbsolutePath()` normalises, blocks `..` traversal, runs `realpath()`, and verifies the resolved path sits inside the configured `COURSES_ROOT`.
- **Accel mode**: returns an empty `200` with headers:
  - `X-Accel-Redirect: /_protected_media/<rawurlencoded relative path>`
  - `Content-Type` based on lesson MIME / resource extension
  - `Content-Disposition` (`inline` for video and pdf, `attachment` otherwise)
- **Nginx** (`docker/nginx/default.conf`) has `location /_protected_media/` marked `internal` with `alias /srv/courses/;`. The browser can never request this prefix directly — only Laravel can trigger it via the header.
- **Byte-range / seeking** is handled entirely by Nginx (`206 Partial Content`), so scrubbing the video is fast and PHP-free.
- **Laravel mode fallback** uses `response()->file($absolutePath, $headers)` for local dev where there is no `internal` location.

## 7. Course scanner (filesystem → database)

`app/Console/Commands/CoursesScanCommand.php` exposes:

```
php artisan courses:scan [path?] [--dry-run] [--with-thumbnails] [--no-thumbnails]
```

`app/Services/Courses/CourseScanner.php` does the real work:

1. Walks `COURSES_ROOT` recursively.
2. Classifies files as **video** (extensions from `COURSE_VIDEO_EXTENSIONS`) or **resource** (`COURSE_RESOURCE_EXTENSIONS`), skipping `COURSE_RESOURCE_EXCLUDE_DIRS` (`.git`, `node_modules`, `vendor`, …).
3. Hashes relative paths to upsert `courses`, `course_sections`, `lessons`, `resources` idempotently.
4. Preserves manual `display_title` edits when the source filename hasn't changed.
5. Marks records missing from disk as `is_missing=true` with `last_seen_at` stamped.
6. `--dry-run` runs inside a transaction that is rolled back, so counts can be previewed without writes.
7. When thumbnails are enabled, hands lessons to `MediaProbeService` for duration + poster + sprite generation.

`ScanSummary` aggregates created/updated/unchanged/missing counts and is printed as a summary table at the end of the command.

## 8. Preview sprites & timeline thumbnails

`app/Services/Courses/MediaProbeService.php` shells out to ffmpeg/ffprobe when available:

- `probeDuration()` — `ffprobe` → `duration_seconds` on lessons.
- `createThumbnail()` — single frame at ~2s, JPEG, stored at `storage/app/private/course-previews/{course_id}/{lesson_hash}.jpg`.
- `createPreviewSprite()` — samples frames at `COURSE_PREVIEW_INTERVAL_SECONDS` (default 10s), tiles them via `fps` + `scale` + `pad` + `tile` filters, writes the sprite plus a JSON manifest describing duration, interval, frame count, columns and rows.

The frontend (`resources/views/lessons/show.blade.php`) fetches the manifest via `/lessons/{lesson}/preview-manifest` and the sprite via `/lessons/{lesson}/preview-sprite` to render hover previews on the seek bar. If ffmpeg/ffprobe are absent, scanning still succeeds with `thumbnail_path` / `preview_manifest_path` / `duration_seconds` left null — preview features degrade gracefully.

## 9. Authentication

- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`: email + password via `Auth::attempt()`.
- Rate limit: 5 failed attempts per IP+email → 60s lockout (`RateLimiter`).
- Session is regenerated on login, invalidated + token-rotated on logout.
- No registration route — users are created manually via `php artisan tinker` (see README).
- All media + course pages live behind the `auth` middleware group; no API tokens or guest streaming.

## 10. Docker topology

`docker-compose.yml` defines three services:

| Service | Image / build | Role |
|---|---|---|
| `app` | `docker/php/Dockerfile` (php-fpm + ffmpeg + composer deps) | Runs Laravel |
| `nginx` | `nginx:1.27-alpine` | Public port `${WEB_PORT:-8080}:80`; serves Laravel and protected media |
| `db` | `mysql:8.4` | Exposed on host `3307`; healthcheck via `mysqladmin ping` |

Volume mounts:

- `./` → `/var/www/html` (code, rw in `app`, ro in `nginx`)
- `./courses` → `/srv/courses` **read-only** in both `app` and `nginx`
- `./docker/nginx/default.conf` → `/etc/nginx/conf.d/default.conf:ro`
- Named volume `db_data` → `/var/lib/mysql`

Compose env pins `COURSE_STREAM_DRIVER=accel`, `SESSION_DRIVER=database`, `CACHE_STORE=database`, `QUEUE_CONNECTION=database` so no Redis is required.

## 11. Frontend

- Vite + Tailwind 4 (`vite.config.js`, `resources/css`, `resources/js`).
- Blade templates only; no SPA framework.
- Key pages:
  - `auth/login.blade.php` — login form
  - `courses/index.blade.php` — course grid with counts
  - `courses/show.blade.php` — sections + lessons + resources tree
  - `lessons/show.blade.php` — HTML5 player, sidebar nav, resume position, hover-preview sprite logic
  - `partials/resource-item.blade.php`, `partials/resource-preview-modal.blade.php` — PDF/text/download UX
  - `layouts/app.blade.php` — base layout

## 12. Configuration surface (`.env`)

The streaming and scan behaviour is driven entirely by env:

- `COURSES_ROOT` — absolute path to the media root (e.g. `/srv/courses`).
- `COURSE_INTERNAL_MEDIA_PREFIX` — Nginx internal location prefix (`/_protected_media`).
- `COURSE_STREAM_DRIVER` — `auto` | `accel` | `laravel`.
- `COURSE_PREVIEW_ENABLED`, `COURSE_PREVIEW_DIRECTORY`, `COURSE_PREVIEW_WIDTH`, `COURSE_PREVIEW_HEIGHT`, `COURSE_PREVIEW_COLUMNS`, `COURSE_PREVIEW_INTERVAL_SECONDS`, `COURSE_PREVIEW_MAX_FRAMES` — preview generation tuning.
- `COURSE_FFMPEG_BINARY`, `COURSE_FFPROBE_BINARY` — binary paths or names.
- `COURSE_VIDEO_EXTENSIONS`, `COURSE_RESOURCE_EXTENSIONS`, `COURSE_RESOURCE_EXCLUDE_DIRS` — classification rules.
- Standard Laravel: `APP_*`, `DB_*`, `SESSION_DRIVER`, `CACHE_STORE`, `QUEUE_CONNECTION`.

## 13. Security posture

- Course files are stored **outside** `public/`; nothing under `COURSES_ROOT` is web-rooted.
- The `/_protected_media/` Nginx location is marked `internal` — only reachable via Laravel's `X-Accel-Redirect`.
- Every stream/resource route is auth-guarded and looks up the file by DB id, never by client-supplied path.
- `StreamController::resolveSafeAbsolutePath()` blocks `..`, normalises slashes, and validates the resolved path is inside `COURSES_ROOT`.
- Path components are rawurl-encoded before being inserted into the `X-Accel-Redirect` header.
- Login is rate-limited (5/60s per IP+email); session is regenerated on success and invalidated on logout.
- DB never holds file contents — only metadata, paths, and progress.

## 14. Testing

- PHPUnit configured in `phpunit.xml`; run via `php artisan test`.
- Tests use SQLite by default (requires `pdo_sqlite` for the PHP CLI).
- `.phpunit.result.cache` is committed alongside, indicating regular local runs.

## 15. Operational notes

- First-time setup: `composer install`, `cp .env.example .env`, `php artisan key:generate`, `php artisan migrate`.
- Create the single user with `php artisan tinker` (no registration UI).
- After dropping new files into `./courses`, re-run `php artisan courses:scan /srv/courses --with-thumbnails` to refresh metadata and previews.
- For local dev without Nginx: set `COURSE_STREAM_DRIVER=laravel`.
- In production behind Nginx: keep `COURSE_STREAM_DRIVER=accel` and ensure the `internal` location is configured (see `docs/nginx-course-library.conf`).
