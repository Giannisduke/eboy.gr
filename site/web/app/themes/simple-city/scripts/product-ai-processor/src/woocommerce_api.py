"""
WooCommerce REST API Client
Handles direct product import to WooCommerce after AI enhancement
"""

import logging
import requests
from typing import Dict, Any, Optional
from requests.auth import HTTPBasicAuth

logger = logging.getLogger(__name__)


class WooCommerceAPI:
    """WooCommerce REST API client for product import"""

    def __init__(self, base_url: str, consumer_key: str, consumer_secret: str):
        """
        Initialize WooCommerce API client

        Args:
            base_url: WordPress site URL (e.g., https://eboy.gr)
            consumer_key: WooCommerce API consumer key
            consumer_secret: WooCommerce API consumer secret
        """
        self.base_url = base_url.rstrip('/')
        self.api_url = f"{self.base_url}/wp-json/wc/v3"
        self.auth = HTTPBasicAuth(consumer_key, consumer_secret)
        self.headers = {
            'Content-Type': 'application/json',
            'User-Agent': 'AI-Product-Processor/1.0'
        }

    def import_product(self, product_data: Dict[str, Any]) -> Optional[Dict]:
        """
        Import or update a product in WooCommerce

        Args:
            product_data: Enhanced product data with AI fields

        Returns:
            WooCommerce product object or None if failed
        """
        try:
            sku = product_data.get('sku')

            # Check if product already exists by SKU
            existing_product = self._find_product_by_sku(sku)

            # Prepare WooCommerce product data
            woo_data = self._prepare_product_data(product_data)

            if existing_product:
                # Update existing product
                product_id = existing_product['id']
                logger.info(f"Updating existing product: {sku} (ID: {product_id})")
                response = self._update_product(product_id, woo_data)
            else:
                # Create new product
                logger.info(f"Creating new product: {sku}")
                response = self._create_product(woo_data)

            if response:
                logger.info(f"✅ Product {sku} imported successfully (ID: {response.get('id')})")
                return response
            else:
                logger.error(f"❌ Failed to import product {sku}")
                return None

        except Exception as e:
            logger.error(f"Error importing product {product_data.get('sku')}: {e}")
            return None

    def _find_product_by_sku(self, sku: str) -> Optional[Dict]:
        """Find product by SKU"""
        try:
            url = f"{self.api_url}/products"
            params = {'sku': sku}

            response = requests.get(
                url,
                auth=self.auth,
                headers=self.headers,
                params=params,
                timeout=30
            )

            if response.status_code == 200:
                products = response.json()
                return products[0] if products else None
            else:
                logger.warning(f"Failed to search for SKU {sku}: {response.status_code}")
                return None

        except Exception as e:
            logger.error(f"Error searching for product {sku}: {e}")
            return None

    def _create_product(self, product_data: Dict) -> Optional[Dict]:
        """Create new product"""
        try:
            url = f"{self.api_url}/products"

            response = requests.post(
                url,
                auth=self.auth,
                headers=self.headers,
                json=product_data,
                timeout=30
            )

            if response.status_code == 201:
                return response.json()
            else:
                logger.error(f"Failed to create product: {response.status_code} - {response.text}")
                return None

        except Exception as e:
            logger.error(f"Error creating product: {e}")
            return None

    def _update_product(self, product_id: int, product_data: Dict) -> Optional[Dict]:
        """Update existing product"""
        try:
            url = f"{self.api_url}/products/{product_id}"

            response = requests.put(
                url,
                auth=self.auth,
                headers=self.headers,
                json=product_data,
                timeout=30
            )

            if response.status_code == 200:
                return response.json()
            else:
                logger.error(f"Failed to update product {product_id}: {response.status_code} - {response.text}")
                return None

        except Exception as e:
            logger.error(f"Error updating product {product_id}: {e}")
            return None

    def _prepare_product_data(self, product_data: Dict[str, Any]) -> Dict:
        """
        Convert enhanced product data to WooCommerce format

        Args:
            product_data: Enhanced product data from AI processor

        Returns:
            WooCommerce API compatible product data
        """
        # Use optimized title or fallback to original
        name = product_data.get('optimized_title') or product_data.get('name', '')

        # Parse price
        price = str(product_data.get('price', '0')).replace(',', '.')
        try:
            price = float(price)
        except:
            price = 0.0

        # Get stock quantity
        stock_quantity = product_data.get('stock_quantity', 0)
        try:
            stock_quantity = int(stock_quantity)
        except:
            stock_quantity = 0

        # Prepare images
        images = []
        if product_data.get('main_image'):
            images.append({'src': product_data['main_image']})

        # Add gallery images
        for img_url in product_data.get('images', [])[:5]:  # Limit to 5 images
            if img_url and img_url != product_data.get('main_image'):
                images.append({'src': img_url})

        # Prepare categories
        categories = []
        woo_category = product_data.get('woo_category')
        if woo_category:
            categories.append({'name': woo_category})

        # Prepare tags
        tags = []
        product_tags = product_data.get('tags', [])
        if isinstance(product_tags, str):
            product_tags = [t.strip() for t in product_tags.split(',')]

        for tag in product_tags[:10]:  # Limit to 10 tags
            if tag:
                tags.append({'name': tag})

        # Build WooCommerce product data
        woo_data = {
            'name': name,
            'type': 'simple',
            'sku': product_data.get('sku', ''),
            'regular_price': str(price),
            'description': product_data.get('description', ''),
            'short_description': product_data.get('short_description', ''),
            'categories': categories,
            'tags': tags,
            'images': images,
            'manage_stock': True,
            'stock_quantity': stock_quantity,
            'stock_status': 'instock' if stock_quantity > 0 else 'outofstock',
        }

        # Add metadata for AI processing tracking
        woo_data['meta_data'] = [
            {'key': '_ai_processed', 'value': 'yes'},
            {'key': '_ai_processor_version', 'value': '1.0'},
            {'key': '_supplier', 'value': product_data.get('supplier', '')},
            {'key': '_original_title', 'value': product_data.get('name', '')},
        ]

        if product_data.get('category_confidence'):
            woo_data['meta_data'].append({
                'key': '_category_confidence',
                'value': str(product_data['category_confidence'])
            })

        return woo_data
