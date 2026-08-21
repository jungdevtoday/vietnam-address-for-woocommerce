#!/usr/bin/env bash
# Builds the WordPress.org submission package: tracked files, minus
# dev-only files (.gitignore/.gitattributes/README.md, already excluded
# from `git archive` via .gitattributes export-ignore) and minus the
# bundled .po/.mo translation files (WordPress.org generates and serves
# these itself via translate.wordpress.org/GlotPress; the .pot template
# is kept). The GitHub release package is unaffected - it still ships via
# plain `git archive` and keeps all bundled translations.
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SLUG="onestudio-vietnam-address-for-woocommerce"
VERSION="$(grep -m1 '^ \* Version:' "$REPO_ROOT/vn-address-woocommerce.php" | sed -E 's/.*Version:[[:space:]]*//')"
OUT_DIR="${1:-$REPO_ROOT/build}"

rm -rf "$OUT_DIR"
mkdir -p "$OUT_DIR/$SLUG"

cd "$REPO_ROOT"
git ls-files \
  | grep -v -E '^(\.gitignore|\.gitattributes|README\.md)$' \
  | grep -v -E '^languages/.*\.(po|mo)$' \
  > "$OUT_DIR/.filelist"

rsync -a --files-from="$OUT_DIR/.filelist" "$REPO_ROOT/" "$OUT_DIR/$SLUG/"
rm "$OUT_DIR/.filelist"

cd "$OUT_DIR"
ZIP_NAME="${SLUG}-${VERSION}-wporg.zip"
rm -f "$ZIP_NAME"
zip -rq "$ZIP_NAME" "$SLUG" -x "*.DS_Store"

echo "Built: $OUT_DIR/$ZIP_NAME"
echo "Contains $(unzip -l "$ZIP_NAME" | tail -1 | awk '{print $2}') files"
echo "Language files included:"
unzip -l "$ZIP_NAME" | grep '/languages/' || echo "  (none)"
