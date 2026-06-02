#!/usr/bin/env python3
"""
RMBG-2.0 Background Removal Server
Uses BRIA AI's RMBG-2.0 model — handles white-on-white and complex product shots.
Drop-in replacement for the rembg server (same /api/remove endpoint).

Usage:
    python rmbg2_server.py [--host 0.0.0.0] [--port 7000]

Env vars:
    RMBG2_PORT   — override default port (7000)
    RMBG2_HOST   — override default host (0.0.0.0)
"""

import argparse
import io
import logging
import os
import sys
from contextlib import asynccontextmanager

import numpy as np
import torch
import uvicorn
from fastapi import FastAPI, File, UploadFile
from fastapi.responses import JSONResponse, Response
from PIL import Image
from torchvision import transforms
from transformers import AutoModelForImageSegmentation

logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s %(levelname)s %(message)s',
    stream=sys.stdout,
)
logger = logging.getLogger(__name__)

device = 'cuda' if torch.cuda.is_available() else 'cpu'
model = None

# Input normalization as specified by RMBG-2.0
_transform = transforms.Compose([
    transforms.Resize((1024, 1024)),
    transforms.ToTensor(),
    transforms.Normalize([0.485, 0.456, 0.406], [0.229, 0.224, 0.225]),
])


@asynccontextmanager
async def lifespan(app: FastAPI):
    global model
    logger.info(f"Device: {device}")
    logger.info("Loading briaai/RMBG-2.0 ...")
    model = AutoModelForImageSegmentation.from_pretrained(
        'briaai/RMBG-2.0',
        trust_remote_code=True,
    )
    model.to(device)
    model.eval()
    logger.info("RMBG-2.0 ready")
    yield


app = FastAPI(title="RMBG-2.0 Server", lifespan=lifespan)


def _remove_background(image: Image.Image) -> Image.Image:
    """Return RGBA image with background removed."""
    original_size = image.size
    rgb = image.convert('RGB')

    tensor = _transform(rgb).unsqueeze(0).to(device)

    with torch.no_grad():
        result = model(tensor)

    # result is a list of predictions; last element is the finest mask
    mask = result[-1].sigmoid().squeeze().cpu().numpy()
    mask = (mask * 255).astype(np.uint8)
    mask_img = Image.fromarray(mask, mode='L').resize(original_size, Image.LANCZOS)

    rgba = rgb.convert('RGBA')
    rgba.putalpha(mask_img)
    return rgba


@app.get("/health")
async def health():
    return {"status": "ok", "model": "briaai/RMBG-2.0", "device": device}


@app.post("/api/remove")
async def api_remove(file: UploadFile = File(...)):
    """
    Drop-in replacement for the rembg /api/remove endpoint.
    Query params (model, ppm, am, af, ab, ae) are accepted but ignored —
    RMBG-2.0 handles edge refinement internally.
    Returns WebP with transparent background.
    """
    try:
        data = await file.read()
        image = Image.open(io.BytesIO(data))

        result = _remove_background(image)

        buf = io.BytesIO()
        result.save(buf, format='WEBP', quality=92)
        buf.seek(0)

        size_in  = len(data) // 1024
        size_out = buf.getbuffer().nbytes // 1024
        logger.info(f"OK {file.filename} {size_in}KB → {size_out}KB")

        return Response(content=buf.read(), media_type='image/webp')

    except Exception as e:
        logger.error(f"Error processing {file.filename}: {e}")
        return JSONResponse(status_code=500, content={"error": str(e)})


if __name__ == '__main__':
    parser = argparse.ArgumentParser()
    parser.add_argument('--host', default=os.environ.get('RMBG2_HOST', '0.0.0.0'))
    parser.add_argument('--port', type=int, default=int(os.environ.get('RMBG2_PORT', 7000)))
    args = parser.parse_args()

    logger.info(f"Starting on {args.host}:{args.port}")
    uvicorn.run(app, host=args.host, port=args.port, log_level='info')
