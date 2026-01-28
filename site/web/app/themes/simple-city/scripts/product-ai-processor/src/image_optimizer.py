"""
Image Optimizer
Downloads and optimizes product images (resize, compress, WebP conversion).
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

    def __init__(self, output_dir: Path, max_concurrent: int = 5):
        self.output_dir = Path(output_dir)
        self.max_concurrent = max_concurrent

        # Image sizes to generate
        self.sizes = {
            'full': (1200, 1200),
            'large': (800, 800),
            'medium': (300, 300),
            'thumbnail': (150, 150)
        }

        # Quality settings
        self.jpeg_quality = 85
        self.webp_quality = 85

        # Ensure output directory exists
        self.output_dir.mkdir(parents=True, exist_ok=True)

    def process_product_images(self, product_data: Dict) -> Dict[str, List[str]]:
        """
        Process all images for a product

        Args:
            product_data: Dict with 'sku', 'main_image', and 'images' (list of URLs)

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

    def _process_single_image(self, url: str, output_dir: Path, name_prefix: str) -> Optional[Path]:
        """
        Download and process a single image

        Returns:
            Path to the main processed image (large size) or None if failed
        """
        try:
            # Download image
            logger.debug(f"Downloading image: {url}")
            response = requests.get(url, timeout=30, stream=True)
            response.raise_for_status()

            # Load image
            image_data = io.BytesIO(response.content)
            img = Image.open(image_data)

            # Convert RGBA to RGB if necessary
            if img.mode in ('RGBA', 'LA', 'P'):
                background = Image.new('RGB', img.size, (255, 255, 255))
                if img.mode == 'P':
                    img = img.convert('RGBA')
                background.paste(img, mask=img.split()[-1] if img.mode == 'RGBA' else None)
                img = background
            elif img.mode != 'RGB':
                img = img.convert('RGB')

            # Validate minimum dimensions
            if img.width < 200 or img.height < 200:
                logger.warning(f"Image too small: {img.width}x{img.height}")
                return None

            # Generate different sizes
            saved_paths = []
            for size_name, max_size in self.sizes.items():
                resized_img = self._resize_image(img, max_size)

                # Save JPEG
                jpeg_path = output_dir / f"{name_prefix}_{size_name}.jpg"
                resized_img.save(
                    jpeg_path,
                    'JPEG',
                    quality=self.jpeg_quality,
                    optimize=True,
                    progressive=True
                )
                saved_paths.append(jpeg_path)

                # Save WebP
                webp_path = output_dir / f"{name_prefix}_{size_name}.webp"
                resized_img.save(
                    webp_path,
                    'WEBP',
                    quality=self.webp_quality,
                    method=6  # Best compression
                )

            # Return the large size JPEG path
            large_path = output_dir / f"{name_prefix}_large.jpg"
            logger.debug(f"Saved image: {large_path}")
            return large_path

        except requests.RequestException as e:
            logger.error(f"Failed to download image {url}: {str(e)}")
            return None
        except Exception as e:
            logger.error(f"Failed to process image {url}: {str(e)}")
            return None

    def _resize_image(self, img: Image.Image, max_size: tuple) -> Image.Image:
        """
        Resize image while maintaining aspect ratio

        Args:
            img: PIL Image object
            max_size: (max_width, max_height)

        Returns:
            Resized PIL Image
        """
        # Calculate new size maintaining aspect ratio
        img.thumbnail(max_size, Image.Resampling.LANCZOS)
        return img

    def download_single_image(self, url: str, output_path: Path) -> bool:
        """
        Simple download for a single image (used for testing)

        Args:
            url: Image URL
            output_path: Where to save

        Returns:
            True if successful
        """
        try:
            response = requests.get(url, timeout=30)
            response.raise_for_status()

            img = Image.open(io.BytesIO(response.content))

            # Convert to RGB if needed
            if img.mode != 'RGB':
                if img.mode in ('RGBA', 'LA'):
                    background = Image.new('RGB', img.size, (255, 255, 255))
                    background.paste(img, mask=img.split()[-1])
                    img = background
                else:
                    img = img.convert('RGB')

            # Resize if too large
            if img.width > 1200 or img.height > 1200:
                img.thumbnail((1200, 1200), Image.Resampling.LANCZOS)

            # Save
            output_path.parent.mkdir(parents=True, exist_ok=True)
            img.save(output_path, 'JPEG', quality=self.jpeg_quality, optimize=True)

            logger.info(f"Downloaded and optimized image to {output_path}")
            return True

        except Exception as e:
            logger.error(f"Failed to download image: {str(e)}")
            return False
