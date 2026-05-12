#!/bin/bash
# Post-deploy fix-ups for the simple-city theme.
#
# After a Capistrano-style deploy, the new release directory needs:
#   1. symlink for `scripts/xml_files` → /shared (so XML feeds and progress
#      files persist between releases).
#   2. symlink for `scripts/product-ai-processor/logs` → /shared (so Python
#      logs persist between releases).
#   3. exec bit on every *.sh under product-ai-processor (some deploys lose it).
#
# Run after every deploy:
#   bash /srv/www/eboy.gr/current/web/app/themes/simple-city/scripts/post-deploy.sh
#
# Or set up an alias:
#   alias post-deploy='bash /srv/www/eboy.gr/current/web/app/themes/simple-city/scripts/post-deploy.sh'

set -e

RELEASE=$(readlink /srv/www/eboy.gr/current)
SHARED=/srv/www/eboy.gr/shared/web/app/themes/simple-city/scripts
DEST=$RELEASE/web/app/themes/simple-city/scripts

echo "Post-deploy for: $RELEASE"

rm -rf "$DEST/xml_files"
ln -sfn "$SHARED/xml_files" "$DEST/xml_files"

rm -rf "$DEST/product-ai-processor/logs"
ln -sfn "$SHARED/product-ai-processor/logs" "$DEST/product-ai-processor/logs"

chmod +x "$DEST/product-ai-processor/"*.sh
ls -la "$DEST/"
