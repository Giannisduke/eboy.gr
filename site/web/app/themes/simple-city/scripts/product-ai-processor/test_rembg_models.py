#!/usr/bin/env python3
"""
Compare background removal across multiple rembg models + preprocessing profiles.
Applies color enhancement before sending to rembg, then saves results for visual comparison.

Usage:
    python test_rembg_models.py <image_path> [image_path2 ...]

Output:
    ~/Downloads/rembg_test/<profile>/<original_filename>.webp
"""
import sys
import os
import io
from pathlib import Path
from concurrent.futures import ThreadPoolExecutor, as_completed

try:
    import requests
    from PIL import Image, ImageEnhance, ImageOps, ImageFilter, ImageCms
    import numpy as np
except ImportError:
    print("ERROR: pip install requests Pillow numpy")
    sys.exit(1)

REMBG_HOST = os.environ.get('REMBG_HOST', '100.86.192.95')
REMBG_PORT = os.environ.get('REMBG_PORT', '7000')
BASE_URL = f"http://{REMBG_HOST}:{REMBG_PORT}/api/remove"
BRIA_URL  = f"http://{REMBG_HOST}:7001/api/remove"

REMBG_PARAMS = {'ppm': '1'}

# Each profile: (label, preprocessing_fn)
def enhance_none(img):
    return img

def enhance_contrast(img):
    img = ImageEnhance.Contrast(img).enhance(1.4)
    img = ImageEnhance.Sharpness(img).enhance(1.5)
    return img

def enhance_autocontrast(img):
    # Stretch histogram — pushes near-white BG to pure white, darkens midtones
    img = ImageOps.autocontrast(img, cutoff=1)
    img = ImageEnhance.Contrast(img).enhance(1.2)
    return img

def enhance_levels_dark(img):
    # Pull black point up slightly + boost contrast → edges more defined
    img = ImageEnhance.Brightness(img).enhance(0.92)
    img = ImageEnhance.Contrast(img).enhance(1.5)
    img = ImageEnhance.Sharpness(img).enhance(2.0)
    return img

def enhance_saturate(img):
    img = ImageEnhance.Color(img).enhance(1.2)
    img = ImageEnhance.Contrast(img).enhance(1.3)
    img = ImageOps.autocontrast(img, cutoff=0.5)
    return img

def enhance_furniture(img):
    """
    Furniture-specific profile:
    - Auto white balance (gray world assumption)
    - Clarity via unsharp mask (brings out wood/fabric texture)
    - Moderate saturation boost for richer colors
    - Contrast push to separate product from white BG
    """
    if img.mode != 'RGB':
        img = img.convert('RGB')
    arr = np.array(img, dtype=np.float32)

    # Gray world auto white balance
    mean_r, mean_g, mean_b = arr[:,:,0].mean(), arr[:,:,1].mean(), arr[:,:,2].mean()
    mean_gray = (mean_r + mean_g + mean_b) / 3
    arr[:,:,0] = np.clip(arr[:,:,0] * (mean_gray / mean_r), 0, 255)
    arr[:,:,1] = np.clip(arr[:,:,1] * (mean_gray / mean_g), 0, 255)
    arr[:,:,2] = np.clip(arr[:,:,2] * (mean_gray / mean_b), 0, 255)
    img = Image.fromarray(arr.astype(np.uint8))

    # Auto-levels: clip top/bottom 0.5% to remove sensor noise
    img = ImageOps.autocontrast(img, cutoff=0.5)

    # Clarity: unsharp mask for texture definition (wood grain, fabric weave)
    img = img.filter(ImageFilter.UnsharpMask(radius=1.5, percent=120, threshold=3))

    # Saturation boost — richer furniture colors
    img = ImageEnhance.Color(img).enhance(1.2)

    # Final contrast push for cleaner edges
    img = ImageEnhance.Contrast(img).enhance(1.25)

    return img

ICC_PROFILE = '/Volumes/eboy_hd/photo_correction/profiles/Coated_Fogra39L_VIGC_300.icc'

def enhance_fogra39(img):
    """
    Apply Fogra39L CMYK color correction before rembg:
    sRGB → CMYK (Fogra39L) → sRGB round-trip.
    Brings colors into print gamut — richer shadows, cleaner whites,
    more defined edges between product and white background.
    """
    if img.mode != 'RGB':
        img = img.convert('RGB')

    srgb_profile = ImageCms.createProfile('sRGB')
    fogra_profile = ImageCms.ImageCmsProfile(ICC_PROFILE)

    # sRGB → CMYK (renderingIntent=1 = Relative Colorimetric)
    to_cmyk = ImageCms.buildTransformFromOpenProfiles(
        srgb_profile, fogra_profile, 'RGB', 'CMYK', renderingIntent=1,
    )
    cmyk = ImageCms.applyTransform(img, to_cmyk)

    # CMYK → sRGB
    to_rgb = ImageCms.buildTransformFromOpenProfiles(
        fogra_profile, srgb_profile, 'CMYK', 'RGB', renderingIntent=1,
    )
    return ImageCms.applyTransform(cmyk, to_rgb)

