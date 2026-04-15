#!/usr/bin/env python3
"""
Batch background removal — local rembg model or remote rembg server.
Called by PHP ProductSync.php via rembg_run.sh.

Usage:
    python remove_bg_batch.py input1 output1.webp [input2 output2.webp ...]

Remote mode (set REMBG_HOST in .env.local):
    Sends each image to the rembg HTTP server running on the Ubuntu GPU PC.
    Only requires requests + Pillow — no local model needed.

Local mode (default):
    Uses the local rembg model (birefnet-general by default).
    Requires rembg[cpu] installed in the venv.

Env vars:
    REMBG_HOST   — hostname/IP of remote rembg server (enables remote mode)
    REMBG_PORT   — port of remote rembg server (default: 7000)
    REMBG_MODEL  — model name for local mode (default: birefnet-general)
"""
import sys
import io
import os


def process_remote(pairs: list, host: str, port: str):
    try:
        import requests
        from PIL import Image
    except ImportError as e:
        print(f"IMPORT_ERROR: {e}", file=sys.stderr)
        sys.exit(2)

    model = os.environ.get('REMBG_MODEL', 'birefnet-general')
    url = f"http://{host}:{port}/api/remove"
    params = {'model': model, 'ppm': '1'}

    for input_path, output_path in pairs:
        try:
            with open(input_path, 'rb') as f:
                response = requests.post(url, files={'file': f}, params=params, timeout=120)
            response.raise_for_status()

            img = Image.open(io.BytesIO(response.content))
            img.save(output_path, 'webp', quality=92)
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

            output_data = remove(input_data, session=session)
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
    rembg_port = os.environ.get('REMBG_PORT', '7000').strip()

    if rembg_host:
        process_remote(pairs, rembg_host, rembg_port)
    else:
        model = os.environ.get('REMBG_MODEL', 'birefnet-general')
        process_local(pairs, model)


if __name__ == '__main__':
    main()
