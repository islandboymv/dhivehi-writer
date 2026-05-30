#!/usr/bin/env bash
#
# Build the WordPress.org-targeted variant of Dhivehi Writer.
#
# Differences from the canonical (GitHub) build:
#   - Noto Sans Thaana is the primary/default font (WP.org cannot bundle Faruma)
#   - the Faruma font file, @font-face rules, and dropdown option are removed
#   - the GitHub auto-update checker (lib/) is removed (WP.org ships updates)
#
# Output:
#   build/wporg/dhivehi-writer/     unpacked plugin (ready for SVN trunk)
#   build/dhivehi-writer-wporg.zip  installable zip
#
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT="$ROOT/build/wporg/dhivehi-writer"

echo "→ staging source"
rm -rf "$ROOT/build/wporg"
mkdir -p "$OUT"
rsync -a \
  --exclude='.git' \
  --exclude='.github' \
  --exclude='build' \
  --exclude='bin' \
  --exclude='lib' \
  --exclude='node_modules' \
  --exclude='.claude' \
  --exclude='.gitignore' \
  --exclude='README.md' \
  --exclude='*.zip' \
  --exclude='assets/fonts/faruma.woff' \
  "$ROOT"/ "$OUT"/

echo "→ stripping build-specific blocks (DHW_WPORG_STRIP_*)"
# Remove everything between the strip markers (inclusive), across php/css/js.
find "$OUT" -type f \( -name '*.php' -o -name '*.css' -o -name '*.js' \) -print0 \
  | xargs -0 perl -0777 -i -pe 's/[^\n]*DHW_WPORG_STRIP_START.*?DHW_WPORG_STRIP_END[^\n]*\n?//gs'

echo "→ flipping primary font to Noto Sans Thaana"
perl -i -pe "s/define\( 'DHW_DEFAULT_FONT', 'Faruma' \);/define( 'DHW_DEFAULT_FONT', 'Noto Sans Thaana' );/" "$OUT/dhivehi-writer.php"
perl -i -pe "s/define\( 'DHW_FONT_FALLBACK', \"'Faruma', 'Noto Sans Thaana', 'MV Boli', serif\" \);/define( 'DHW_FONT_FALLBACK', \"'Noto Sans Thaana', 'MV Boli', serif\" );/" "$OUT/dhivehi-writer.php"

echo "→ removing Faruma dropdown option"
perl -i -ne "print unless /^\s*'Faruma'\s*=>/" "$OUT/dhivehi-writer.php"

echo "→ dropping Faruma from CSS/JS fallback stacks"
find "$OUT" -type f \( -name '*.css' -o -name '*.js' \) -print0 \
  | xargs -0 perl -i -pe "s/'Faruma', 'Noto Sans Thaana'/'Noto Sans Thaana'/g"

echo "→ tidying readme wording"
perl -i -pe "s/Bundled web fonts \(Faruma \+ Noto Sans Thaana\)/Bundled web font (Noto Sans Thaana)/g" "$OUT/readme.txt"
perl -i -pe "s/the bundled Faruma and Noto Sans Thaana, so Dhivehi never breaks\. Faruma \(the classic Maldivian Thaana look\) is the default\./the bundled Noto Sans Thaana, so Dhivehi never breaks./g" "$OUT/readme.txt"
perl -i -pe "s/, and the \*\*Faruma\*\* Thaana font\././g" "$OUT/readme.txt"
perl -i -pe "s/Bundled Faruma \(default\) and Noto Sans Thaana web fonts/Bundled the Noto Sans Thaana web font/g" "$OUT/readme.txt"

echo "→ verifying build"
fail=0
php -l "$OUT/dhivehi-writer.php" >/dev/null || { echo "  ✗ PHP lint failed"; fail=1; }
[ -f "$OUT/assets/fonts/faruma.woff" ] && { echo "  ✗ faruma.woff still present"; fail=1; }
[ -d "$OUT/lib" ] && { echo "  ✗ lib/ (update checker) still present"; fail=1; }
grep -rqi "DHW_WPORG_STRIP" "$OUT" && { echo "  ✗ strip markers remain"; fail=1; }
grep -rqi "plugin-update-checker\|PucFactory" "$OUT" && { echo "  ✗ update-checker reference remains"; fail=1; }
# Functional Faruma references only (quoted font name or the font file) — prose/comments are fine.
grep -rq "faruma.woff" "$OUT" && { echo "  ✗ faruma.woff referenced:"; grep -rn "faruma.woff" "$OUT"; fail=1; }
grep -rq "'Faruma'" "$OUT" && { echo "  ✗ 'Faruma' used as a font name:"; grep -rn "'Faruma'" "$OUT"; fail=1; }
grep -q "define( 'DHW_DEFAULT_FONT', 'Noto Sans Thaana' );" "$OUT/dhivehi-writer.php" || { echo "  ✗ default font not flipped"; fail=1; }
[ "$fail" -eq 0 ] || { echo "BUILD FAILED"; exit 1; }

echo "→ zipping"
( cd "$ROOT/build/wporg" && zip -rq "$ROOT/build/dhivehi-writer-wporg.zip" dhivehi-writer )

echo "✓ WP.org build ready:"
echo "    $OUT"
echo "    $ROOT/build/dhivehi-writer-wporg.zip"
