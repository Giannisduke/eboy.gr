"""
Title Optimizer
Shortens and optimizes product titles for better SEO and user experience.
"""

import logging
import yaml
from typing import Optional
from .ai_client import OllamaClient

logger = logging.getLogger(__name__)


class TitleOptimizer:
    """Optimizes product titles using AI"""

    def __init__(self, ai_client: OllamaClient, prompts_config: dict):
        self.ai_client = ai_client
        self.prompts_config = prompts_config
        self.prompt_template = prompts_config.get('title_optimization', '')

    def optimize(self, original_title: str, supplier: str = None) -> Optional[str]:
        """
        Optimize a product title

        Args:
            original_title: The original product title
            supplier: Supplier name to use supplier-specific prompt

        Returns:
            Optimized title or None if failed
        """
        if not original_title or len(original_title.strip()) == 0:
            logger.warning("Empty title provided")
            return None

        # Select supplier-specific prompt if available
        if supplier:
            prompt_key = f'title_optimization_{supplier}'
            prompt_template = self.prompts_config.get(prompt_key, self.prompt_template)
            logger.debug(f"Using {prompt_key} prompt for supplier: {supplier}")
        else:
            prompt_template = self.prompt_template

        # Use AI to optimize (even for short titles, to apply supplier-specific rules)
        prompt = prompt_template.format(original_title=original_title)

        try:
            optimized = self.ai_client.generate(
                prompt=prompt,
                temperature=0.3,  # Lower temperature for more consistent results
                max_tokens=100
            )

            if optimized:
                # Clean up the response
                optimized = optimized.strip()

                # Remove quotes if AI added them
                if optimized.startswith('"') and optimized.endswith('"'):
                    optimized = optimized[1:-1]
                if optimized.startswith("'") and optimized.endswith("'"):
                    optimized = optimized[1:-1]

                # Validate length
                if len(optimized) > 70:
                    logger.warning(f"AI generated title too long ({len(optimized)} chars), falling back to simple cleanup")
                    return self._simple_cleanup(original_title)

                if len(optimized) < 10:
                    logger.warning(f"AI generated title too short ({len(optimized)} chars), falling back to simple cleanup")
                    return self._simple_cleanup(original_title)

                logger.info(f"Title optimized: '{original_title[:50]}...' -> '{optimized}'")
                return optimized
            else:
                logger.warning("AI failed to optimize title, using fallback")
                return self._simple_cleanup(original_title)

        except Exception as e:
            logger.error(f"Error optimizing title: {str(e)}")
            return self._simple_cleanup(original_title)

    def _simple_cleanup(self, title: str) -> str:
        """
        Simple rule-based title cleanup as fallback

        Removes dimensions, excessive punctuation, etc.
        """
        import re

        # Remove dimensions (e.g., "80x37x123εκ", "80x37x123cm")
        title = re.sub(r'\d+[xX×]\d+[xX×]?\d*\s*(εκ|cm|mm|μ|m)?', '', title)

        # Remove standalone dimensions (e.g., "80cm")
        title = re.sub(r'\b\d+\s*(εκ|cm|mm|μ|m)\b', '', title)

        # Remove excessive punctuation
        title = re.sub(r'[-_]+', ' ', title)

        # Normalize whitespace
        title = ' '.join(title.split())

        # Capitalize first letter of each word
        title = title.title()

        # Limit length
        if len(title) > 65:
            # Try to cut at a word boundary
            title = title[:62].rsplit(' ', 1)[0] + '...'

        return title.strip()
