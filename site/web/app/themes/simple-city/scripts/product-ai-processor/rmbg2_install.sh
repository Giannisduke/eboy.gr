#!/bin/bash
# One-time setup for RMBG-2.0 server on the Ubuntu GPU PC.
# Run as the normal user (not root).
#
# Usage:
#   bash rmbg2_install.sh

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
# venv must live on a local filesystem (not NTFS/exFAT mounts — no symlink support)
VENV_DIR="${RMBG2_VENV:-$HOME/.venvs/rmbg2}"

echo "=== RMBG-2.0 install — $(date) ==="
echo "Script dir : $SCRIPT_DIR"
echo "Venv       : $VENV_DIR"
echo ""

# ── Python venv ──────────────────────────────────────────────────────────────
python3 -m venv "$VENV_DIR"
# shellcheck disable=SC1091
source "$VENV_DIR/bin/activate"
pip install --upgrade pip --quiet

# ── PyTorch (CUDA 12.1) ──────────────────────────────────────────────────────
echo "Installing PyTorch (CUDA 12.1)..."
pip install torch torchvision \
    --index-url https://download.pytorch.org/whl/cu121 \
    --quiet

# ── Other deps ───────────────────────────────────────────────────────────────
echo "Installing FastAPI + model deps..."
pip install \
    fastapi \
    "uvicorn[standard]" \
    transformers \
    huggingface_hub \
    Pillow \
    numpy \
    --quiet

# ── Pre-download model weights ────────────────────────────────────────────────
echo "Downloading briaai/RMBG-2.0 weights (first time only)..."
python3 - <<'PYEOF'
from transformers import AutoModelForImageSegmentation
model = AutoModelForImageSegmentation.from_pretrained(
    'briaai/RMBG-2.0',
    trust_remote_code=True,
)
print("Model weights downloaded OK")
PYEOF

echo ""
echo "=== Setup complete ==="
echo ""
echo "Start the server:"
echo "  $VENV_DIR/bin/python $SCRIPT_DIR/rmbg2_server.py"
echo ""
echo "Run in background (screen):"
echo "  screen -dmS rmbg2 $VENV_DIR/bin/python $SCRIPT_DIR/rmbg2_server.py"
echo ""
echo "Test:"
echo "  curl http://localhost:7000/health"
