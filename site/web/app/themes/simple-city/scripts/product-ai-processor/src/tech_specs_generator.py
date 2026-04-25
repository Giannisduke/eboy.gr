"""
Tech Specs Generator
Generates a structured HTML list of technical characteristics using AI.
Works for all suppliers: extracts from description (Pakoworld) or builds from attributes.
"""

import logging
import re
from typing import Optional
from .ai_client import OllamaClient

logger = logging.getLogger(__name__)


class TechSpecsGenerator:
    """Generates technical specifications as <ul><li> HTML using AI"""

    def __init__(self, ai_client: OllamaClient, prompts_config: dict):
        self.ai_client = ai_client
        self.prompt_template = prompts_config.get('tech_specs', '')

    def generate(self, product_data: dict) -> Optional[str]:
        title = product_data.get('title', '')
        if not title:
            return None

        supplier_category  = product_data.get('supplier_category', '')
        material           = product_data.get('material', '')
        dimensions         = product_data.get('dimensions', '')
        attributes         = product_data.get('attributes', [])
        original_desc      = product_data.get('original_description', '')

        attrs_text  = self._format_attributes(attributes)
        clean_desc  = self._strip_html(original_desc)[:800]

        prompt = self.prompt_template.format(
            title=title,
            supplier_category=supplier_category or 'N/A',
            material=material or 'N/A',
            dimensions=dimensions or 'N/A',
            attributes=attrs_text or 'N/A',
            original_description=clean_desc or 'N/A',
        )

        try:
            result = self.ai_client.generate(
                prompt=prompt,
                temperature=0.4,
                max_tokens=500,
            )

            if result:
                # Strip markdown code fences before extracting <ul>
                result = re.sub(r'^```(?:html)?\s*', '', result.strip(), flags=re.IGNORECASE)
                result = re.sub(r'\s*```$', '', result.strip())
                result = result.strip()

                ul = self._extract_ul(result)
                if ul:
                    logger.info(f"Tech specs generated for: {title[:50]}")
                    return ul

            logger.warning(f"AI returned no valid <ul> for tech specs: {title[:50]}")
            return None

        except Exception as e:
            logger.error(f"Error generating tech specs for {title[:50]}: {str(e)}")
            return None

    def _format_attributes(self, attributes) -> str:
        if not attributes:
            return ""
        parts = []
        items = attributes[:10] if isinstance(attributes, list) else []
        for attr in items:
            if isinstance(attr, dict):
                name  = attr.get('name', '')
                value = attr.get('value', '')
                if name and value:
                    parts.append(f"{name}: {value}")
            elif isinstance(attr, str):
                parts.append(attr)
        return ", ".join(parts)

    def _strip_html(self, html: str) -> str:
        if not html:
            return ""
        clean = re.sub(r'<[^>]+>', ' ', html)
        return ' '.join(clean.split()).strip()

    def _extract_ul(self, text: str) -> Optional[str]:
        """Extract the first <ul>...</ul> block from AI output."""
        match = re.search(r'<ul>.*?</ul>', text, re.DOTALL | re.IGNORECASE)
        return match.group(0) if match else None
