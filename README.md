# Private Video Course Library (Laravel + Nginx)

Private Laravel application for browsing and watching locally uploaded course files on a VPS.

## What This App Does

- Session login (private access only).
- Scans a real filesystem course directory and stores metadata only.
- Supports mixed course layouts:
  - root-level videos
  - chapter/section folders
  - mixed videos + resources
  - nested folders
- Serves videos/resources through **Nginx internal location** using `X-Accel-Redirect`.
- HTML5 player page with:
  - play/pause, seek, fullscreen, playback speed (native controls)
  - lesson sidebar/navigation
  - resume from last watched position
- Resource behavior:
  - `.pdf` opens inline in a new tab
  - `.txt` and `.go` can be previewed in an in-app dialog
  - other resource types download normally
- Stores lesson progress per authenticated user.

## Architecture

- **Laravel**:
  - Auth, course metadata, section/lesson/resource indexing, access checks, UI.
  - Returns protected internal URLs via `X-Accel-Redirect`.
- **Nginx**:
  - Serves actual file bytes directly from disk (`/srv/courses` by default).
  - Handles byte-range requests for video seeking (no PHP streaming).
- **Database**:
  - Stores metadata/references only, never video content.

## Data Model

- `courses`
- `course_sections`
- `lessons`
- `resources`
- `lesson_progress`

Each record tracks source path metadata, ordering, and missing-file state (`is_missing`, `last_seen_at`).

## Environment Configuration

Add to `.env`:

```env
COURSES_ROOT=/srv/courses
COURSE_INTERNAL_MEDIA_PREFIX=/_protected_media
COURSE_STREAM_DRIVER=auto
COURSE_PREVIEW_ENABLED=true
COURSE_PREVIEW_DIRECTORY=course-previews
COURSE_PREVIEW_WIDTH=160
COURSE_PREVIEW_HEIGHT=90
COURSE_PREVIEW_COLUMNS=10
COURSE_PREVIEW_INTERVAL_SECONDS=10
COURSE_PREVIEW_MAX_FRAMES=120
COURSE_FFMPEG_BINARY=ffmpeg
COURSE_FFPROBE_BINARY=ffprobe
COURSE_VIDEO_EXTENSIONS=mp4,mkv,webm,m4v,mov,avi,flv,ts,wmv
COURSE_RESOURCE_EXTENSIONS=pdf,zip,rar,7z,tar,gz,doc,docx,ppt,pptx,xls,xlsx,txt,md,srt,vtt,jpg,jpeg,png,svg,webp,json,yaml,yml,xml,csv,sql,go,py,js,ts,tsx,jsx,java,kt,cs,cpp,c,h,hpp,php,sh,bat,ps1,proto
COURSE_RESOURCE_EXCLUDE_DIRS=.git,node_modules,vendor,dist,build,target,.idea,.vscode,__pycache__,.next,.nuxt,coverage
```

## Setup (VPS)

```bash
cd /var/www/streamapp
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

## Docker Compose

Run the app with PHP-FPM + Nginx + MySQL using the included Docker setup.

1. Prepare env and media folder:

```bash
cp .env.example .env
mkdir -p courses
```

2. Start containers:

```bash
docker compose up -d --build
```

3. Initialize Laravel (first run):

```bash
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
```

4. Open app:

```text
http://localhost:8080
```

If port `8080` is busy, run on another port (example `8081`):

```bash
WEB_PORT=8081 docker compose up -d --build
```

PowerShell equivalent:

```powershell
$env:WEB_PORT=8081
docker compose up -d --build
```

5. Scan mounted course files (generate poster + timeline hover previews):

```bash
docker compose exec app php artisan courses:scan /srv/courses --with-thumbnails
```

Notes:

- Host `./courses` is mounted read-only to `/srv/courses` in both `app` and `nginx` containers.
- Streaming uses `COURSE_STREAM_DRIVER=accel` in compose, so Nginx handles protected media bytes.
- MySQL is exposed on host port `3307`.

Create your private user:

```bash
php artisan tinker --execute="App\Models\User::query()->updateOrCreate(['email'=>'you@example.com'], ['name'=>'Owner', 'password'=>bcrypt('CHANGE_ME_NOW')]);"
```

## Scan/Sync Courses

Initial import (with preview generation):

```bash
php artisan courses:scan /srv/courses --with-thumbnails
```

Dry run:

```bash
php artisan courses:scan /srv/courses --dry-run --no-thumbnails
```

Disable thumbnail/preview generation:

```bash
php artisan courses:scan /srv/courses --no-thumbnails
```

Re-run scan whenever you add/remove files. The scanner:

- upserts metadata
- preserves manual lesson/course display titles
- marks removed files as `is_missing=true`

## Nginx Configuration

Example production config: [docs/nginx-course-library.conf](docs/nginx-course-library.conf)

Critical parts:

- `location /_protected_media/ { internal; alias /srv/courses/; }`
- Laravel sends `X-Accel-Redirect: /_protected_media/<encoded-relative-path>`
- Browser requests are authenticated through Laravel first
- Nginx serves bytes directly from disk

### Byte-range and Seeking

Browsers request partial file segments using the `Range` header (for example when seeking).
Nginx responds with partial content (`206`) and only the requested byte range.
This avoids full-file download and makes seeking fast.

## Routes

- `GET /login`
- `POST /login`
- `POST /logout`
- `GET /courses`
- `GET /courses/{course}`
- `GET /lessons/{lesson}`
- `POST /progress/{lesson}`
- `GET /stream/lessons/{lesson}`
- `GET /stream/resources/{resource}`
- `GET /lessons/{lesson}/thumbnail`
- `GET /lessons/{lesson}/preview-manifest`
- `GET /lessons/{lesson}/preview-sprite`

All course/media routes require authentication.

## Security Notes

- Course files must stay **outside** Laravel `public/`.
- Direct video/resource URLs are never exposed as absolute server paths.
- Stream/download endpoints are ID-based and auth-guarded.
- Path traversal is blocked by server-side normalized path checks.
- Nginx internal location blocks unauthenticated direct media access.

## Local Development Without Nginx

If you run with `php artisan serve`, Nginx internal redirects are unavailable.
Set this in `.env`:

```env
COURSE_STREAM_DRIVER=laravel
```

For VPS/production with Nginx `internal` location, use:

```env
COURSE_STREAM_DRIVER=accel
```

## Optional ffmpeg/ffprobe

If `ffprobe` exists, scanner attempts duration extraction.
If `ffmpeg` exists and previews are enabled, scanner generates:

- lesson poster images (`poster=...`)
- timeline hover preview sprites + JSON manifests
If tools are missing, scan still succeeds and metadata fields remain nullable.

## Testing

```bash
php artisan test
```

If tests fail with `could not find driver` on SQLite, install/enable `pdo_sqlite` for your PHP CLI.
