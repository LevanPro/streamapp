#!/usr/bin/env bash
#
# upload-courses.sh — push local course folder(s) to the streamapp VPS.
#
# Uploads the WHOLE directory you pass (the folder itself, not just its
# contents): `upload-courses.sh /path/to/My Course` creates
# `/var/www/streamapp/courses/My Course/...` on the server. A trailing
# slash on the argument is ignored, so behaviour is always "copy the dir".
#
# Usage:
#   scripts/upload-courses.sh [options] DIR [DIR ...]
#
# Options:
#   --scan       After a successful upload, run `courses:scan` on the server
#                so the new lessons appear (the running queue worker then
#                generates posters/sprites/manifests asynchronously).
#   --dry-run    Show exactly what rsync would transfer, change nothing,
#                and skip the scan.
#   --delete     Mirror mode: also delete server-side files that no longer
#                exist locally *within each uploaded folder*. Off by default.
#   -h, --help   This help.
#
# Examples (run these LOCALLY, from the repo root: cd /mnt/d/streamapp):
#
#   # 1. Dry run first — safe, changes nothing, proves it copies the FOLDER:
#   scripts/upload-courses.sh --dry-run "/mnt/d/courses/Git In Depth"
#
#   # 2. Real upload + register it in the app (recommended one-liner):
#   scripts/upload-courses.sh --scan "/mnt/d/courses/Git In Depth"
#
#   # 3. Several courses at once:
#   scripts/upload-courses.sh --scan \
#     "/mnt/d/courses/Git In Depth" \
#     "/mnt/d/courses/Docker Mastery" \
#     "/mnt/c/Users/Levan/Downloads/Kubernetes 101"
#
#   # 4. Upload now, scan later (huge course / slow link). Re-running is
#   #    near-instant — rsync skips already-sent files, then --scan registers:
#   scripts/upload-courses.sh "/mnt/d/courses/Big Course"
#   scripts/upload-courses.sh --scan "/mnt/d/courses/Big Course"
#
#   # 5. Trailing slash is irrelevant — both create courses/Git In Depth/:
#   scripts/upload-courses.sh "/mnt/d/courses/Git In Depth"
#   scripts/upload-courses.sh "/mnt/d/courses/Git In Depth/"
#
# Tips: quote paths with spaces; a dropped upload just needs the same
# command again (it resumes); --scan is safe to repeat (idempotent).
#
# Env overrides (sane defaults baked in from the known deployment):
#   REMOTE_HOST         ssh alias / host         (default: myvps)
#   REMOTE_COURSES_DIR  course root on server    (default: /var/www/streamapp/courses)
#   REMOTE_APP_DIR      compose project dir      (default: /var/www/streamapp)
#
set -euo pipefail

REMOTE_HOST="${REMOTE_HOST:-myvps}"
REMOTE_COURSES_DIR="${REMOTE_COURSES_DIR:-/var/www/streamapp/courses}"
REMOTE_APP_DIR="${REMOTE_APP_DIR:-/var/www/streamapp}"

do_scan=0
dry_run=0
do_delete=0
sources=()

die() { echo "error: $*" >&2; exit 1; }

# Print the leading comment block (line 2 up to the first non-comment
# line) with the "# " prefix stripped — length-independent, so adding
# more examples above never truncates --help.
usage() { awk 'NR==1{next} /^#/{sub(/^# ?/,""); print; next} {exit}' "$0"; exit "${1:-0}"; }

# ---- parse args -----------------------------------------------------------
while [[ $# -gt 0 ]]; do
  case "$1" in
    --scan)     do_scan=1 ;;
    --dry-run)  dry_run=1 ;;
    --delete)   do_delete=1 ;;
    -h|--help)  usage 0 ;;
    --)         shift; while [[ $# -gt 0 ]]; do sources+=("$1"); shift; done; break ;;
    -*)         die "unknown option: $1 (try --help)" ;;
    *)          sources+=("$1") ;;
  esac
  shift
