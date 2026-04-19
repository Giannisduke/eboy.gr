#!/usr/bin/env python3
"""BRIA RMBG 2.0 HTTP server on port 7001 — /api/remove compatible with rembg."""
import io
import numpy as np
from PIL import Image
import torch
from torchvision import transforms
from transformers import AutoModelForImageSegmentation
from fastapi import FastAPI, File, UploadFile
from fastapi.responses import Response
import uvicorn

app = FastAPI()

DEVICE = 'cuda' if torch.cuda.is_available() else 'cpu'
print(f"Loading briaai/RMBG-2.0 on {DEVICE}...")
model = AutoModelForImageSegmentation.from_pretrained('briaai/RMBG-2.0', trust_remote_code=True)
model = model.to(DEVICE)
model.eval()
print("Ready.")

transform = transforms.Compose([
    transforms.Resize((1024, 1024)),
    transforms.ToTensor(),
    transforms.Normalize([0.485, 0.456, 0.406], [0.229, 0.224, 0.225]),
])


@app.get('/ping')
def ping():
    return {'status': 'ok', 'device': DEVICE}


@app.post('/api/remove')
async def remove_background(file: UploadFile = File(...)):
    data = await file.read()
    img = Image.open(io.BytesIO(data)).convert('RGB')
    w, h = img.size

    inp = transform(img).unsqueeze(0).to(DEVICE)
    with torch.no_grad():
        preds = model(inp)[-1].sigmoid().cpu()

    mask = transforms.ToPILImage()(preds[0].squeeze()).resize((w, h), Image.LANCZOS)
    result = img.convert('RGBA')
    result.putalpha(mask)

    buf = io.BytesIO()
    result.save(buf, 'PNG')
    buf.seek(0)
    return Response(content=buf.read(), media_type='image/png')


if __name__ == '__main__':
    uvicorn.run(app, host='0.0.0.0', port=7001)
