#!/bin/bash
# Post-deploy fix-ups.
#
# After a Capistrano-style deploy, the new release directory needs:
#   1. symlink for `eboy-product-importer/data/xml_files` → /shared (so XML
#      feeds and progress files persist between releases).
#   2. symlink for `eboy-product-importer/product-ai-processor/logs` → /shared
#      (so Python logs persist between releases).
#   3. symlink for `eboy-product-importer/product-ai-processor/venv` → /shared
#      (Capistrano ships an empty venv shell; the real virtualenv lives in
#      /shared).
#   4. exec bit on every *.sh under product-ai-processor (some deploys lose it).
#   5. clear stale Acorn cache from previous release.
#
# Run after every deploy:
#   bash /srv/www/eboy.gr/current/web/app/themes/simple-city/scripts/post-deploy.sh

set -e

RELEASE=$(readlink /srv/www/eboy.gr/current)
SHARED_PLUGIN=/srv/www/eboy.gr/shared/web/app/plugins/eboy-product-importer
DEST_PLUGIN=$RELEASE/web/app/plugins/eboy-product-importer

echo "Post-deploy for: $RELEASE"

mkdir -p "$DEST_PLUGIN/data"

rm -rf "$DEST_PLUGIN/data/xml_files"
ln -sfn "$SHARED_PLUGIN/data/xml_files" "$DEST_PLUGIN/data/xml_files"

rm -rf "$DEST_PLUGIN/product-ai-processor/logs"
ln -sfn "$SHARED_PLUGIN/product-ai-processor/logs" "$DEST_PLUGIN/product-ai-processor/logs"

rm -rf "$DEST_PLUGIN/product-ai-processor/venv"
ln -sfn "$SHARED_PLUGIN/product-ai-processor/venv" "$DEST_PLUGIN/product-ai-processor/venv"

chmod +x "$DEST_PLUGIN/product-ai-processor/"*.sh

# Clear stale Acorn cache from previous release. Compiled service paths in
# /web/app/cache/acorn point at the OLD release directory and produce a fatal
# error on the first frontend request after deploy. Removing the directory
# forces Acorn to re-build the cache against the new release paths.
# Cache files are written by php-fpm (user `web`), so we need sudo to wipe them.
ACORN_CACHE="$RELEASE/web/app/cache/acorn"
if [ -d "$ACORN_CACHE" ]; then
    sudo rm -rf "$ACORN_CACHE"
    echo "Cleared Acorn cache: $ACORN_CACHE"
fi

ls -la "$DEST_PLUGIN/"
