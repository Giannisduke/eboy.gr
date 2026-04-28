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

# Liberta: ALL-CAPS unaccented Greek type words → properly accented Greek
_LIBERTA_GREEK_TYPES = {
    'ΚΡΕΒΑΤΙ': 'Κρεβάτι',
    'ΝΤΟΥΛΑΠΑ': 'Ντουλάπα',
    'ΒΙΒΛΙΟΘΗΚΗ': 'Βιβλιοθήκη',
    'ΣΥΡΤΑΡΙΕΡΑ': 'Συρταριέρα',
    'ΚΟΜΟΤΑ': 'Συρταριέρα',    # κομότα = chest of drawers
    'ΚΟΜΟΔΑ': 'Συρταριέρα',
    'ΚΟΜΟΔΙΝΟ': 'Κομοδίνο',
    'ΚΑΝΑΠΕΣ': 'Καναπές',
    'ΚΑΡΕΚΛΑ': 'Καρέκλα',
    'ΤΡΑΠΕΖΙ': 'Τραπέζι',
    'ΣΚΑΜΠΟ': 'Σκαμπό',
    'ΠΟΥΦ': 'Πουφ',
    'ΠΟΛΥΘΡΟΝΑ': 'Πολυθρόνα',
    'ΚΟΝΣΟΛΑ': 'Κονσόλα',
    'ΜΠΟΥΦΕΣ': 'Μπουφές',
    'ΒΙΤΡΙΝΑ': 'Βιτρίνα',
    'ΡΑΦΙΕΡΑ': 'Ραφιέρα',
    'ΚΡΕΜΑΣΤΡΑ': 'Κρεμάστρα',
    'ΚΑΘΡΕΦΤΗΣ': 'Καθρέφτης',
    'ΚΑΘΡΕΠΤΗΣ': 'Καθρέφτης',
    'ΜΠΑΟΥΛΟ': 'Μπαούλο',
    'ΠΑΠΟΥΤΣΟΘΗΚΗ': 'Παπουτσοθήκη',
    'ΠΑΓΚΑΚΙ': 'Παγκάκι',
    'ΚΕΦΑΛΑΡΙ': 'Κεφαλάρι',
    'ΣΟΜΙΕ': 'Σομιέ',
    'ΒΑΣΗ': 'Βάση',
    'ΤΡΑΠΕΖΑΡΙΑ': 'Τραπεζαρία',
    'ΓΡΑΦΕΙΟ': 'Γραφείο',
    'ΦΩΤΙΣΤΙΚΟ': 'Φωτιστικό',
    'ΧΑΛΙ': 'Χαλί',
}

# Liberta: English type compound words → Greek (longest match checked first)
_LIBERTA_ENGLISH_TYPES = [
    (['SOFA', 'BED'], 'Καναπές-κρεβάτι'),
    (['CORNER', 'SOFA'], 'Γωνιακός καναπές'),
    (['COFFEE', 'TABLE'], 'Τραπεζάκι σαλονιού'),
    (['SIDE', 'TABLE'], 'Βοηθητικό τραπέζι'),
    (['DINING', 'TABLE'], 'Τραπέζι τραπεζαρίας'),
    (['CONSOLE', 'TABLE'], 'Κονσόλα'),
    (['TV', 'UNIT'], 'Έπιπλο τηλεόρασης'),
    (['OFFICE', 'CHAIR'], 'Καρέκλα γραφείου'),
    (['ACCENT', 'CHAIR'], 'Πολυθρόνα'),
    (['DINING', 'CHAIR'], 'Καρέκλα'),
    (['EGG', 'CHAIR'], 'Πολυθρόνα'),
    (['CHEST', 'OF', 'DRAWERS'], 'Συρταριέρα'),
    (['CHEST'], 'Συρταριέρα'),
    (['BOOKCASE'], 'Βιβλιοθήκη'),
    (['BOOKSHELF'], 'Βιβλιοθήκη'),
    (['WARDROBE'], 'Ντουλάπα'),
    (['DRESSER'], 'Συρταριέρα'),
    (['NIGHTSTAND'], 'Κομοδίνο'),
    (['NIGHTTABLE'], 'Κομοδίνο'),
    (['SOFA'], 'Καναπές'),
    (['ARMCHAIR'], 'Πολυθρόνα'),
    (['STOOL'], 'Σκαμπό'),
    (['BENCH'], 'Παγκάκι'),
    (['CHAIR'], 'Καρέκλα'),
    (['TABLE'], 'Τραπέζι'),
    (['DESK'], 'Γραφείο'),
    (['SHELF'], 'Ραφιέρα'),
    (['MIRROR'], 'Καθρέφτης'),
    (['CONSOLE'], 'Κονσόλα'),
    (['BUFFET'], 'Μπουφές'),
    (['SIDEBOARD'], 'Μπουφές'),
    (['CABINET'], 'Ντουλάπα'),
    (['BED'], 'Κρεβάτι'),
    (['HEADBOARD'], 'Κεφαλάρι'),
    (['MATTRESS'], 'Στρώμα'),
    (['RACK'], 'Ραφιέρα'),
    (['LAMP'], 'Φωτιστικό'),
    (['RUG'], 'Χαλί'),
    (['CARPET'], 'Χαλί'),
    (['CUSHION'], 'Μαξιλάρι'),
    (['PILLOW'], 'Μαξιλάρι'),
    (['UNIT'], 'Έπιπλο'),
]


