#!/usr/bin/env bash
#
# Builds a WordPress.org-style distribution zip, honoring .distignore.
#
# wp dist-archive would normally do this, but it shells out via proc_open(),
# which this environment's php.ini disables (config/php/php.ini:disable_functions).
#
# Usage: bin/build-dist.sh

set -euo pipefail

PLUGIN_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SLUG="plumbline-labs-service-schema-for-woocommerce"
VERSION=$(grep -m1 "^Stable tag:" "$PLUGIN_DIR/readme.txt" | sed 's/Stable tag: *//')

BUILD_ROOT="$(mktemp -d)"
STAGE_DIR="$BUILD_ROOT/$SLUG"

trap 'rm -rf "$BUILD_ROOT"' EXIT

mkdir -p "$STAGE_DIR"

grep -v '^#' "$PLUGIN_DIR/.distignore" | grep -v '^$' > "$BUILD_ROOT/exclude.txt"

rsync -a --exclude-from="$BUILD_ROOT/exclude.txt" "$PLUGIN_DIR/" "$STAGE_DIR/"

mkdir -p "$PLUGIN_DIR/build"
ZIP_PATH="$PLUGIN_DIR/build/${SLUG}-${VERSION}.zip"
rm -f "$ZIP_PATH"

( cd "$BUILD_ROOT" && zip -rq "$ZIP_PATH" "$SLUG" -x '*.DS_Store' )

echo "Built: $ZIP_PATH"
unzip -l "$ZIP_PATH"
