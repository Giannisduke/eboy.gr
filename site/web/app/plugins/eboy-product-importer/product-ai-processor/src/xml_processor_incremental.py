"""
Incremental Processing Methods for XMLProcessor
"""

import hashlib
import logging
from lxml import etree
from pathlib import Path
from typing import Dict, Optional

logger = logging.getLogger(__name__)


def calculate_product_hash(product_data: Dict) -> str:
    """
    Calculate hash for a product based on key fields that affect AI processing.

    Args:
        product_data: Product data dictionary

    Returns:
        MD5 hash string
    """
    # Fields that affect AI output
    hash_fields = [
        str(product_data.get('name', '')),
        str(product_data.get('description', '')),
        str(product_data.get('category', '')),
        str(product_data.get('price', '')),
        str(product_data.get('manufacturer', ''))
    ]

    # Concatenate and hash
    combined = '|'.join(hash_fields)
    return hashlib.md5(combined.encode('utf-8')).hexdigest()


def load_existing_enhanced_products(enhanced_xml_path: Path, supplier: str) -> Dict[str, Dict]:
    """
    Load existing enhanced products from XML file.

    Args:
        enhanced_xml_path: Path to existing enhanced XML
        supplier: Supplier name for detection

    Returns:
        Dictionary mapping SKU -> {hash, element, data}
    """
    if not enhanced_xml_path.exists():
        logger.info("No existing enhanced XML found")
        return {}

    logger.info(f"Loading existing enhanced XML: {enhanced_xml_path}")

    try:
        tree = etree.parse(str(enhanced_xml_path))
        root = tree.getroot()

        # Find product elements based on supplier format (case-insensitive)
        products = root.xpath('.//product | .//Product')

        existing = {}
        for product_elem in products:
            # Extract SKU - try different field names based on supplier
            sku_elem = product_elem.find('.//SKU')  # estiahomeart
            if sku_elem is None:
                sku_elem = product_elem.find('.//model')  # pakoworld, b2bmarkt
            if sku_elem is None:
                sku_elem = product_elem.find('.//sku')  # generic

            if sku_elem is None or sku_elem.text is None:
                continue

            sku = sku_elem.text.strip()

            # Extract hash if it exists (we'll add this field)
            hash_elem = product_elem.find('.//original_hash')
            original_hash = hash_elem.text if hash_elem is not None else None

            # Store element and data
            existing[sku] = {
                'hash': original_hash,
                'element': product_elem,
                'enhanced': True  # Mark as already enhanced
            }

        logger.info(f"Loaded {len(existing)} existing enhanced products")
        return existing

    except Exception as e:
        logger.error(f"Failed to load existing enhanced XML: {str(e)}")
        return {}


def should_process_product(product_data: Dict, existing_products: Dict, force_reprocess: bool = False) -> bool:
    """
    Determine if a product should be processed based on changes.

    Args:
        product_data: Current product data from original XML
        existing_products: Dictionary of existing enhanced products
        force_reprocess: Force reprocessing regardless of changes

    Returns:
        True if product should be processed, False to skip
    """
    if force_reprocess:
        return True

    sku = product_data.get('sku')
    if not sku:
        return True  # Process if no SKU

    # New product - must process
    if sku not in existing_products:
        logger.debug(f"Product {sku}: NEW - will process")
        return True

    # Calculate current hash
    current_hash = calculate_product_hash(product_data)

    # Compare with existing hash
    existing_hash = existing_products[sku].get('hash')

    if existing_hash is None:
        # No hash stored - assume changed
        logger.debug(f"Product {sku}: No hash stored - will process")
        return True

    if current_hash != existing_hash:
        logger.info(f"Product {sku}: CHANGED (hash mismatch) - will process")
        return True

    logger.debug(f"Product {sku}: UNCHANGED - will skip AI processing")
    return False