done

[[ ${#sources[@]} -gt 0 ]] || { echo "no directories given." >&2; usage 1; }

command -v rsync >/dev/null 2>&1 || die "rsync not found on PATH"

# ---- normalise sources: strip trailing slashes, require real dirs ---------
clean_sources=()
for src in "${sources[@]}"; do
  while [[ "$src" == */ && "$src" != "/" ]]; do src="${src%/}"; done
  [[ -d "$src" ]] || die "not a directory: $src"
  # realpath gives a clean, slash-free absolute path so the transferred
  # top-level name is unambiguous (handles ".", symlinks, "a/./b", etc.).
  if command -v realpath >/dev/null 2>&1; then
    src="$(realpath "$src")"
  fi
  clean_sources+=("$src")
done

# ---- preflight: destination must exist on the server ----------------------
echo ">> checking ${REMOTE_HOST}:${REMOTE_COURSES_DIR} ..."
ssh "$REMOTE_HOST" "test -d $(printf '%q' "$REMOTE_COURSES_DIR")" \
  || die "remote courses dir missing or unreachable: ${REMOTE_HOST}:${REMOTE_COURSES_DIR}"

# ---- rsync ----------------------------------------------------------------
# -r -l -t : recurse, keep symlinks, preserve mtimes (mtimes matter — the
#            scanner uses them and incremental re-runs rely on them).
# NO -p -o -g : never copy perms/owner/group; the repo lives on a WSL/NTFS
#            mount where those are meaningless. --chmod forces correct,
#            world-readable modes on the server (containers read as www-data).
# --partial / --partial-dir : resume interrupted large video transfers.
# --info=progress2 -h : single overall progress line, human-readable sizes.
rsync_opts=(
  -rlt
  --chmod=D755,F644
  --partial --partial-dir=.rsync-partial
  --info=progress2 -h
  --exclude='.DS_Store'
  --exclude='Thumbs.db'
  --exclude='desktop.ini'
  --exclude='.rsync-partial/'
  --exclude='@eaDir/'        # Synology junk, harmless if absent
)
[[ $dry_run    -eq 1 ]] && rsync_opts+=(--dry-run --itemize-changes)
[[ $do_delete  -eq 1 ]] && rsync_opts+=(--delete)

dest="${REMOTE_HOST}:$(printf '%q' "${REMOTE_COURSES_DIR%/}/")"

for src in "${clean_sources[@]}"; do
  base="$(basename "$src")"
  echo
  echo ">> uploading: '${base}'"
  echo "   ${src}  ->  ${REMOTE_HOST}:${REMOTE_COURSES_DIR%/}/${base}/"
  # No trailing slash on "$src" => rsync copies the directory itself.
  rsync "${rsync_opts[@]}" -e ssh "$src" "$dest"
done

if [[ $dry_run -eq 1 ]]; then
  echo
  echo ">> dry run only — nothing changed, scan skipped."
  exit 0
fi

# ---- optional: register new lessons + kick preview generation -------------
scan_cmd="cd $(printf '%q' "$REMOTE_APP_DIR") && docker compose exec -T app php artisan courses:scan /srv/courses --with-thumbnails"
if [[ $do_scan -eq 1 ]]; then
  echo
  echo ">> running courses:scan on ${REMOTE_HOST} ..."
  ssh "$REMOTE_HOST" "$scan_cmd"
  echo
  echo ">> done. The queue worker generates posters/sprites in the background."
  echo "   watch: ssh ${REMOTE_HOST} \"cd ${REMOTE_APP_DIR} && docker compose exec -T app php artisan tinker --execute='echo \\\\App\\\\Models\\\\Lesson::whereNotNull(\\\"preview_manifest_path\\\")->count();'\""
else
  echo
  echo ">> upload complete. New folders are on disk but NOT yet in the app."
  echo "   Register them with --scan next time, or run now:"
  echo "   ssh ${REMOTE_HOST} '${scan_cmd}'"
fi