AM0 = {'am': '0'}
AM1 = {'am': '1', 'af': '220', 'ab': '10', 'ae': '8'}

def enhance_identity(img):
    return img.convert('RGB')

def enhance_saturate(img):
    img = ImageEnhance.Color(img).enhance(1.2)
    img = ImageEnhance.Contrast(img).enhance(1.3)
    img = ImageOps.autocontrast(img, cutoff=0.5)
    return img

MODELS = [
    'birefnet-general',
    'birefnet-dis',
    'birefnet-hrsod',
    'bria-rmbg',
    'isnet-general-use',
    'u2net',
]

PROFILES = [
    (f'{m}_am1', enhance_saturate, {**AM1, 'model': m}, enhance_identity)
    for m in MODELS
] + [
    (f'{m}_am0', enhance_saturate, {**AM0, 'model': m}, enhance_identity)
    for m in MODELS
]

# BRIA RMBG 2.0 profiles (separate server on port 7001)
BRIA_PROFILES = [
    ('bria2_none',     enhance_identity, {}, enhance_identity),
    ('bria2_saturate', enhance_saturate, {}, enhance_identity),
]

OUTPUT_DIR = Path.home() / 'Downloads' / 'rembg_test'


def preprocess(img: Image.Image, enhance_fn) -> bytes:
    if img.mode != 'RGB':
        img = img.convert('RGB')
    img = enhance_fn(img)
    buf = io.BytesIO()
    img.save(buf, 'JPEG', quality=95)
    buf.seek(0)
    return buf.read()


def test_profile(label: str, enhance_fn, extra_params: dict, image_path: Path,
                 color_fn=None, url: str = None) -> tuple:
    """
    color_fn: if set, apply it to the *original* image and use that as the RGB source,
              while the alpha mask comes from the rembg result of enhance_fn.
              This lets saturate improve edge detection without affecting final colors.
    url: override server URL (default: BASE_URL for rembg, BRIA_URL for bria2 profiles)
    """
    out_dir = OUTPUT_DIR / label
    out_dir.mkdir(parents=True, exist_ok=True)
    out_path = out_dir / (image_path.stem + '.webp')

    try:
        img = Image.open(image_path)
        img_data = preprocess(img, enhance_fn)

        target_url = url or BASE_URL
        params = {**REMBG_PARAMS, **extra_params}
        response = requests.post(
            target_url,
            files={'file': (image_path.name, img_data, 'image/jpeg')},
            params=params,
            timeout=120,
        )
        response.raise_for_status()

        result = Image.open(io.BytesIO(response.content)).convert('RGBA')

        if color_fn is not None:
            # Extract mask from rembg result, apply to color-corrected original
            alpha = result.split()[3]
            color_img = color_fn(img.convert('RGB'))
            color_rgba = color_img.convert('RGBA')
            color_rgba.putalpha(alpha)
            result = color_rgba

        result.save(out_path, 'webp', quality=92)
        return (label, str(out_path), None)

    except Exception as e:
        return (label, None, str(e))


def main():
    if len(sys.argv) < 2:
        print("Usage: python test_rembg_models.py <image> [image2 ...]")
        sys.exit(1)

    images = [Path(p) for p in sys.argv[1:]]
    for img in images:
        if not img.exists():
            print(f"ERROR: File not found: {img}")
            sys.exit(1)

    all_profiles = (
        [(label, fn, extra, cfn, BASE_URL) for label, fn, extra, cfn in PROFILES] +
        [(label, fn, extra, cfn, BRIA_URL) for label, fn, extra, cfn in BRIA_PROFILES]
    )
    print(f"Testing {len(all_profiles)} profiles × {len(images)} image(s) in parallel...")
    print(f"rembg: {BASE_URL}  |  bria2: {BRIA_URL}\n")

    tasks = [(label, fn, extra, cfn, url, img) for label, fn, extra, cfn, url in all_profiles for img in images]

    with ThreadPoolExecutor(max_workers=len(tasks)) as executor:
        futures = {
            executor.submit(test_profile, label, fn, extra, img, cfn, url): (label, img)
            for label, fn, extra, cfn, url, img in tasks
        }

        for future in as_completed(futures):
            label, img = futures[future]
            result_label, out_path, error = future.result()
            if error:
                print(f"FAIL  {result_label:25s} {img.name} → {error}", flush=True)
            else:
                print(f"OK    {result_label:25s} {img.name} → {out_path}", flush=True)

    print(f"\nResults saved in: {OUTPUT_DIR}/")
    import subprocess
    subprocess.run(['open', str(OUTPUT_DIR)])


if __name__ == '__main__':
    main()
