#!/usr/bin/env bash
# Builds the full GitHub release package from the current HEAD commit via
# `git archive` (applies .gitattributes export-ignore - dev-only files like
# .gitignore/.gitattributes/README.md/bin/ are excluded). Unlike
# build-wporg-zip.sh, this keeps the bundled .po/.mo translation files,
# since GitHub-distributed installs don't get WordPress.org's automatic
# translation loading.
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SLUG="onestudio-vietnam-address-for-woocommerce"
VERSION="$(grep -m1 '^ \* Version:' "$REPO_ROOT/vn-address-woocommerce.php" | sed -E 's/.*Version:[[:space:]]*//')"
OUT_DIR="${1:-$REPO_ROOT/build}"

rm -rf "$OUT_DIR/$SLUG"
mkdir -p "$OUT_DIR/$SLUG"

cd "$REPO_ROOT"
git archive HEAD | tar -x -C "$OUT_DIR/$SLUG"
rm -rf "$OUT_DIR/$SLUG/bin"

cd "$OUT_DIR"
ZIP_NAME="${SLUG}-${VERSION}.zip"
rm -f "$ZIP_NAME"
zip -rq "$ZIP_NAME" "$SLUG" -x "*.DS_Store"

echo "Built: $OUT_DIR/$ZIP_NAME"
echo "Contains $(unzip -l "$ZIP_NAME" | tail -1 | awk '{print $2}') files"
