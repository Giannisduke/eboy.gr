"""
Description Enhancer
Enhances product descriptions using AI to make them more compelling and SEO-friendly.
"""

import logging
import re
from typing import Optional
from .ai_client import OllamaClient

logger = logging.getLogger(__name__)


class DescriptionEnhancer:
    """Enhances product descriptions using AI"""

    def __init__(self, ai_client: OllamaClient, prompts_config: dict):
        self.ai_client = ai_client
        self.prompt_template = prompts_config.get('description_enhancement', '')

    def enhance(self, product_data: dict) -> Optional[str]:
        """
        Enhance a product description

        Args:
            product_data: Dict containing product information
                - title: Product title
                - supplier_category: Supplier category hierarchy
                - material: Product materials
                - dimensions: Product dimensions
                - features: Product features/attributes
                - original_description: Original description from XML

        Returns:
            Enhanced HTML description or None if failed
        """
        title = product_data.get('title', '')
        supplier_category = product_data.get('supplier_category', '')
        material = product_data.get('material', '')
        dimensions = product_data.get('dimensions', '')
        features = product_data.get('features', [])
        original_description = product_data.get('original_description', '')

        if not title:
            logger.warning("No title provided for description enhancement")
            return None

        # Clean the original description (remove excessive HTML, scripts, etc.)
        cleaned_description = self._clean_html(original_description)

        # Extract key features from attributes
        features_text = self._extract_features(features)

        # Build the prompt
        prompt = self.prompt_template.format(
            title=title,
            supplier_category=supplier_category or 'N/A',
            material=material or 'N/A',
            dimensions=dimensions or 'N/A',
            features=features_text or 'N/A',
            original_description=cleaned_description[:500] if cleaned_description else 'N/A'
        )

        try:
            enhanced = self.ai_client.generate(
                prompt=prompt,
                temperature=0.7,  # Balanced creativity
                max_tokens=800  # Allow longer descriptions
            )

            if enhanced:
                # Strip markdown code fences (```html ... ```) that some models add
                enhanced = re.sub(r'^```(?:html)?\s*', '', enhanced.strip(), flags=re.IGNORECASE)
                enhanced = re.sub(r'\s*```$', '', enhanced.strip())
                enhanced = enhanced.strip()

                # Validate that it's HTML
                if not self._contains_html(enhanced):
                    # If AI didn't return HTML, wrap it in basic HTML
                    enhanced = self._wrap_in_html(enhanced)

                logger.info(f"Description enhanced for: {title[:50]}...")
                return enhanced
            else:
                logger.warning("AI failed to enhance description, using cleaned original")
                return self._wrap_in_html(cleaned_description) if cleaned_description else None

        except Exception as e:
            logger.error(f"Error enhancing description: {str(e)}")
            return self._wrap_in_html(cleaned_description) if cleaned_description else None

    def _clean_html(self, html: str) -> str:
        """Remove excessive HTML tags and clean up text"""
        if not html:
            return ""

        # Remove script and style tags
        html = re.sub(r'<script[^>]*>.*?</script>', '', html, flags=re.DOTALL | re.IGNORECASE)
        html = re.sub(r'<style[^>]*>.*?</style>', '', html, flags=re.DOTALL | re.IGNORECASE)

        # Remove inline styles
        html = re.sub(r'style="[^"]*"', '', html)
        html = re.sub(r"style='[^']*'", '', html)

        # Remove class attributes
        html = re.sub(r'class="[^"]*"', '', html)
        html = re.sub(r"class='[^']*'", '', html)

        # Normalize whitespace
        html = ' '.join(html.split())

        return html.strip()

    def _extract_features(self, features: list) -> str:
        """Extract key features from attributes list"""
        if not features:
            return ""

        # Take first 5-6 features
        feature_list = features[:6] if isinstance(features, list) else []
        return ", ".join(str(f) for f in feature_list)

    def _contains_html(self, text: str) -> bool:
        """Check if text contains HTML tags"""
        return bool(re.search(r'<[a-z][\s\S]*>', text, re.IGNORECASE))

    def _wrap_in_html(self, text: str) -> str:
        """Wrap plain text in basic HTML structure"""
        if not text:
            return ""

        # Split into paragraphs
        paragraphs = text.split('\n\n')

        html_parts = []
        for para in paragraphs:
            para = para.strip()
            if para:
                # Check if it's a bullet list
                if '\n-' in para or '\n•' in para or '\n*' in para:
                    items = [item.strip('- •*\t') for item in para.split('\n') if item.strip()]
                    html_parts.append('<ul>')
                    for item in items:
                        if item:
                            html_parts.append(f'<li>{item}</li>')
                    html_parts.append('</ul>')
                else:
                    html_parts.append(f'<p>{para}</p>')

        return '\n'.join(html_parts)
