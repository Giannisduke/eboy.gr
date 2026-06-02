"""
Image Optimizer
Downloads and optimizes product images (resize, compress, WebP conversion).
Supports white background removal with transparent WebP output.
"""

import logging
import requests
from pathlib import Path
from typing import Optional, List, Dict
from PIL import Image
import io
from concurrent.futures import ThreadPoolExecutor, as_completed

logger = logging.getLogger(__name__)


class ImageOptimizer:
    """Handles image download and optimization"""

    def __init__(
        self,
        output_dir: Path,
        max_concurrent: int = 5,
        remove_bg: bool = False,
        bg_threshold: int = 230,
    ):
        self.output_dir = Path(output_dir)
        self.max_concurrent = max_concurrent
        self.remove_bg = remove_bg
        self.bg_threshold = bg_threshold

        # Image sizes to generate
        self.sizes = {
            'full': (1200, 1200),
            'large': (800, 800),
            'medium': (300, 300),
            'thumbnail': (150, 150)
        }

        # Quality settings
        self.jpeg_quality = 85
        self.webp_quality = 90  # Slightly higher for transparent WebP

        # Ensure output directory exists
        self.output_dir.mkdir(parents=True, exist_ok=True)

    def process_product_images(self, product_data: Dict) -> Dict[str, List[str]]:
        """
        Process all images for a product.

        Returns:
            Dict with processed image paths: {'main': path, 'gallery': [paths]}
        """
        sku = product_data.get('sku', 'unknown')
        main_image_url = product_data.get('main_image')
        gallery_urls = product_data.get('images', [])
        supplier = product_data.get('supplier', 'unknown')

        # Create product-specific directory
        product_dir = self.output_dir / supplier / sku
        product_dir.mkdir(parents=True, exist_ok=True)

        result = {
            'main': None,
            'gallery': []
        }

        # Process main image
        if main_image_url:
            main_path = self._process_single_image(main_image_url, product_dir, 'main')
            if main_path:
                result['main'] = str(main_path)

        # Process gallery images in parallel
        if gallery_urls:
            with ThreadPoolExecutor(max_workers=self.max_concurrent) as executor:
                futures = {}
                for idx, url in enumerate(gallery_urls[:10]):  # Limit to 10 images
                    future = executor.submit(
                        self._process_single_image,
                        url,
                        product_dir,
                        f'gallery_{idx}'
                    )
                    futures[future] = url

                for future in as_completed(futures):
                    try:
                        path = future.result()
                        if path:
                            result['gallery'].append(str(path))
                    except Exception as e:
                        logger.error(f"Error processing image {futures[future]}: {str(e)}")

        logger.info(f"Processed {len(result['gallery']) + (1 if result['main'] else 0)} images for SKU {sku}")
        return result

    def _detect_white_background(self, img: Image.Image) -> bool:
        """
        Detect if an image has a predominantly white border/background.

        Samples all border pixels and checks if >= 80% of them are near-white.

        Args:
            img: PIL Image (any mode — will be read as RGBA internally)

        Returns:
            True if the image likely has a white background
        """
        import numpy as np
        rgba = img.convert('RGBA')
        data = np.array(rgba, dtype=np.uint8)
        h, w = data.shape[:2]

        # Collect border pixel RGB values
        top    = data[0, :, :3]
        bottom = data[h - 1, :, :3]
        left   = data[1:h - 1, 0, :3]
        right  = data[1:h - 1, w - 1, :3]

        border = np.concatenate([top, bottom, left, right], axis=0)

        # A pixel is "white" when all RGB channels are >= threshold
        white_count = np.sum(np.all(border >= self.bg_threshold, axis=1))
        white_fraction = white_count / max(len(border), 1)

        logger.debug(f"White background detection: {white_fraction:.2%} of border pixels are white")
        return white_fraction >= 0.80

    def _remove_white_background(self, img: Image.Image) -> Image.Image:
        """
        Remove white background using BFS flood-fill from all border pixels.

        Only pixels that are:
        - near-white (all RGB >= bg_threshold), AND
        - reachable from the image border through a path of near-white pixels
        are made transparent.

        Returns:
            RGBA PIL Image with background set to transparent.
        """
        import numpy as np
        from collections import deque
        rgba = img.convert('RGBA')
        data = np.array(rgba, dtype=np.uint8)
        h, w = data.shape[:2]

        # Pre-compute white mask (vectorized — no Python loop)
        white_mask = np.all(data[:, :, :3] >= self.bg_threshold, axis=2)

        visited = np.zeros((h, w), dtype=bool)
        bg_mask = np.zeros((h, w), dtype=bool)
        queue = deque()

        # Seed queue with ALL border pixels and mark as visited
        for x in range(w):
            if not visited[0, x]:
                visited[0, x] = True
                queue.append((0, x))
            if not visited[h - 1, x]:
                visited[h - 1, x] = True
                queue.append((h - 1, x))
        for y in range(1, h - 1):
            if not visited[y, 0]:
                visited[y, 0] = True
                queue.append((y, 0))
            if not visited[y, w - 1]:
                visited[y, w - 1] = True
                queue.append((y, w - 1))

        # BFS: expand only through near-white pixels
        while queue:
            y, x = queue.popleft()
            if not white_mask[y, x]:
                continue  # Not white — stop expansion here

            bg_mask[y, x] = True

            for dy, dx in ((-1, 0), (1, 0), (0, -1), (0, 1)):
                ny, nx = y + dy, x + dx
                if 0 <= ny < h and 0 <= nx < w and not visited[ny, nx]:
                    visited[ny, nx] = True
                    queue.append((ny, nx))

        # Apply transparency to detected background
        result_data = data.copy()
        result_data[bg_mask, 3] = 0

        transparent_pixels = int(np.sum(bg_mask))
        logger.debug(f"Background removal: {transparent_pixels} pixels made transparent")

        return Image.fromarray(result_data, 'RGBA')

    def _process_single_image(
        self, url: str, output_dir: Path, name_prefix: str
    ) -> Optional[Path]:
        """
        Download and process a single image.

        When remove_bg is enabled:
        - Detects white backgrounds and removes them
        - WebP output: saved as RGBA (transparent background)
        - JPEG output: composited on white (JPEG has no alpha support)
        - Returns the large WebP path (preserves transparency)

        When remove_bg is disabled (default):
        - Converts all formats to RGB (white fill for any existing transparency)
        - Returns the large JPEG path

        Returns:
            Path to the primary processed image (large size) or None if failed.
        """
        try:
            # ── Download ──────────────────────────────────────────────────────
            logger.debug(f"Downloading image: {url}")
            response = requests.get(url, timeout=30, stream=True)
            response.raise_for_status()

            image_data = io.BytesIO(response.content)
            img = Image.open(image_data)
            img.load()  # Ensure data is fully read before BytesIO goes out of scope

            # Normalize palette images early
            if img.mode == 'P':
                img = img.convert('RGBA')

            # ── Background removal ────────────────────────────────────────────
            bg_removed = False

            if self.remove_bg:
                import numpy as np
                img_rgba = img.convert('RGBA')
                processed = self._remove_white_background(img_rgba)
                # BFS is self-limiting: if no white edges exist nothing changes.
                # Check if any pixels were actually made transparent.
                if np.any(np.array(processed)[:, :, 3] == 0):
                    img = processed
                    bg_removed = True
                    logger.info(f"Background removed: {url}")
                else:
                    logger.info(f"No white background found: {url}")
                    img = img_rgba  # Keep RGBA, composited to RGB below

            # ── Convert to RGB when NOT removing background ───────────────────
            if not bg_removed:
                if img.mode in ('RGBA', 'LA'):
                    background = Image.new('RGB', img.size, (255, 255, 255))
                    alpha = img.split()[-1] if img.mode == 'RGBA' else None
                    background.paste(img, mask=alpha)
                    img = background
                elif img.mode != 'RGB':
                    img = img.convert('RGB')

            # ── Validate minimum dimensions ───────────────────────────────────
            if img.width < 200 or img.height < 200:
                logger.warning(f"Image too small: {img.width}x{img.height} — skipping")
                return None

            # ── Generate sizes ────────────────────────────────────────────────
            for size_name, max_size in self.sizes.items():
                resized_img = self._resize_image(img, max_size)

                webp_path = output_dir / f"{name_prefix}_{size_name}.webp"
                jpeg_path = output_dir / f"{name_prefix}_{size_name}.jpg"

                if bg_removed:
                    # WebP: preserve RGBA transparency
                    resized_img.save(
                        webp_path,
                        'WEBP',
                        quality=self.webp_quality,
                        method=6,
                    )

                    # JPEG: composite transparent pixels on white
                    jpeg_base = Image.new('RGB', resized_img.size, (255, 255, 255))
                    jpeg_base.paste(resized_img, mask=resized_img.split()[3])
                    jpeg_base.save(
                        jpeg_path,
                        'JPEG',
                        quality=self.jpeg_quality,
                        optimize=True,
                        progressive=True,
                    )
                else:
                    # JPEG (RGB, no transparency)
                    resized_img.save(
                        jpeg_path,
                        'JPEG',
                        quality=self.jpeg_quality,
                        optimize=True,
                        progressive=True,
                    )

                    # WebP (RGB)
                    resized_img.save(
                        webp_path,
                        'WEBP',
                        quality=self.webp_quality,
                        method=6,
                    )

            # ── Return primary path ───────────────────────────────────────────
            # When bg was removed, prefer the transparent WebP as the canonical path
            if bg_removed:
                primary_path = output_dir / f"{name_prefix}_large.webp"
            else:
                primary_path = output_dir / f"{name_prefix}_large.jpg"

            logger.debug(f"Saved image: {primary_path}")
            return primary_path

        except requests.RequestException as e:
            logger.error(f"Failed to download image {url}: {str(e)}")
            return None
        except Exception as e:
            logger.error(f"Failed to process image {url}: {str(e)}")
            return None

    def _resize_image(self, img: Image.Image, max_size: tuple) -> Image.Image:
        """
        Resize image while maintaining aspect ratio.
        Uses thumbnail (in-place shrink) — never enlarges.
        """
        img.thumbnail(max_size, Image.Resampling.LANCZOS)
        return img

    def download_single_image(self, url: str, output_path: Path) -> bool:
        """
        Simple download for a single image (used for testing).
        """
        try:
            response = requests.get(url, timeout=30)
            response.raise_for_status()

            img = Image.open(io.BytesIO(response.content))

            if img.mode != 'RGB':
                if img.mode in ('RGBA', 'LA'):
                    background = Image.new('RGB', img.size, (255, 255, 255))
                    background.paste(img, mask=img.split()[-1])
                    img = background
                else:
                    img = img.convert('RGB')

            if img.width > 1200 or img.height > 1200:
                img.thumbnail((1200, 1200), Image.Resampling.LANCZOS)

            output_path.parent.mkdir(parents=True, exist_ok=True)
            img.save(output_path, 'JPEG', quality=self.jpeg_quality, optimize=True)

            logger.info(f"Downloaded and optimized image to {output_path}")
            return True

        except Exception as e:
            logger.error(f"Failed to download image: {str(e)}")
            return False
