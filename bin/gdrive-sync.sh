#!/bin/bash

# sync our backup folder to Google Drive

BACKUPDIR=$(dirname "$0")/backups

if ! [ -x "$(command -v rclone)" ]; then
  echo 'Error: rclone is not installed.' >&2
  exit 1
fi

flock -n ~/.gdrive-sync.lock rclone sync --dry-run "$BACKUPDIR" "g:backups/db"
