import sys, io, requests
sys.path.insert(0, 'src')
from image_optimizer import ImageOptimizer
from pathlib import Path
import numpy as np
from PIL import Image

opt = ImageOptimizer(Path('/tmp/bg-test'), remove_bg=True, bg_threshold=230)
print('remove_bg:', opt.remove_bg)
print('bg_threshold:', opt.bg_threshold)

url = 'https://libertab2b.gr/media/catalog/product/0/3/035239-QhK7E-first-_1.jpg'
print('Downloading...')
r = requests.get(url, timeout=30)
img = Image.open(io.BytesIO(r.content)).convert('RGBA')
print('Image mode:', img.mode, '| Size:', img.size)

data = np.array(img)
print('Corner pixels RGB:')
print('  TL:', data[0,0,:3], ' TR:', data[0,-1,:3])
print('  BL:', data[-1,0,:3], ' BR:', data[-1,-1,:3])

result = opt._remove_white_background(img)
arr = np.array(result)
t = int(np.sum(arr[:,:,3] == 0))
total = img.width * img.height
print(f'Transparent pixels: {t}/{total} ({t/total:.1%})')
