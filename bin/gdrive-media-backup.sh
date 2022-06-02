#!/bin/bash

# Backup media to Google Drive

# Args:
#  full - do a complete copy of the media directory to Google Drive. This won't copy
#         files already on drive. Will not delete any files od Drive
#  sync - same as full, but will delete files on Drive which aren't on this server.
#         Use with care!!!
#  weekly - copy any files added/changed in the last 8 days. This will run reasonably
#         quickly as it doesn't have to a full compare between Drive and this server.

ARGS=''
if [[ "$1" = "--dry-run" ]]; then
  ARGS=" $1"
  shift
fi
if [[ $# -gt 1 ]]; then
    echo "Usage: [--dry-run] [full|sync|weekly] (default weekly)"
    exit 1
fi
cd $(dirname "$0")
source ./find-rclone.sh

echo Backing up Media files to Google Drive - option $1

case $1 in
  weekly|'')
    $RCLONE$ARGS copy --max-age 8d --no-traverse ../media/ g:backups/media/
    ;;
  full)
    $RCLONE$ARGS copy ../media/ g:backups/media/
    ;;
  sync)
    if [[ "$ARGS" = '' ]] ; then
      echo "WARNING: potentially dangerous operation"
      echo "This command will sync the media directory with Google Drive, which will delete files not on this server from Google Drive"
      read -p "Do you want to continue? <y/N> " prompt
      if [[ $prompt != "y" && $prompt != "Y" ]] ; then
        echo 'Backup aborted'
        exit 0
      fi
    fi
    $RCLONE$ARGS sync ../media/ g:backups/media/
    ;;
  *)
    echo "Usage: [--dry-run] [full|sync|weekly] (default weekly)"
    exit 1
    ;;
esac
