"""
Category Mapper
Maps supplier categories to WooCommerce categories using AI and keyword matching.
"""

import logging
import json
import yaml
from typing import Optional, Dict, List, Tuple
from pathlib import Path
from .ai_client import OllamaClient

logger = logging.getLogger(__name__)


class CategoryMapper:
    """Maps supplier categories to WooCommerce categories"""

    def __init__(self, ai_client: OllamaClient, prompts_config: dict, categories_config: dict):
        self.ai_client = ai_client
        self.prompt_template = prompts_config.get('category_mapping', '')
        self.prompt_with_confidence = prompts_config.get('category_mapping_with_confidence', '')
        self.categories = categories_config.get('categories', [])
        self.category_keywords = categories_config.get('category_keywords', {})

        # Cache for mappings
        self.mapping_cache = {}

        # Store mappings for review
        self.mappings_for_review = []

    def map_category(self, supplier_category: str, product_name: str = "", use_confidence: bool = True) -> Tuple[str, float, List[str]]:
        """
        Map a supplier category to a WooCommerce category

        Args:
            supplier_category: The supplier's category hierarchy (e.g., "Έπιπλα Χωλ > Παπουτσοθήκες")
            product_name: Product name for additional context
            use_confidence: Whether to use the confidence-aware prompt

        Returns:
            Tuple of (woo_category, confidence_score, subcategories)
        """
        # Check cache first
        cache_key = f"{supplier_category}|{product_name}"
        if cache_key in self.mapping_cache:
            return self.mapping_cache[cache_key]

        # Try rule-based mapping first (fast)
        rule_result = self._rule_based_mapping(supplier_category)
        if rule_result and rule_result[1] > 0.8:  # High confidence from rules
            self.mapping_cache[cache_key] = rule_result
            self._store_mapping_for_review(supplier_category, product_name, *rule_result, method="rule")
            return rule_result

        # Fall back to AI mapping
        try:
            if use_confidence:
                result = self._ai_mapping_with_confidence(supplier_category, product_name)
            else:
                result = self._ai_mapping_simple(supplier_category, product_name)

            if result:
                self.mapping_cache[cache_key] = result
                self._store_mapping_for_review(supplier_category, product_name, *result, method="ai")
                return result
            else:
                # Final fallback
                logger.warning(f"AI mapping failed for: {supplier_category}, using default")
                fallback = ("Έπιπλο", 0.5, [])
                self._store_mapping_for_review(supplier_category, product_name, *fallback, method="fallback")
                return fallback

        except Exception as e:
            logger.error(f"Error mapping category: {str(e)}")
            fallback = ("Έπιπλο", 0.3, [])
            return fallback

    def _ai_mapping_with_confidence(self, supplier_category: str, product_name: str) -> Optional[Tuple[str, float, List[str]]]:
        """Use AI to map category with confidence score"""
        prompt = self.prompt_with_confidence.format(
            supplier_category=supplier_category,
            product_name=product_name or "N/A"
        )

        response = self.ai_client.generate(
            prompt=prompt,
            temperature=0.2,  # Low temperature for consistent results
            max_tokens=200
        )

        if response:
            try:
                # Parse JSON response
                data = json.loads(response)
                category = data.get('category', 'Έπιπλο')
                confidence = float(data.get('confidence', 0.5))
                subcategories = data.get('subcategories', [])

                # Validate category
                if not self._is_valid_category(category):
                    logger.warning(f"Invalid category from AI: {category}, using fallback")
                    return None

                logger.info(f"Mapped '{supplier_category}' -> '{category}' (confidence: {confidence:.2f})")
                return (category, confidence, subcategories)

            except json.JSONDecodeError:
                logger.warning(f"Failed to parse AI JSON response: {response[:100]}")
                return None

        return None

    def _ai_mapping_simple(self, supplier_category: str, product_name: str) -> Optional[Tuple[str, float, List[str]]]:
        """Simple AI mapping without confidence (faster)"""
        prompt = self.prompt_template.format(
            supplier_category=supplier_category,
            product_name=product_name or "N/A"
        )

        response = self.ai_client.generate(
            prompt=prompt,
            temperature=0.2,
            max_tokens=50
        )

        if response:
            category = response.strip()

            # Clean up response
            category = category.replace('"', '').replace("'", "").strip()

            if self._is_valid_category(category):
                # Extract subcategories from supplier category
                subcats = self._extract_subcategories(supplier_category)
                return (category, 0.7, subcats)

        return None

    def _rule_based_mapping(self, supplier_category: str) -> Optional[Tuple[str, float, List[str]]]:
        """
        Rule-based category mapping using keywords

        Fast and reliable for common patterns
        """
        category_lower = supplier_category.lower()

        # Check each category's keywords
        for category_name, keywords in self.category_keywords.items():
            for keyword in keywords:
                if keyword.lower() in category_lower:
                    subcats = self._extract_subcategories(supplier_category)
                    logger.debug(f"Rule-based match: '{supplier_category}' -> '{category_name}' (keyword: {keyword})")
                    return (category_name, 0.9, subcats)

        return None

    def _extract_subcategories(self, supplier_category: str) -> List[str]:
        """Extract subcategory terms from supplier hierarchy"""
        if not supplier_category:
            return []

        # Split by > and extract meaningful parts
        parts = supplier_category.split('>')
        subcats = []

        for part in parts:
            part = part.strip()
            if part and len(part) > 2:
                # Convert to lowercase tag format
                subcat = part.lower()
                subcats.append(subcat)

        return subcats[:5]  # Limit to first 5 levels

    def _is_valid_category(self, category: str) -> bool:
        """Check if category is one of the valid 9 categories"""
        valid_categories = [cat['name'] for cat in self.categories]
        return category in valid_categories

    def _store_mapping_for_review(self, supplier_category: str, product_name: str, woo_category: str, confidence: float, subcategories: List[str], method: str):
        """Store mapping for later review"""
        mapping = {
            'supplier_category': supplier_category,
            'product_name': product_name,
            'mapped_to': woo_category,
            'confidence': round(confidence, 2),
            'subcategories': subcategories,
            'method': method,
            'status': 'needs_review' if confidence < 0.7 else 'auto'
        }
        self.mappings_for_review.append(mapping)

    def export_mappings_for_review(self, output_file: Path):
        """Export all mappings to a YAML file for manual review"""
        review_data = {
            'total_mappings': len(self.mappings_for_review),
            'high_confidence': len([m for m in self.mappings_for_review if m['confidence'] >= 0.8]),
            'medium_confidence': len([m for m in self.mappings_for_review if 0.6 <= m['confidence'] < 0.8]),
            'low_confidence': len([m for m in self.mappings_for_review if m['confidence'] < 0.6]),
            'mappings': self.mappings_for_review
        }

        with open(output_file, 'w', encoding='utf-8') as f:
            yaml.dump(review_data, f, allow_unicode=True, sort_keys=False, default_flow_style=False)

        logger.info(f"Exported {len(self.mappings_for_review)} mappings to {output_file}")

    def load_reviewed_mappings(self, reviewed_file: Path):
        """Load user-reviewed mappings from file"""
        if not reviewed_file.exists():
            logger.warning(f"Reviewed mappings file not found: {reviewed_file}")
            return

        with open(reviewed_file, 'r', encoding='utf-8') as f:
            data = yaml.safe_load(f)

        # Load corrected mappings into cache
        for mapping in data.get('mappings', []):
            if mapping.get('status') == 'manual_corrected':
                cache_key = f"{mapping['supplier_category']}|{mapping.get('product_name', '')}"
                self.mapping_cache[cache_key] = (
                    mapping['mapped_to'],
                    1.0,  # Manual corrections have 100% confidence
                    mapping.get('subcategories', [])
                )

        logger.info(f"Loaded reviewed mappings from {reviewed_file}")
