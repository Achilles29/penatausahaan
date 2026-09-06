#!/bin/sh
set -eu

ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
VERSION=$(/www/server/php/81/bin/php -r '$m=json_decode(file_get_contents($argv[1]),true); if(empty($m["version"])) exit(1); echo $m["version"];' "$ROOT/app-manifest.json")
OUTPUT_DIR=${PENATUS_RELEASE_DIR:-/var/lib/penatausahaan-releases}
STAMP=$(date -u +%Y%m%dT%H%M%SZ)
BUILD_EPOCH=${PENATUS_BUILD_EPOCH:-$(git log -1 --format=%ct 2>/dev/null || date +%s)}
NAME="namua-penatausahaan-${VERSION}-${STAMP}"
TEMP_ARCHIVE="$OUTPUT_DIR/.${NAME}.tar.gz.tmp"
ARCHIVE="$OUTPUT_DIR/${NAME}.tar.gz"
MANIFEST="$OUTPUT_DIR/${NAME}.release.json"

install -d -m 0750 "$OUTPUT_DIR"
cd "$ROOT"
if ! git rev-parse --verify HEAD >/dev/null 2>&1; then
  echo "Release build requires a Git commit." >&2
  exit 1
fi
if [ -n "$(git status --porcelain 2>/dev/null)" ]; then
  echo "Release build refused: the source worktree is not clean." >&2
  exit 1
fi
/www/server/php/81/bin/php tools/security_scan.php

tar \
  --sort=name \
  --mtime="@${BUILD_EPOCH}" \
  --owner=0 \
  --group=0 \
  --numeric-owner \
  --exclude='application/cache/sessions/*' \
  --exclude='application/cache/login_attempts/*' \
  --exclude='application/logs/*' \
  --exclude='application/controllers/Setup.php' \
  --exclude='tools/build_release.sh' \
  --exclude='tools/sign_release.php' \
  --exclude='assets/img/logo.png' \
  --exclude='assets/img/favicon.ico' \
  --exclude='*.log' \
  -czf "$TEMP_ARCHIVE" \
  application assets system database tools deploy .htaccess index.php README.md INSTALL.md app-manifest.json composer.json license.txt

if tar -tzf "$TEMP_ARCHIVE" | grep -Eq '(^|/)(docs|db_backup|\.git|\.env|penatus\.sql)(/|$)|application/cache/(sessions|login_attempts)/.+|application/logs/.+|assets/img/(logo\.png|favicon\.ico)$|application/controllers/Setup\.php$|tools/(build_release\.sh|sign_release\.php)$'; then
  echo "Release contains a forbidden path." >&2
  exit 1
fi
mv "$TEMP_ARCHIVE" "$ARCHIVE"
chmod 0640 "$ARCHIVE"

SHA256=$(sha256sum "$ARCHIVE" | awk '{print $1}')
SIZE=$(stat -c '%s' "$ARCHIVE")
COMMIT=$(git rev-parse --verify HEAD 2>/dev/null || printf 'unversioned')
DIRTY=false

/www/server/php/81/bin/php -r '
$applicationManifest=json_decode(file_get_contents($argv[8]."/app-manifest.json"),true,32,JSON_THROW_ON_ERROR);
$migrations=[];
foreach(glob($argv[8]."/database/migrations/[0-9]*_*.php")?:[] as $migration){
  $migrations[]=["file"=>basename($migration),"sha256"=>hash_file("sha256",$migration)];
}
$data=[
  "manifest_version"=>2,
  "product_code"=>"NAMUA_PENATAUSAHAAN",
  "version"=>$argv[1],
  "schema_version"=>(string)($applicationManifest["schema_version"]??""),
  "baseline_schema_version"=>(string)($applicationManifest["baseline_schema_version"]??""),
  "migrations"=>$migrations,
  "artifact"=>basename($argv[2]),
  "sha256"=>$argv[3],
  "size_bytes"=>(int)$argv[4],
  "built_at"=>gmdate(DATE_ATOM),
  "source_commit"=>$argv[5],
  "source_dirty"=>$argv[6]==="true",
  "contains_customer_data"=>false,
  "contains_secrets"=>false,
];
file_put_contents($argv[7],json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL,LOCK_EX);
' "$VERSION" "$ARCHIVE" "$SHA256" "$SIZE" "$COMMIT" "$DIRTY" "$MANIFEST" "$ROOT"
chmod 0640 "$MANIFEST"
/www/server/php/81/bin/php tools/sign_release.php "$MANIFEST"

printf 'Artifact: %s\n' "$ARCHIVE"
printf 'Manifest: %s\n' "$MANIFEST"
printf 'SHA256: %s\n' "$SHA256"
