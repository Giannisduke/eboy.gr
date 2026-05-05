"""
XML Processor
Main orchestrator that processes XML files and coordinates all AI enhancements.
"""

import logging
from pathlib import Path
from typing import Dict, List, Optional
from lxml import etree
import time
import json
import hashlib
from concurrent.futures import ThreadPoolExecutor, as_completed

from .ai_client import OllamaClient
from .title_optimizer import TitleOptimizer
from .description_enhancer import DescriptionEnhancer
from .tech_specs_generator import TechSpecsGenerator
from .tag_generator import TagGenerator
from .category_mapper import CategoryMapper
from .image_optimizer import ImageOptimizer
from .translator import ProductTranslator
from .woocommerce_api import WooCommerceAPI
from .xml_processor_incremental import (
    calculate_product_hash,
    load_existing_enhanced_products,
    should_process_product
)

logger = logging.getLogger(__name__)


class XMLProcessor:
    """Processes XML product feeds with AI enhancements"""

    def __init__(self, config: Dict):
        self.config = config
        self.ai_client = OllamaClient(
            host=config.get('ollama_host', '127.0.0.1'),
            port=config.get('ollama_port', 11434),
            model=config.get('ai_model', 'mistral:7b-instruct-q4_K_M')
        )

        # Load configurations
        self.prompts_config = config['prompts']
        self.categories_config = config['categories']

        # Check if we're in English mode (need translation)
        self.language = config.get('language', 'el')
        self.translator = None

        if self.language == 'en' and config.get('translation_model'):
            # Create separate client for translation
            translation_client = OllamaClient(
                host=config.get('ollama_host', '127.0.0.1'),
                port=config.get('ollama_port', 11434),
                model=config.get('translation_model')
            )
            self.translator = ProductTranslator(
                translation_client,
                prompts_file='config/prompts_translation.yaml'
            )
            logger.info(f"English mode enabled - will translate to Greek using {config.get('translation_model')}")

        # Initialize WooCommerce API client (optional)
        self.woo_api = None
        if config.get('woo_auto_import', False):
            woo_url = config.get('woo_url')
            woo_key = config.get('woo_consumer_key')
            woo_secret = config.get('woo_consumer_secret')

            if woo_url and woo_key and woo_secret:
                self.woo_api = WooCommerceAPI(woo_url, woo_key, woo_secret)
                logger.info(f"✅ WooCommerce auto-import enabled: {woo_url}")
            else:
                logger.warning("⚠️  WooCommerce auto-import enabled but credentials missing")

        # Initialize processors
        self.title_optimizer = TitleOptimizer(self.ai_client, self.prompts_config)
        self.description_enhancer = DescriptionEnhancer(self.ai_client, self.prompts_config)
        self.tech_specs_generator = TechSpecsGenerator(self.ai_client, self.prompts_config)
        self.tag_generator = TagGenerator(self.ai_client, self.prompts_config)
        self.category_mapper = CategoryMapper(self.ai_client, self.prompts_config, self.categories_config)
        skip_images = config.get('skip_images', False)
        self.image_optimizer = None if skip_images else ImageOptimizer(
            output_dir=Path(config.get('image_output_dir', './output/images')),
            max_concurrent=config.get('max_concurrent_images', 5),
            remove_bg=config.get('remove_bg', False),
            bg_threshold=config.get('bg_threshold', 240),
        )

        # Processing stats
        self.stats = {
            'total': 0,
            'processed': 0,
            'failed': 0,
            'skipped': 0
        }

    def process_xml_file(self, xml_path: Path, output_path: Path, limit: Optional[int] = None, skip_images: bool = False, sku_filter: Optional[list] = None, extend_from_backup: bool = False) -> Dict:
        """
        Process an XML file and generate enhanced output

        Args:
            xml_path: Path to input XML file
            output_path: Path for output XML file
            limit: Optional limit on number of products to process
            skip_images: Whether to skip image processing (for faster testing)
            sku_filter: Optional list of SKUs to process (for incremental updates)
            extend_from_backup: Copy existing products from backup as-is, only process new ones

        Returns:
            Dict with processing statistics
        """
        logger.info(f"Processing XML file: {xml_path}")
        logger.info(f"Output will be saved to: {output_path}")

        # Detect supplier from filename
        supplier = self._detect_supplier(xml_path)
        logger.info(f"Detected supplier: {supplier}")

        # Parse XML
        try:
            tree = etree.parse(str(xml_path))
            root = tree.getroot()
        except Exception as e:
            logger.error(f"Failed to parse XML: {str(e)}")
            return {'error': str(e)}

        # Find product elements
        products = self._find_product_elements(root, supplier)
        logger.info(f"Found {len(products)} products in XML")

        # Apply SKU filter if provided
        if sku_filter:
            filtered_products = []
            for product_elem in products:
                product_data = self._extract_product_data(product_elem, supplier)
                if product_data.get('sku') in sku_filter:
                    filtered_products.append(product_elem)
            products = filtered_products
            logger.info(f"SKU filter applied: {len(products)} products matched")

        if limit:
            products = products[:limit]
            logger.info(f"Processing limited to {limit} products")

        # Load existing enhanced products for incremental processing
        existing_enhanced = {}
        if extend_from_backup:
            # When extending from backup, copy existing products as-is (no hash comparison)
            backup_path = Path(str(output_path) + '.backup')
            if backup_path.exists():
                logger.info(f"Extend mode: Loading existing products from backup: {backup_path}")
                existing_enhanced = load_existing_enhanced_products(backup_path, supplier)
                logger.info(f"Loaded {len(existing_enhanced)} existing enhanced products (will copy as-is)")
            else:
                logger.warning(f"Extend mode requested but backup file not found: {backup_path}")
        else:
            # Normal incremental mode: use hash comparison
            backup_path = Path(str(output_path) + '.backup')
            if backup_path.exists():
                logger.info(f"Found backup file, loading for incremental processing: {backup_path}")
                existing_enhanced = load_existing_enhanced_products(backup_path, supplier)
            else:
                existing_enhanced = load_existing_enhanced_products(output_path, supplier)
            logger.info(f"Loaded {len(existing_enhanced)} existing enhanced products")

        # Progress tracking files go in xml_files/ (parent of enhanced/)
        progress_file = output_path.parent.parent / f"{supplier}-progress.json"
        ready_file = output_path.parent.parent / f"{supplier}-ready.json"

        # Create output XML structure immediately
        output_path.parent.mkdir(parents=True, exist_ok=True)

        # Initialize ready-for-import file
        ready_data = {
            'ready_skus': [],
            'total_ready': 0,
            'total_products': len(products)
        }
        try:
            with open(ready_file, 'w') as f:
                json.dump(ready_data, f)
        except Exception as e:
            logger.warning(f"Failed to create ready file: {str(e)}")

        # Write initial progress file AFTER resetting ready.json.
        # PHP polls this file and will not start importing until status != 'complete'.
        # This prevents PHP from reading stale ready.json/enhanced.xml from a previous run.
        run_started_at = time.time()
        try:
            with open(progress_file, 'w') as f:
                json.dump({
                    'started_at': run_started_at,
                    'current': 0,
                    'total': len(products),
                    'percent': 0,
                    'processed': 0,
                    'failed': 0,
                    'status': 'processing'
                }, f)
        except Exception as e:
            logger.warning(f"Failed to initialize progress file: {e}")

        # Build new tree with metadata
        # Determine product container tag based on supplier
        if supplier == 'estiahomeart' or supplier == 'b2bmarkt':
            products_container_tag = 'Products'  # PascalCase
        else:
            products_container_tag = 'products'  # lowercase

        # Check if root element IS the products container (b2bmarkt, estiah structure)
        # vs. root has a separate products child container (pakoworld structure)
        if root.tag.lower() == 'products':
            # Root IS the products container - keep it as root
            new_root = etree.Element(root.tag, attrib=root.attrib)

            # Copy only non-product metadata children (created, etc.)
            for child in root:
                # Skip Product/product elements - we'll add them later
                if child.tag.lower() != 'product':
                    # Create a shallow copy of the element (without its children)
                    new_elem = etree.Element(child.tag, attrib=child.attrib)
                    if child.text:
                        new_elem.text = child.text
                    if child.tail:
                        new_elem.tail = child.tail
                    new_root.append(new_elem)

            # Products container is the root itself
            products_elem = new_root
            new_tree = etree.ElementTree(new_root)
        else:
            # Root is NOT products container - need to find/create it
            new_root = etree.Element(root.tag, attrib=root.attrib)

            # Copy metadata elements but skip product container
            for child in root:
                if child.tag.lower() != 'products':
                    # Create a shallow copy of the element (without its children)
                    new_elem = etree.Element(child.tag, attrib=child.attrib)
                    if child.text:
                        new_elem.text = child.text
                    if child.tail:
                        new_elem.tail = child.tail
                    new_root.append(new_elem)

            # Add empty products container
            products_elem = etree.SubElement(new_root, products_container_tag)
            new_tree = etree.ElementTree(new_root)

        # Write initial empty XML
        new_tree.write(str(output_path), encoding='utf-8', xml_declaration=True, pretty_print=True)
        logger.info(f"Created initial XML file: {output_path}")

        # Process each product (concurrent: 2 at a time matching OLLAMA_NUM_PARALLEL)
        enhanced_products = []
        skipped_count = 0
        total = len(products)

        def process_one(args):
            """Process a single product with AI — runs in thread pool."""
            idx, product_elem = args
            product_data = self._extract_product_data(product_elem, supplier)
            sku = product_data.get('sku', 'unknown')

            # Extend mode: copy from backup, no AI needed
            if extend_from_backup and sku in existing_enhanced:
                return idx, product_data, existing_enhanced[sku]['element'], None, 'copied'

            # Incremental mode: skip if unchanged
            if not extend_from_backup and not should_process_product(product_data, existing_enhanced):
                return idx, product_data, existing_enhanced[sku]['element'], None, 'skipped'

            # AI enhancement
            enhanced_data = self._enhance_product(product_data, skip_images)
            if not extend_from_backup:
                enhanced_data['original_hash'] = calculate_product_hash(product_data)
            self._update_product_element(product_elem, enhanced_data)
            return idx, product_data, product_elem, enhanced_data, 'processed'

        # Build ordered results using thread pool (2 concurrent workers)
        results = [None] * total
        with ThreadPoolExecutor(max_workers=2) as executor:
            futures = {executor.submit(process_one, (idx, elem)): idx - 1
                       for idx, elem in enumerate(products, 1)}
            for future in as_completed(futures):
                pos = futures[future]
                try:
                    results[pos] = future.result()
                except Exception as e:
                    logger.error(f"Failed to process product at position {pos + 1}: {e}")
                    results[pos] = (pos + 1, {}, None, None, 'failed')

        # Write results to XML in order, update progress sequentially
        for pos, result in enumerate(results):
            if result is None:
                self.stats['failed'] += 1
                continue

            idx, product_data, product_elem, enhanced_data, status = result

            if status == 'failed' or product_elem is None:
                self.stats['failed'] += 1
            elif status in ('copied', 'skipped'):
                skipped_count += 1
                self.stats['skipped'] += 1
                products_elem.append(product_elem)
                self.stats['processed'] += 1
            else:
                products_elem.append(product_elem)
                if enhanced_data:
                    enhanced_products.append(enhanced_data)

                    if self.woo_api:
                        try:
                            woo_result = self.woo_api.import_product(enhanced_data)
                            if woo_result:
                                logger.info(f"✅ Product {product_data.get('sku')} imported (ID: {woo_result.get('id')})")
                        except Exception as e:
                            logger.error(f"❌ WooCommerce import error: {e}")

                self.stats['processed'] += 1

            # Write XML after each product (for realtime import)
            new_tree.write(str(output_path), encoding='utf-8', xml_declaration=True, pretty_print=True)

            # Mark SKU as ready
            try:
                with open(ready_file, 'r') as f:
                    ready_data = json.load(f)
                if product_data.get('sku'):
                    ready_data['ready_skus'].append(product_data['sku'])
                    ready_data['total_ready'] = len(ready_data['ready_skus'])
                with open(ready_file, 'w') as f:
                    json.dump(ready_data, f)
            except Exception as e:
                logger.warning(f"Failed to update ready file: {e}")

            # Update progress
            self.stats['total'] = total
            try:
                with open(progress_file, 'w') as f:
                    json.dump({
                        'started_at': run_started_at,
                        'current': pos + 1,
                        'total': total,
                        'percent': round(((pos + 1) / total) * 100, 1),
                        'processed': self.stats['processed'],
                        'failed': self.stats['failed'],
                        'status': 'processing'
                    }, f)
            except Exception as e:
                logger.warning(f"Failed to write progress file: {e}")

        logger.info(f"Enhanced XML completed: {output_path}")
        logger.info(f"Incremental processing stats: {skipped_count} products unchanged (skipped AI), "
                   f"{self.stats['processed']} products processed with AI")

        # Delete backup file if it exists (no longer needed after successful completion)
        backup_path = Path(str(output_path) + '.backup')
        if backup_path.exists():
            backup_path.unlink()
            logger.info(f"Deleted backup file: {backup_path}")

        # Mark as complete
        complete_data = {
            'started_at': run_started_at,
            'current': len(products),
            'total': len(products),
            'percent': 100,
            'processed': self.stats['processed'],
            'failed': self.stats['failed'],
            'skipped': skipped_count,
            'status': 'complete'
        }
        try:
            with open(progress_file, 'w') as f:
                json.dump(complete_data, f)
        except Exception as e:
            logger.warning(f"Failed to write final progress: {str(e)}")

        return self.stats

    def generate_category_mappings(self, xml_files: List[Path], output_file: Path):
        """
        Generate category mappings from XML files for review

        Args:
            xml_files: List of XML files to process
            output_file: Where to save the mappings YAML
        """
        logger.info(f"Generating category mappings from {len(xml_files)} XML files")

        for xml_path in xml_files:
            supplier = self._detect_supplier(xml_path)
            logger.info(f"Processing {xml_path.name} (supplier: {supplier})")

            try:
                tree = etree.parse(str(xml_path))
                root = tree.getroot()
                products = self._find_product_elements(root, supplier)

                # Sample every 10th product to get representative categories
                sample_products = products[::10][:50]  # Max 50 samples per supplier

                for product_elem in sample_products:
                    product_data = self._extract_product_data(product_elem, supplier)
                    category = product_data.get('category', '')
                    name = product_data.get('name', '')

                    if category:
                        # Map category (this will store it for review)
                        self.category_mapper.map_category(category, name)

            except Exception as e:
                logger.error(f"Error processing {xml_path}: {str(e)}")

        # Export all mappings
        self.category_mapper.export_mappings_for_review(output_file)
        logger.info(f"Category mappings exported to: {output_file}")

    def _detect_supplier(self, xml_path: Path) -> str:
        """Detect supplier from filename"""
        filename = xml_path.stem.lower()
        if 'pakoworld' in filename:
            return 'pakoworld'
        elif 'liberta' in filename:
            return 'libertab2b'
        elif 'b2bmarkt' in filename or 'b2b' in filename:
            return 'b2bmarkt'
        elif 'estiah' in filename or 'estia' in filename:
            return 'estiahomeart'
        else:
            return 'unknown'

    def _find_product_elements(self, root: etree.Element, supplier: str) -> List[etree.Element]:
        """Find product elements based on supplier XML structure"""
        if supplier == 'pakoworld':
            return root.findall('.//product')
        elif supplier == 'b2bmarkt':
            return root.findall('./Product')
        elif supplier == 'libertab2b':
            return root.findall('.//product')
        elif supplier == 'estiahomeart':
            return root.findall('.//Product')  # PascalCase for estiah
        else:
            # Try common patterns
            products = root.findall('.//product') or root.findall('.//Product')
            return products

    def _extract_product_data(self, elem: etree.Element, supplier: str) -> Dict:
        """Extract product data from XML element"""
        data = {'supplier': supplier}

        # Supplier-specific field extraction
        if supplier == 'estiahomeart':
            # Estiah uses PascalCase tags
            data['sku'] = self._get_text(elem, './/SKU')
            data['name'] = self._get_text(elem, './/Name')

            # Combine CategoryParent + Category (e.g., "Κουζίνα > Είδη Barbeque")
            cat_parent = self._get_text(elem, './/CategoryParent')
            cat = self._get_text(elem, './/Category')
            if cat_parent and cat:
                data['category'] = f"{cat_parent} > {cat}"
            elif cat_parent:
                data['category'] = cat_parent
            else:
                data['category'] = cat

            data['description'] = self._get_text(elem, './/Description') or ''
            data['price'] = self._get_text(elem, './/Price') or self._get_text(elem, './/PriceRetail')
            data['main_image'] = self._get_text(elem, './/imageurl')

            # No gallery images in estiah XML
            data['images'] = []

            # Get ProductSpecification elements as attributes
            specs = elem.findall('.//ProductSpecification')
            data['attributes'] = [spec.text.strip() for spec in specs if spec.text and spec.text.strip()]
        elif supplier == 'libertab2b':
            data['sku'] = self._get_text(elem, './/sku')
            data['name'] = self._get_text(elem, './/name')
            data['barcode'] = self._get_text(elem, './/barcode')

            # Liberta uses <categories><item> — read the first Greek category item
            cat_items = elem.findall('.//categories/item')
            data['category'] = cat_items[0].text.strip() if cat_items and cat_items[0].text else None

            data['description'] = self._get_text(elem, './/description') or ''
            data['price'] = self._get_text(elem, './/retail-price')
            data['main_image'] = self._get_text(elem, './/photo')

            # Gallery images are under <photos><item>
            photos = elem.findall('.//photos/item')
            data['images'] = [p.text.strip() for p in photos if p.text and p.text.strip()]

            # Keyed fields used by AI (tech_specs / description prompts)
            data['material'] = self._get_text(elem, './/material') or ''
            data['dimensions_text'] = self._get_text(elem, './/dimensions') or ''

            # Attributes from material, color, dimensions, comments
            attrs = []
            for field in ('material', 'color', 'dimensions', 'comments'):
                val = self._get_text(elem, f'.//{field}')
                if val:
                    attrs.append(val)
            data['attributes'] = attrs
        else:
            # Common fields for other suppliers
            data['sku'] = self._get_text(elem, './/model') or self._get_text(elem, './/ProductCode') or self._get_text(elem, './/sku')
            data['name'] = self._get_text(elem, './/name') or self._get_text(elem, './/Name')
            data['category'] = self._get_text(elem, './/category') or self._get_text(elem, './/Categories/Category[@level="1"]')
            data['description'] = self._get_text(elem, './/description') or self._get_text(elem, './/ExtendedDescription')
            data['price'] = self._get_text(elem, './/price') or self._get_text(elem, './/RetailCurrentPrice')
            data['main_image'] = self._get_text(elem, './/main_image') or self._get_text(elem, './/ImagesLocation/image[1]')

            # Get gallery images
            images = elem.findall('.//images/image') or elem.findall('.//ImagesLocation/image')
            data['images'] = [img.text.strip() for img in images if img.text and img.text.strip()]

            # Get attributes
            attrs = elem.findall('.//attributes/attribute') or elem.findall('.//Filters/Filter')
            data['attributes'] = [attr.text.strip() if hasattr(attr, 'text') and attr.text else etree.tostring(attr, encoding='unicode', method='text').strip() for attr in attrs]

        return data

    def _get_text(self, elem: etree.Element, xpath: str) -> Optional[str]:
        """Safely get text from XPath"""
        try:
            found = elem.find(xpath)
            if found is not None and found.text:
                return found.text.strip()
        except:
            pass
        return None

    def _enhance_product(self, product_data: Dict, skip_images: bool = False) -> Dict:
        """Apply all AI enhancements to a product"""
        enhanced = product_data.copy()

        # 1. Optimize title (with error handling)
        if product_data.get('name'):
            try:
                # Pass supplier to use supplier-specific prompt
                supplier = product_data.get('supplier')
                optimized_title = self.title_optimizer.optimize(
                    product_data['name'],
                    supplier=supplier
                )
                if optimized_title:
                    enhanced['optimized_title'] = optimized_title
            except Exception as e:
                logger.warning(f"Title optimization failed for {product_data.get('sku')}: {str(e)}")
                # Use original title as fallback
                enhanced['optimized_title'] = product_data['name']

        # 2. Map category (with error handling - don't let this crash the whole import)
        if product_data.get('category'):
            try:
                woo_category, confidence, subcats = self.category_mapper.map_category(
                    product_data['category'],
                    product_data.get('name', '')
                )
                enhanced['woo_category'] = woo_category
                enhanced['category_confidence'] = confidence
                enhanced['subcategories'] = subcats
            except Exception as e:
                logger.warning(f"Category mapping failed for {product_data.get('sku')}: {str(e)}")
                # Use a safe default category
                enhanced['woo_category'] = 'Οργάνωση σπιτιού'
                enhanced['category_confidence'] = 0.5
                enhanced['subcategories'] = []

        # 3. Generate tags (with error handling)
        try:
            tag_data = {
                'title': enhanced.get('optimized_title', product_data.get('name', '')),
                'supplier_category': product_data.get('category', ''),
                'woo_category': enhanced.get('woo_category', ''),
                'attributes': product_data.get('attributes', []),
                'description_summary': product_data.get('description', '')[:200]
            }
            tags = self.tag_generator.generate_tags(tag_data)
            enhanced['tags'] = tags
        except Exception as e:
            logger.warning(f"Tag generation failed for {product_data.get('sku')}: {str(e)}")
            # Use basic tags as fallback
            enhanced['tags'] = []

        # 4. Enhance description
        try:
            desc_data = {
                'title': enhanced.get('optimized_title', product_data.get('name', '')),
                'supplier_category': product_data.get('category', ''),
                'material': product_data.get('material', ''),
                'dimensions': product_data.get('dimensions_text', ''),
                'features': product_data.get('attributes', []),
                'original_description': product_data.get('description', ''),
            }
            ai_desc = self.description_enhancer.enhance(desc_data)
            if ai_desc:
                enhanced['enhanced_description'] = ai_desc
        except Exception as e:
            logger.warning(f"Description enhancement failed for {product_data.get('sku')}: {str(e)}")

        # 4b. Generate tech specs
        try:
            tech_data = {
                'title': enhanced.get('optimized_title', product_data.get('name', '')),
                'supplier_category': product_data.get('category', ''),
                'material': product_data.get('material', ''),
                'dimensions': product_data.get('dimensions_text', ''),
                'attributes': product_data.get('attributes', []),
                'original_description': product_data.get('description', ''),
            }
            tech_specs = self.tech_specs_generator.generate(tech_data)
            if tech_specs:
                enhanced['tech_specs'] = tech_specs
        except Exception as e:
            logger.warning(f"Tech specs generation failed for {product_data.get('sku')}: {str(e)}")

        # 5. Translate to Greek if in English mode
        if self.translator and self.language == 'en':
            try:
                logger.info(f"Translating product to Greek: {enhanced.get('optimized_title', '')[:50]}")

                # Translate optimized title
                if enhanced.get('optimized_title'):
                    greek_title = self.translator.translate_title(enhanced['optimized_title'])
                    enhanced['optimized_title'] = greek_title

                # Translate tags
                if enhanced.get('tags'):
                    # Tags are a list, convert to comma-separated string for translation
                    english_tags = ', '.join(enhanced['tags'])
                    greek_tags = self.translator.translate_tags(english_tags)
                    enhanced['tags'] = [tag.strip() for tag in greek_tags.split(',')]

                # Translate category (use direct mapping from translator)
                if enhanced.get('woo_category'):
                    # Category mapper may have output English category name
                    # Keep it as-is if already Greek, otherwise translate
                    if not any(greek_char in enhanced['woo_category'] for greek_char in 'αβγδεζηθικλμνξοπρστυφχψωΑΒΓΔΕΖΗΘΙΚΛΜΝΞΟΠΡΣΤΥΦΧΨΩ'):
                        greek_category = self.translator.translate_category(enhanced['woo_category'])
                        enhanced['woo_category'] = greek_category

                # Translate description if we had enhanced it
                if enhanced.get('ai_description'):
                    greek_description = self.translator.translate_description(enhanced['ai_description'])
                    enhanced['ai_description'] = greek_description

                logger.info(f"Translation complete: {enhanced['optimized_title']}")

            except Exception as e:
                logger.error(f"Translation failed for {product_data.get('sku')}: {str(e)}")
                # Keep English versions as fallback

        # 6. Process images
        if not skip_images and (product_data.get('main_image') or product_data.get('images')):
            image_data = {
                'sku': product_data.get('sku', 'unknown'),
                'supplier': product_data.get('supplier', 'unknown'),
                'main_image': product_data.get('main_image'),
                'images': product_data.get('images', [])
            }
            processed_images = self.image_optimizer.process_product_images(image_data)
            enhanced['processed_images'] = processed_images

        return enhanced

    def _update_product_element(self, elem: etree.Element, enhanced_data: Dict):
        """Update XML element with enhanced data"""
        supplier = enhanced_data.get('supplier', '')

        # Store hash for incremental processing
        if 'original_hash' in enhanced_data:
            hash_elem = elem.find('original_hash')
            if hash_elem is None:
                hash_elem = etree.SubElement(elem, 'original_hash')
            hash_elem.text = enhanced_data['original_hash']

        # Optimized title - replace original name
        if 'optimized_title' in enhanced_data:
            if supplier == 'estiahomeart':
                name_elem = elem.find('Name')
            else:
                name_elem = elem.find('name')
                if name_elem is None:
                    name_elem = elem.find('Name')

            if name_elem is not None:
                name_elem.text = enhanced_data['optimized_title']
                logger.debug(f"Updated title to: {enhanced_data['optimized_title'][:50]}...")

        # Category
        if 'woo_category' in enhanced_data:
            # Add a new element for WooCommerce category
            woo_cat_elem = etree.SubElement(elem, 'woo_category')
            woo_cat_elem.text = enhanced_data['woo_category']

        # Tags
        if 'tags' in enhanced_data and enhanced_data['tags']:
            tags_elem = etree.SubElement(elem, 'tags')
            tags_elem.text = ', '.join(enhanced_data['tags'])

        # Enhanced description - replace original description
        if 'enhanced_description' in enhanced_data:
            if supplier == 'estiahomeart':
                desc_elem = elem.find('Description')
            else:
                desc_elem = elem.find('description')
                if desc_elem is None:
                    desc_elem = elem.find('ExtendedDescription')

            if desc_elem is not None:
                desc_elem.text = etree.CDATA(enhanced_data['enhanced_description'])
                logger.debug(f"Updated description ({len(enhanced_data['enhanced_description'])} chars)")
            elif supplier == 'estiahomeart':
                # Create Description element if it doesn't exist (estiah has empty descriptions)
                desc_elem = etree.SubElement(elem, 'Description')
                desc_elem.text = etree.CDATA(enhanced_data['enhanced_description'])
                logger.debug(f"Created description ({len(enhanced_data['enhanced_description'])} chars)")

        # Tech specs
        if enhanced_data.get('tech_specs'):
            tech_elem = etree.SubElement(elem, 'tech_specs')
            tech_elem.text = etree.CDATA(enhanced_data['tech_specs'])

        # Processed images
        if 'processed_images' in enhanced_data:
            images_data = enhanced_data['processed_images']
            if images_data.get('main'):
                main_img_elem = etree.SubElement(elem, 'processed_main_image')
                main_img_elem.text = images_data['main']
            if images_data.get('gallery'):
                gallery_elem = etree.SubElement(elem, 'processed_gallery')
                for img_path in images_data['gallery']:
                    img_elem = etree.SubElement(gallery_elem, 'image')
                    img_elem.text = img_path
