#!/usr/bin/env bash
# Run periodically via cron to delete temporary files

DIR=$(dirname $(dirname $0))

find "${DIR}/temp" -name "ws-*" -mtime +1 -delete

find "${DIR}/temp" -name "img-*" -mtime +1 -delete
