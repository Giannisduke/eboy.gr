"""
Tag Generator
Generates relevant product tags using AI and rule-based extraction.
"""

import logging
import re
from typing import List, Optional
from .ai_client import OllamaClient

logger = logging.getLogger(__name__)


class TagGenerator:
    """Generates product tags using AI"""

    WHITELIST = {
        'καναπές', 'πολυθρόνα', 'τραπέζι', 'καρέκλα', 'σκαμπό', 'πουφ', 'κρεβάτι',
        'ντουλάπα', 'βιβλιοθήκη', 'γραφείο', 'συρταριέρα', 'κομοδίνο',
        'κονσόλα', 'μπουφές', 'βιτρίνα', 'ραφιέρα', 'παπουτσοθήκη',
        'κρεμάστρα', 'καλόγερος', 'σομιέ', 'στρώμα', 'μαξιλάρι',
        'φωτιστικό', 'καθρέφτης', 'διακοσμητικό', 'βάζο', 'ξαπλώστρα',
        'σεζλόνγκ', 'ομπρέλα', 'αιώρα', 'καλάθι', 'έπιπλο τηλεόρασης', 'φυτά',
        'πίνακας', 'κηροπήγιο', 'χαλί', 'ρολόι', 'ριχτάρ', 'μπαούλο', 'λουλούδι',
        'παγκάκι',
    }

    def __init__(self, ai_client: OllamaClient, prompts_config: dict):
        self.ai_client = ai_client
        self.prompt_template = prompts_config.get('tag_generation', '')

    def generate_tags(self, product_data: dict) -> List[str]:
        """
        Generate tags for a product

        Args:
            product_data: Dict containing:
                - title: Product title
                - supplier_category: Supplier category hierarchy
                - woo_category: Assigned WooCommerce category
                - attributes: Product attributes/features
                - description_summary: Brief description

        Returns:
            List of tag strings (lowercase, Greek)
        """
        title = product_data.get('title', '')
        supplier_category = product_data.get('supplier_category', '')
        woo_category = product_data.get('woo_category', '')
        attributes = product_data.get('attributes', [])
        description = product_data.get('description_summary', '')

        if not title:
            logger.warning("No title provided for tag generation")
            return []

        # Build attributes text
        attrs_text = self._format_attributes(attributes)

        # Build the prompt
        prompt = self.prompt_template.format(
            title=title,
            supplier_category=supplier_category or 'N/A',
            woo_category=woo_category or 'N/A',
            attributes=attrs_text or 'N/A',
            description_summary=description[:200] if description else 'N/A'
        )

        try:
            # Priority 1: title token scan — most reliable for deterministic titles
            # (e.g. "Πουφ Jutta" → πουφ, "Σκαμπό Cube" → σκαμπό)
            final_tags = []
            title_clean = self._clean_tag(title)
            for token in title_clean.split():
                canonical = self._enforce_whitelist(token)
                if canonical:
                    final_tags = [canonical]
                    break
            # Also try the full cleaned title for multi-word tags
            if not final_tags:
                canonical = self._enforce_whitelist(title_clean)
                if canonical:
                    final_tags = [canonical]

            if not final_tags:
                # Priority 2: AI-generated tag (uses title + category context)
                ai_response = self.ai_client.generate(
                    prompt=prompt,
                    temperature=0.6,
                    max_tokens=200
                )
                ai_tags = self._parse_tag_response(ai_response) if ai_response else []
                for raw in ai_tags:
                    canonical = self._enforce_whitelist(self._clean_tag(raw))
                    if canonical:
                        final_tags = [canonical]
                        break

            if not final_tags:
                # Priority 3: supplier_category
                if supplier_category:
                    for part in supplier_category.split('>'):
                        canonical = self._enforce_whitelist(self._clean_tag(part.strip()))
                        if canonical:
                            final_tags = [canonical]
                            break

            if not final_tags:
                # Priority 4: rule-based fallback (attributes, material keywords)
                rule_tags = self._extract_rule_based_tags(product_data)
                for raw in rule_tags:
                    canonical = self._enforce_whitelist(self._clean_tag(raw))
                    if canonical:
                        final_tags = [canonical]
                        break

            logger.info(f"Generated {len(final_tags)} tags for: {title[:50]}...")
            return final_tags

        except Exception as e:
            logger.error(f"Error generating tags: {str(e)}")
            # Fallback to rule-based only
            return self._extract_rule_based_tags(product_data)[:15]

    def _parse_tag_response(self, response: str) -> List[str]:
        """Parse comma-separated tags from AI response"""
        # Remove any extra text before/after tags
        response = response.strip()

        # If response has multiple lines, try to find the line with tags
        if '\n' in response:
            lines = response.split('\n')
            # Look for a line with commas (likely the tag list)
            for line in lines:
                if ',' in line:
                    response = line
                    break

        # Split by comma
        tags = [tag.strip() for tag in response.split(',')]

        return tags

    def _extract_rule_based_tags(self, product_data: dict) -> List[str]:
        """
        Extract tags using rules (backup method)

        Extracts from:
        - Supplier category hierarchy
        - Product attributes
        - Title keywords
        """
        tags = []

        # Extract from supplier category
        supplier_category = product_data.get('supplier_category', '')
        if supplier_category:
            # Split by > and extract each level
            parts = supplier_category.split('>')
            for part in parts:
                part = part.strip().lower()
                if part and len(part) > 2:
                    tags.append(part)

        # Extract colors from attributes
        attributes = product_data.get('attributes', [])
        color_keywords = ['χρώμα', 'χρωμα', 'color', 'colour']
        for attr in attributes:
            attr_str = str(attr).lower()
            for keyword in color_keywords:
                if keyword in attr_str:
                    # Extract the color value
                    match = re.search(rf'{keyword}[:\s]+([^\n,]+)', attr_str)
                    if match:
                        color = match.group(1).strip()
                        tags.append(color)

        # Extract material keywords
        material_keywords = ['ξύλο', 'μέταλλο', 'πλαστικό', 'ύφασμα', 'γυαλί', 'δέρμα']
        title_lower = product_data.get('title', '').lower()
        for material in material_keywords:
            if material in title_lower:
                tags.append(material)

        return tags

    def _format_attributes(self, attributes: list) -> str:
        """Format attributes list as text"""
        if not attributes:
            return ""

        if isinstance(attributes, list):
            # Take first 8 attributes
            attrs = attributes[:8]
            return ", ".join(str(a) for a in attrs)
        else:
            return str(attributes)[:200]

    # Maps derivative/compound Greek forms to their canonical whitelist tag
    _CANONICAL_TAGS = {
        # τραπέζι variants
        'τραπεζάκι': 'τραπέζι', 'τραπεζακι': 'τραπέζι',
        'τραπεζαρία': 'τραπέζι', 'τραπεζαρια': 'τραπέζι',
        'τραπέζια': 'τραπέζι', 'τραπεζια': 'τραπέζι',
        'βοηθητικό τραπέζι': 'τραπέζι', 'βοηθητικο τραπεζι': 'τραπέζι',
        'επιφάνεια τραπεζιού': 'τραπέζι',
        # καναπές variants
        'καναπεδάκι': 'καναπές', 'καναπεδακι': 'καναπές',
        'γωνιακός καναπές': 'καναπές', 'γωνιακο καναπε': 'καναπές',
        'γωνιακό καναπέ': 'καναπές',
        # καρέκλα variants
        'καρεκλάκι': 'καρέκλα', 'καρεκλακι': 'καρέκλα',
        'καρέκλα γραφείου': 'καρέκλα',
        # κρεβάτι variants
        'κρεβατοκάμαρα': 'κρεβάτι', 'κρεβατοκαμαρα': 'κρεβάτι',
        'κρεβατάκι': 'κρεβάτι', 'κρεβατακι': 'κρεβάτι',
        'κρεβατιού': 'κρεβάτι',
        'κρεβάτια': 'κρεβάτι', 'κρεβατια': 'κρεβάτι',
        # plural category forms (from supplier XML categories)
        'καναπέδες': 'καναπές', 'καναπεδες': 'καναπές',
        'τραπέζια': 'τραπέζι', 'τραπεζια': 'τραπέζι',
        'καρέκλες': 'καρέκλα', 'καρεκλες': 'καρέκλα',
        'ντουλάπες': 'ντουλάπα', 'ντουλαπες': 'ντουλάπα',
        'βιβλιοθήκες': 'βιβλιοθήκη', 'βιβλιοθηκες': 'βιβλιοθήκη',
        'κομοδίνα': 'κομοδίνο', 'κομοδινα': 'κομοδίνο',
        'καθρέφτες': 'καθρέφτης', 'καθρεφτες': 'καθρέφτης',
        'σομιέδες': 'σομιέ', 'σομιεδες': 'σομιέ',
        'στρώματα': 'στρώμα', 'στρωματα': 'στρώμα',
        'χαλιά': 'χαλί', 'χαλια': 'χαλί',
        'φωτιστικά': 'φωτιστικό', 'φωτιστικα': 'φωτιστικό',
        # ντουλάπα variants
        'ντουλάπι': 'ντουλάπα', 'ντουλαπι': 'ντουλάπα',
        'ντουλαπάκι': 'ντουλάπα', 'ντουλαπακι': 'ντουλάπα',
        # συρταριέρα
        'συρταριερα': 'συρταριέρα',
        # κομόδιο → κομοδίνο (κομόδιο δεν είναι στη whitelist)
        'κομόδιο': 'κομοδίνο', 'κομοδιο': 'κομοδίνο',
        # σκαμπό / παγκάκι variants
        'σκαμνός': 'σκαμπό', 'σκαμνος': 'σκαμπό',
        'σκαμνάκι': 'σκαμπό', 'σκαμνακι': 'σκαμπό',
        'σκαμπώ': 'σκαμπό',
        'παγκακι': 'παγκάκι',
        'σκαμπό-παπουτσοθήκη': 'παπουτσοθήκη',
        # παπουτσοθήκη typo (latin o)
        'παπουτσoθήκη': 'παπουτσοθήκη',
        # καθρέφτης
        'καθρέπτης': 'καθρέφτης', 'καθρεπτης': 'καθρέφτης',
        # ραφιέρα / ράφι → ραφιέρα
        'ραφι': 'ραφιέρα', 'ράφι': 'ραφιέρα',
        # φωτιστικό
        'φως': 'φωτιστικό',
        'παιδικά φωτιστικά οροφής': 'φωτιστικό',
        # βάζο
        'βαζάκι': 'βάζο', 'βαζακι': 'βάζο',
        'βάζο': 'βάζο', 'βαζο': 'βάζο', 'βαζ': 'βάζο',
        # γραφείο
        'επιπλα γραφείου': 'γραφείο', 'γραφείου': 'γραφείο',
        # κονσόλα / επιπλα εισόδων
        'επιπλα εισόδων': 'κονσόλα',
        # σεζλόνγκ
        'ξαπλώστρα': 'ξαπλώστρα', 'σεζλογκ': 'σεζλόνγκ',
        # τηλεόραση → έπιπλο τηλεόρασης
        'τηλεόραση': 'έπιπλο τηλεόρασης', 'τηλεορασης': 'έπιπλο τηλεόρασης',
        'έπιπλο τηλεορασης': 'έπιπλο τηλεόρασης',
        # αιώρα / κούνια
        'κουνια': 'αιώρα', 'κούνια': 'αιώρα',
    }

    def _enforce_whitelist(self, tag: str) -> Optional[str]:
        """Return the canonical whitelist tag for the input, or None."""
        if not tag:
            return None
        # 1. Direct whitelist match
        if tag in self.WHITELIST:
            return tag
        # 2. Canonical map (full phrase or single-word variant)
        mapped = self._CANONICAL_TAGS.get(tag)
        if mapped and mapped in self.WHITELIST:
            return mapped
        # 3. Token scan: find the first token that is in the whitelist
        for token in tag.split():
            if token in self.WHITELIST:
                return token
            mapped = self._CANONICAL_TAGS.get(token)
            if mapped and mapped in self.WHITELIST:
                return mapped
        return None

    def _clean_tag(self, tag: str) -> str:
        """Clean and normalize a tag"""
        # Convert to lowercase
        tag = tag.lower()

        # Remove special characters except Greek letters, spaces, and hyphens
        tag = re.sub(r'[^\u0370-\u03FF\u1F00-\u1FFF\sa-z0-9-]', '', tag)

        # Normalize whitespace
        tag = ' '.join(tag.split())

        # Remove leading/trailing hyphens
        tag = tag.strip('- ')

        # Map to canonical form if a derivative/compound was generated
        tag = self._CANONICAL_TAGS.get(tag, tag)

        return tag

    def _is_valid_tag(self, tag: str) -> bool:
        """Check if a tag is valid"""
        if not tag or len(tag) < 2:
            return False

        if len(tag) > 50:  # Too long
            return False

        # Must contain at least one Greek letter or Latin letter
        if not re.search(r'[\u0370-\u03FF\u1F00-\u1FFFa-z]', tag):
            return False

        # Exclude numeric-only tags
        if tag.isdigit():
            return False

        return True
