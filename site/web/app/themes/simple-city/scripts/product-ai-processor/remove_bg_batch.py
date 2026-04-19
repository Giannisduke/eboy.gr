#!/usr/bin/env python3
"""
Batch background removal — BRIA RMBG 2.0 (remote) or rembg (local fallback).
Called by PHP ProductSync.php via rembg_run.sh.

Usage:
    python remove_bg_batch.py input1 output1.webp [input2 output2.webp ...]

Remote mode (set REMBG_HOST in .env.local):
    Sends each image to the BRIA RMBG 2.0 server on the Ubuntu GPU PC.
    Only requires requests + Pillow — no local model needed.

Local mode (default):
    Uses the local rembg model (birefnet-general by default).
    Requires rembg[cpu] installed in the venv.

Env vars:
    REMBG_HOST   — hostname/IP of Ubuntu GPU PC (enables remote mode)
    BRIA_PORT    — port of BRIA server (default: 7001)
    REMBG_MODEL  — model name for local fallback (default: birefnet-general)
"""
import sys
import io
import os


def process_remote(pairs: list, host: str):
    try:
        import requests
        from PIL import Image
    except ImportError as e:
        print(f"IMPORT_ERROR: {e}", file=sys.stderr)
        sys.exit(2)

    bria_port = os.environ.get('BRIA_PORT', '7001')
    url = f"http://{host}:{bria_port}/api/remove"

    for input_path, output_path in pairs:
        try:
            original = Image.open(input_path).convert('RGBA')

            buf = io.BytesIO()
            original.convert('RGB').save(buf, 'JPEG', quality=95)
            buf.seek(0)

            response = requests.post(url, files={'file': buf}, timeout=120)
            response.raise_for_status()

            # Use mask from BRIA, colors from original
            alpha = Image.open(io.BytesIO(response.content)).convert('RGBA').split()[3]
            original.putalpha(alpha)

            original.save(output_path, 'webp', quality=92)
            print(f"OK:{output_path}", flush=True)

        except Exception as e:
            print(f"ERROR:{input_path}:{e}", file=sys.stderr, flush=True)


def process_local(pairs: list, model: str):
    try:
        from rembg import remove, new_session
        from PIL import Image
    except ImportError as e:
        print(f"IMPORT_ERROR: {e}", file=sys.stderr)
        sys.exit(2)

    session = new_session(model)

    for input_path, output_path in pairs:
        try:
            with open(input_path, 'rb') as f:
                input_data = f.read()

            output_data = remove(
                input_data,
                session=session,
                alpha_matting=True,
                alpha_matting_foreground_threshold=240,
                alpha_matting_background_threshold=10,
                alpha_matting_erode_size=15,
            )
            img = Image.open(io.BytesIO(output_data))
            img.save(output_path, 'webp', quality=92)
            print(f"OK:{output_path}", flush=True)

        except Exception as e:
            print(f"ERROR:{input_path}:{e}", file=sys.stderr, flush=True)


def main():
    args = sys.argv[1:]

    if not args or len(args) % 2 != 0:
        print("Usage: remove_bg_batch.py input1 output1 [input2 output2 ...]", file=sys.stderr)
        sys.exit(1)

    pairs = [(args[i], args[i + 1]) for i in range(0, len(args), 2)]

    rembg_host = os.environ.get('REMBG_HOST', '').strip()

    if rembg_host:
        process_remote(pairs, rembg_host)
    else:
        model = os.environ.get('REMBG_MODEL', 'birefnet-general')
        process_local(pairs, model)


if __name__ == '__main__':
    main()