def _strip_greek_accents(text: str) -> str:
    """Strip accent marks from Greek text for accent-insensitive dict lookup."""
    return text.translate(str.maketrans(
        'ΆΈΉΊΌΎΏάέήίόύώϊϋΐΰ',
        'ΑΕΗΙΟΥΩαεηιουωιυιυ'
    ))


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

        # Liberta / estiahomeart: bypass AI entirely — deterministic extraction
        if supplier in ('libertab2b', 'estiahomeart'):
            result = self._extract_liberta_title(original_title)
            if result:
                logger.info(f"Liberta title: '{original_title[:50]}' -> '{result}'")
                return result
            fallback = self._extract_type_and_model(original_title)
            return fallback or original_title

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

    def _extract_liberta_title(self, original_title: str) -> Optional[str]:
        """
        Deterministic title extraction for Liberta/estiahomeart format.
        Pattern: [MODEL] [TYPE_WORDS] [ATTRIBUTES/COLORS/DIMS]
        Returns "[Greek type] [Model]" in sentence case.
        """
        def is_dimension(w: str) -> bool:
            return bool(re.search(r'\d+[xX×]|\d+(?:cm|εκ|ΕΚ)', w)) or \
                   bool(re.match(r'^[\d.,]+$', w))

        def is_greek(w: str) -> bool:
            return bool(re.search(r'[Α-ΩΆΈΉΊΌΎΏα-ωάέήίόύώ]', w))

        def is_latin_word(w: str) -> bool:
            return bool(re.match(r'^[A-Za-z]+(?:-[A-Za-z]+)*$', w))

        words = original_title.upper().split()
        words = [w for w in words if not is_dimension(w)]
        if not words:
            return None

        # First word must be a Latin model/collection name
        if not is_latin_word(words[0]):
            return None

        model_cap = words[0].capitalize()
        remaining = words[1:]
        if not remaining:
            return None

        # Case A: first remaining word contains Greek letters → Greek type
        if is_greek(remaining[0]):
            raw_type = re.sub(r'[^Α-ΩΆΈΉΊΌΎΏα-ωάέήίόύώ]', '', remaining[0])
            if not raw_type:
                return None
            lookup_key = _strip_greek_accents(raw_type.upper())
            greek_type = _LIBERTA_GREEK_TYPES.get(lookup_key)
            if greek_type:
                return f"{greek_type} {model_cap}"
            # Unknown Greek type: normalize case and return best guess
            normalized = raw_type[0].upper() + raw_type[1:].lower()
            return f"{normalized} {model_cap}"

        # Case B: remaining words are Latin → match English compound type
        # First: check if words[0]+remaining form a 2+ word compound (e.g., EGG CHAIR, ACCENT CHAIR)
        all_latin = [words[0]] + [w for w in remaining if is_latin_word(w)]
        for type_tokens, greek_translation in _LIBERTA_ENGLISH_TYPES:
            n = len(type_tokens)
            if n >= 2 and len(all_latin) >= n and all_latin[:n] == type_tokens:
                return f"{greek_translation} {model_cap}"

        # Standard: words[0] is model, remaining contains the type
        latin_remaining = [w for w in remaining if is_latin_word(w)]
        for type_tokens, greek_translation in _LIBERTA_ENGLISH_TYPES:
            n = len(type_tokens)
            if len(latin_remaining) >= n and latin_remaining[:n] == type_tokens:
                return f"{greek_translation} {model_cap}"

        return None

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
