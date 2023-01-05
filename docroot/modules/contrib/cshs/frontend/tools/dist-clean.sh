#!/usr/bin/env bash

set -e

DIST="$(node tools/printPath.js dist)"

if [[ -d "$DIST" ]]; then
    echo "==> [INFO] Removing \"$DIST\"."
    rm -rf "$DIST"
fi

echo "==> [INFO] Creating \"$DIST\""
mkdir -p "$DIST"
