#!/usr/bin/env python3
"""
Single-product category mapping CLI.

Used by recategorize-orphans-cli.php to ask the AI which whitelisted
WooCommerce category a product belongs to, given its current category
name (parasitic/raw) and its product title.

Usage:
    python3 categorize_one_cli.py "<parasitic_cat>" "<product_title>"

Output (stdout, JSON):
    {"category": "Διακόσμηση", "confidence": 0.85}

Exit codes:
    0 = success (JSON on stdout)
    1 = failure (error message on stderr)
"""
import json
import logging
import sys
from pathlib import Path

import yaml

SCRIPT_DIR = Path(__file__).resolve().parent
sys.path.insert(0, str(SCRIPT_DIR))

from src.ai_client import OllamaClient  # noqa: E402
from src.category_mapper import CategoryMapper  # noqa: E402


def main() -> int:
    if len(sys.argv) < 2:
        print("Usage: categorize_one_cli.py <parasitic_cat> [<product_title>]", file=sys.stderr)
        return 1

    parasitic_cat = sys.argv[1]
    product_title = sys.argv[2] if len(sys.argv) >= 3 else ""

    logging.basicConfig(level=logging.WARNING)

    # Load configs
    with open(SCRIPT_DIR / "config" / "prompts.yaml", encoding="utf-8") as f:
        prompts = yaml.safe_load(f)
    with open(SCRIPT_DIR / "config" / "categories.yaml", encoding="utf-8") as f:
        categories = yaml.safe_load(f)

    # Read env (REMBG/OLLAMA host etc.) from .env files — same pattern as rembg_run.sh.
    import os
    for env_file in [SCRIPT_DIR / ".env", SCRIPT_DIR / ".env.local"]:
        if env_file.exists():
            for line in env_file.read_text(encoding="utf-8").splitlines():
                line = line.strip()
                if not line or line.startswith("#") or "=" not in line:
                    continue
                key, _, value = line.partition("=")
                os.environ.setdefault(key.strip(), value.strip().strip('"').strip("'"))

    # Build AI client with the model from .env (OllamaClient hard-codes a
    # default mistral model that isn't installed on the user's Ollama server).
    model = os.environ.get("OLLAMA_MODEL", "ilsp/Llama-Krikri-8B-Instruct:latest")
    port  = int(os.environ.get("OLLAMA_PORT", "11434"))
    ai = OllamaClient(model=model, port=port)
    mapper = CategoryMapper(ai, prompts, categories)

    try:
        woo_cat, confidence, _ = mapper.map_category(parasitic_cat, product_title)
    except Exception as e:
        print(f"AI mapping failed: {e}", file=sys.stderr)
        return 1

    # Validate against whitelist (defensive — CategoryMapper should already enforce this)
    allowed = {c["name"] for c in categories.get("categories", [])}
    if woo_cat not in allowed:
        print(
            f"AI returned non-whitelisted category '{woo_cat}' for '{parasitic_cat}' / '{product_title}'",
            file=sys.stderr,
        )
        return 1

    print(json.dumps({"category": woo_cat, "confidence": confidence}, ensure_ascii=False))
    return 0


if __name__ == "__main__":
    sys.exit(main())
