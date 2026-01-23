#!/usr/bin/env bash
set -euo pipefail

DRY_RUN=false
for arg in "$@"; do
  case "$arg" in
    --dry-run)
      DRY_RUN=true
      ;;
    *)
      echo "Usage: $0 [--dry-run]" >&2
      exit 1
      ;;
  esac
done

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
VIDEOS_DIR="$PROJECT_ROOT/videos"
LOG_DIR="$PROJECT_ROOT/storage/logs"
LOG_FILE="$LOG_DIR/video_migration.log"

mkdir -p "$LOG_DIR"
touch "$LOG_FILE"

log_action() {
  local message="$1"
  local timestamp
  timestamp="$(date '+%Y-%m-%d %H:%M:%S')"
  printf '[%s] %s\n' "$timestamp" "$message" | tee -a "$LOG_FILE"
}

create_directory() {
  local dir="$1"
  if [ -d "$dir" ]; then
    log_action "Directory already exists: $dir"
    return
  fi
  if [ "$DRY_RUN" = true ]; then
    log_action "DRY-RUN: would create directory $dir"
    return
  fi
  log_action "Creating directory: $dir"
  mkdir -p "$dir"
}

TIP_ARTIFACTS=(
  "$VIDEOS_DIR/matches"
  "$VIDEOS_DIR/temp/downloads"
  "$VIDEOS_DIR/temp/yt-dlp"
  "$VIDEOS_DIR/temp/staging"
  "$VIDEOS_DIR/legacy/raw"
)

for dir in "${TIP_ARTIFACTS[@]}"; do
  create_directory "$dir"
done

shopt -s nullglob
raw_files=("$VIDEOS_DIR/raw/match_"*"_raw.mp4")
shopt -u nullglob

if [ "${#raw_files[@]}" -eq 0 ]; then
  log_action "No raw videos found to migrate."
fi

for src_raw in "${raw_files[@]}"; do
  filename="$(basename "$src_raw")"
  match_id="${filename#match_}"
  match_id="${match_id%_raw.mp4}"
  match_dir="$VIDEOS_DIR/matches/match_${match_id}"

  match_subdirs=(
    "$match_dir/source/veo/standard"
    "$match_dir/source/veo/panoramic"
    "$match_dir/source/upload"
    "$match_dir/working/stitched"
    "$match_dir/working/transcoded"
    "$match_dir/working/thumbnails"
    "$match_dir/analysis/tracking/players"
    "$match_dir/analysis/tracking/ball"
    "$match_dir/analysis/homography"
    "$match_dir/analysis/heatmaps"
    "$match_dir/exports/clips"
    "$match_dir/exports/highlights"
    "$match_dir/exports/reports"
  )

  for subdir in "${match_subdirs[@]}"; do
    create_directory "$subdir"
  done

  destination="$match_dir/source/veo/standard/match_${match_id}_standard.mp4"
  legacy_destination="$VIDEOS_DIR/legacy/raw/$filename"

  if [ -f "$destination" ]; then
    log_action "Standard video already present for match $match_id: $destination"
  elif [ -f "$src_raw" ]; then
    if [ "$DRY_RUN" = true ]; then
      log_action "DRY-RUN: would move $src_raw to $destination"
    else
      log_action "Moving raw video $src_raw -> $destination"
      mv "$src_raw" "$destination"
    fi
  else
    log_action "Raw match file missing, skipping move: $src_raw"
  fi

  if [ -f "$destination" ]; then
    if [ -f "$legacy_destination" ] && cmp -s "$destination" "$legacy_destination"; then
      log_action "Legacy copy already matches: $legacy_destination"
    else
      if [ "$DRY_RUN" = true ]; then
        log_action "DRY-RUN: would copy $destination to $legacy_destination"
      else
        log_action "Copying standard video to legacy archive: $legacy_destination"
        cp -p "$destination" "$legacy_destination"
      fi
    fi
  else
    log_action "Skipping legacy copy because standard file is unavailable for match $match_id"
  fi

done
