"""
Product Content Translator (English → Greek)
Uses Llama-Krikri model for high-quality Greek translations
"""

import logging
from typing import Dict, Any
import yaml
from pathlib import Path

from .ai_client import OllamaClient

logger = logging.getLogger(__name__)


class ProductTranslator:
    """Translates AI-enhanced English content to Greek"""

    def __init__(
        self,
        ollama_client: OllamaClient,
        prompts_file: str = "config/prompts_translation.yaml"
    ):
        self.client = ollama_client
        self.prompts = self._load_prompts(prompts_file)

    def _load_prompts(self, prompts_file: str) -> Dict[str, str]:
        """Load translation prompts from YAML file"""
        try:
            # Go up one level from src/ to project root, then to config/
            prompts_path = Path(__file__).parent.parent / prompts_file
            with open(prompts_path, 'r', encoding='utf-8') as f:
                return yaml.safe_load(f)
        except Exception as e:
            logger.error(f"Failed to load translation prompts: {e}")
            raise

    def translate_title(self, english_title: str) -> str:
        """
        Translate product title from English to Greek

        Args:
            english_title: English product title

        Returns:
            Greek product title
        """
        try:
            prompt = self.prompts['title_translation'].format(
                english_title=english_title
            )

            greek_title = self.client.generate(prompt).strip()

            # Post-processing: Remove extra text that model may add
            # Keep only the first line if model added explanations
            if '\n' in greek_title:
                # Split by newline and take first non-empty line
                lines = [line.strip() for line in greek_title.split('\n') if line.strip()]
                greek_title = lines[0] if lines else greek_title.split('\n')[0]

            # Remove common prefixes/explanations
            prefixes_to_remove = [
                'output:', 'μετάφραση:', 'translation:',
                'ελληνικά:', 'greek:', 'τίτλος:', 'greek translation:',
                'ελληνική μετάφραση:', 'αποτέλεσμα:'
            ]
            for prefix in prefixes_to_remove:
                if greek_title.lower().startswith(prefix):
                    greek_title = greek_title[len(prefix):].strip()

            # Remove quotes and extra whitespace
            greek_title = greek_title.strip('"\'').strip()

            # Remove trailing dots or commas
            greek_title = greek_title.rstrip('.,')

            # Enforce sentence case: ONLY first letter uppercase
            greek_title = self._enforce_sentence_case(greek_title)

            logger.info(f"Translated title: {english_title} → {greek_title}")
            return greek_title

        except Exception as e:
            logger.error(f"Title translation failed: {e}")
            return english_title  # Fallback to English

    def _enforce_sentence_case(self, title: str) -> str:
        """
        Enforce sentence case for Greek titles:
        - ONLY first letter uppercase
        - Everything else lowercase
        - Numbers remain unchanged

        Args:
            title: Greek title to process

        Returns:
            Title in sentence case
        """
        if not title or len(title) == 0:
            return title

        # Split into words
        words = title.split()
        if not words:
            return title

        result_words = []
        for i, word in enumerate(words):
            # First word: capitalize first letter only
            if i == 0:
                if len(word) > 0:
                    result_words.append(word[0].upper() + word[1:].lower())
                else:
                    result_words.append(word)
            # Numbers and words containing numbers: keep as-is
            elif any(c.isdigit() for c in word):
                result_words.append(word)
            # Everything else: lowercase
            else:
                result_words.append(word.lower())

        return ' '.join(result_words)

    def translate_description(self, english_description: str) -> str:
        """
        Translate product description from English to Greek

        Args:
            english_description: English HTML description

        Returns:
            Greek HTML description
        """
        try:
            prompt = self.prompts['description_translation'].format(
                english_description=english_description
            )

            greek_description = self.client.generate(prompt).strip()

            logger.info("Description translated successfully")
            return greek_description

        except Exception as e:
            logger.error(f"Description translation failed: {e}")
            return english_description  # Fallback to English

    def translate_tags(self, english_tags: str) -> str:
        """
        Translate product tags from English to Greek

        Args:
            english_tags: Comma-separated English tags

        Returns:
            Comma-separated Greek tags
        """
        try:
            prompt = self.prompts['tag_translation'].format(
                english_tags=english_tags
            )

            greek_tags = self.client.generate(prompt).strip()

            # Clean up the output - sometimes models add extra text
            # Keep only the first line if there are multiple lines
            if '\n' in greek_tags:
                greek_tags = greek_tags.split('\n')[0].strip()

            # Remove common prefixes/suffixes
            prefixes_to_remove = [
                'output:', 'greek:', 'ελληνικά:', 'μετάφραση:',
                'translation:', 'tags:', 'αποτέλεσμα:'
            ]
            for prefix in prefixes_to_remove:
                if greek_tags.lower().startswith(prefix):
                    greek_tags = greek_tags[len(prefix):].strip()

            # Remove quotes if present
            greek_tags = greek_tags.strip('"\'')

            # Clean up tags: split by comma, trim each, remove empty, rejoin
            if ',' in greek_tags:
                tags_list = [tag.strip() for tag in greek_tags.split(',')]
                tags_list = [tag for tag in tags_list if tag]  # Remove empty
                greek_tags = ', '.join(tags_list)

            # Remove trailing commas/spaces
            greek_tags = greek_tags.rstrip(', ')

            logger.info(f"Translated tags: {english_tags} → {greek_tags}")
            return greek_tags

        except Exception as e:
            logger.error(f"Tag translation failed: {e}")
            return english_tags  # Fallback to English

    def translate_category(self, english_category: str) -> str:
        """
        Translate category name from English to Greek

        Args:
            english_category: English category name

        Returns:
            Greek category name
        """
        # Use direct mapping for categories (more reliable)
        category_map = {
            'Furniture': 'Έπιπλο',
            'Office': 'Γραφείο',
            'Garden': 'Κήπος',
            'Decoration': 'Διακόσμηση',
            'White goods': 'Λευκά είδη',
            'Home organization': 'Οργάνωση σπιτιού',
            'Kitchen': 'Κουζίνα',
            'Bathroom': 'Μπάνιο',
            'Lighting': 'Φωτισμός'
        }

        greek_category = category_map.get(english_category, english_category)
        logger.info(f"Translated category: {english_category} → {greek_category}")

        return greek_category

    def translate_product(self, english_product: Dict[str, Any]) -> Dict[str, Any]:
        """
        Translate entire product data from English to Greek

        Args:
            english_product: Product dict with English AI-enhanced content

        Returns:
            Product dict with Greek content
        """
        greek_product = english_product.copy()

        # Translate each field
        if 'ai_title' in english_product:
            greek_product['ai_title'] = self.translate_title(
                english_product['ai_title']
            )

        if 'ai_description' in english_product:
            greek_product['ai_description'] = self.translate_description(
                english_product['ai_description']
            )

        if 'ai_tags' in english_product:
            greek_product['ai_tags'] = self.translate_tags(
                english_product['ai_tags']
            )

        if 'woo_category' in english_product:
            greek_product['woo_category'] = self.translate_category(
                english_product['woo_category']
            )

        return greek_product
