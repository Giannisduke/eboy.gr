#!/bin/sh
# Wrapper called by PHP to invoke remove_bg_batch.py.
# PHP file_exists() is blocked by open_basedir outside /srv/www/, but exec() is not.
# This script (inside the virtiofs mount) locates the correct Python at runtime.

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

# Load .env / .env.local so REMBG_HOST and other vars reach the Python script.
# The last file wins (local overrides base).
for ENV_FILE in "$SCRIPT_DIR/.env" "$SCRIPT_DIR/.env.local"; do
    if [ -f "$ENV_FILE" ]; then
        set -a
        # shellcheck disable=SC1090
        . "$ENV_FILE"
        set +a
    fi
done

# Locate the best available Python.
# When REMBG_HOST is set (remote server mode), only requests + Pillow are needed —
# no local rembg/onnxruntime. The venv from requirements.txt already has both.
PYTHON=""
for CANDIDATE in \
    "$SCRIPT_DIR/venv/bin/python3" \
    "/home/eboy.linux/.venvs/product-ai-processor/bin/python3" \
    "python3"
do
    if [ -x "$CANDIDATE" ] || command -v "$CANDIDATE" >/dev/null 2>&1; then
        PYTHON="$CANDIDATE"
        break
    fi
done

if [ -z "$PYTHON" ]; then
    echo "ERROR: No Python found" >&2
    exit 1
fi

exec "$PYTHON" "$SCRIPT_DIR/remove_bg_batch.py" "$@"
