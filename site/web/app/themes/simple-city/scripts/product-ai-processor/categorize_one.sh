#!/bin/sh
# Wrapper called by PHP to invoke categorize_one_cli.py.
# Same pattern as rembg_run.sh — locates the correct Python at runtime.

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

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

exec "$PYTHON" "$SCRIPT_DIR/categorize_one_cli.py" "$@"
