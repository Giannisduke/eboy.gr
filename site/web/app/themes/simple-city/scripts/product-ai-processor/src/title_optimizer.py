"""
Title Optimizer
Shortens and optimizes product titles for better SEO and user experience.
"""

import logging
import re
from typing import Optional
from .ai_client import OllamaClient

logger = logging.getLogger(__name__)

# Supplier brand names — excluded from model name detection
_KNOWN_BRANDS = frozenset({
    'pakoworld', 'libertab2b', 'liberta', 'b2bmarkt', 'estiahomeart', 'estia',
})

# Latin abbreviations / materials — excluded from model name detection
_KNOWN_NON_MODELS = frozenset({
    'pu', 'mdf', 'pe', 'pc', 'led', 'uv', 'rattan', 'velvet', 'plus',
})


class TitleOptimizer:
    """Optimizes product titles using AI"""

    def __init__(self, ai_client: OllamaClient, prompts_config: dict):
        self.ai_client = ai_client
        self.prompts_config = prompts_config
        self.prompt_template = prompts_config.get('title_optimization', '')

    def optimize(self, original_title: str, supplier: str = None) -> Optional[str]:
        if not original_title or len(original_title.strip()) == 0:
            logger.warning("Empty title provided")
            return None

        prompt_key = f'title_optimization_{supplier}' if supplier else ''
        prompt_template = (
            self.prompts_config.get(prompt_key, self.prompt_template)
            if prompt_key else self.prompt_template
        )

        prompt = prompt_template.format(original_title=original_title)

        try:
            ai_result = self.ai_client.generate(
                prompt=prompt,
                temperature=0.3,
                max_tokens=100
            )

            if ai_result:
                ai_result = ai_result.strip().strip('"').strip("'")
                optimized = self._extract_type_and_model(ai_result) \
                            or self._extract_type_and_model(original_title)
            else:
                logger.warning("AI returned empty result, using fallback")
                optimized = self._extract_type_and_model(original_title)

        except Exception as e:
            logger.error(f"Error optimizing title: {e}")
            optimized = self._extract_type_and_model(original_title)

        if not optimized or len(optimized) < 3:
            return original_title

        logger.info(f"Title: '{original_title[:50]}' -> '{optimized}'")
        return optimized

    def _extract_type_and_model(self, title: str) -> Optional[str]:
        """Keep Greek type words + consecutive non-brand Latin model words."""
        cleaned = [re.sub(r'^[^\w-]+|[^\w-]+$', '', w) for w in title.split()]

        def is_latin_word(s: str) -> bool:
            return bool(re.match(r'^[A-Za-z][A-Za-z0-9]*(?:-[A-Za-z][A-Za-z0-9]*)*$', s))

        def is_greek_word(s: str) -> bool:
            return bool(re.search(r'[α-ωΑ-ΩάέήίόύώΆΈΉΊΌΎΏ]', s))

        # Greek type words = all Greek words before the first Latin word (brand or not)
        first_latin = next((i for i, c in enumerate(cleaned) if c and is_latin_word(c)), len(cleaned))
        type_words = []
        for i, clean in enumerate(cleaned[:first_latin]):
            if clean and is_greek_word(clean):
                word = clean[0].upper() + clean[1:].lower() if i == 0 else clean.lower()
                type_words.append(word)

        # Model words = consecutive non-brand Latin words starting from first non-brand Latin
        model_start = next(
            (i for i, c in enumerate(cleaned)
             if c and is_latin_word(c)
             and c.lower() not in _KNOWN_BRANDS
             and c.lower() not in _KNOWN_NON_MODELS),
            None
        )
        model_words = []
        if model_start is not None:
            for clean in cleaned[model_start:]:
                if not clean:
                    continue
                if is_greek_word(clean):
                    break
                if is_latin_word(clean):
                    if clean.lower() in _KNOWN_BRANDS or clean.lower() in _KNOWN_NON_MODELS:
                        break
                    model_words.append('-'.join(p.capitalize() for p in clean.split('-')))

        parts = type_words + model_words
        return ' '.join(parts) if parts else None
