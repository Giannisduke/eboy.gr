#!/usr/bin/env python3
"""
AI-Powered Product XML Processor
Main entry point for processing product XML feeds with AI enhancements.
"""

import sys
import argparse
import logging
from pathlib import Path
import yaml
from dotenv import load_dotenv
import os

# Add src to path
sys.path.insert(0, str(Path(__file__).parent / 'src'))

from src.xml_processor import XMLProcessor
from src.ai_client import OllamaClient


def setup_logging(debug: bool = False):
    """Configure logging"""
    level = logging.DEBUG if debug else logging.INFO
    logging.basicConfig(
        level=level,
        format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
        handlers=[
            logging.StreamHandler(),
            logging.FileHandler('logs/processor.log', encoding='utf-8')
        ]
    )


def load_config(language: str = 'el') -> dict:
    """Load configuration from .env and YAML files"""
    # Load environment variables
    # Try .env.local first (for local development), then .env
    env_local = Path(__file__).parent / '.env.local'
    env_file = Path(__file__).parent / '.env'

    if env_local.exists():
        load_dotenv(env_local, override=False)
        print(f"✓ Loaded configuration from .env.local")
    elif env_file.exists():
        load_dotenv(env_file, override=False)
        print(f"✓ Loaded configuration from .env")
    else:
        print(f"⚠ No .env or .env.local file found")

    # Load prompts based on language
    if language == 'en':
        prompts_path = Path(__file__).parent / 'config' / 'prompts_en.yaml'
        prompts_translation_path = Path(__file__).parent / 'config' / 'prompts_translation.yaml'
        with open(prompts_translation_path, 'r', encoding='utf-8') as f:
            translation_prompts = yaml.safe_load(f)
    else:
        prompts_path = Path(__file__).parent / 'config' / 'prompts.yaml'
        translation_prompts = None

    with open(prompts_path, 'r', encoding='utf-8') as f:
        prompts = yaml.safe_load(f)

    # Load categories
    categories_path = Path(__file__).parent / 'config' / 'categories.yaml'
    with open(categories_path, 'r', encoding='utf-8') as f:
        categories = yaml.safe_load(f)

    # Load suppliers configuration
    suppliers_path = Path(__file__).parent / 'config' / 'suppliers.yaml'
    with open(suppliers_path, 'r', encoding='utf-8') as f:
        suppliers = yaml.safe_load(f)

    # Set models based on language
    if language == 'en':
        ai_model = os.getenv('AI_MODEL_EN', 'mistral:7b-instruct-q4_K_M')
        translation_model = os.getenv('TRANSLATION_MODEL', 'ilsp/Llama-Krikri-8B-Instruct:latest')
    else:
        ai_model = os.getenv('OLLAMA_MODEL', 'ilsp/Llama-Krikri-8B-Instruct:latest')
        translation_model = None

    config = {
        'language': language,
        'ollama_host': os.getenv('OLLAMA_HOST', '127.0.0.1'),
        'ollama_port': int(os.getenv('OLLAMA_PORT', 11434)),
        'ai_model': ai_model,
        'translation_model': translation_model,
        'xml_input_dir': Path(os.getenv('XML_INPUT_DIR', '../site/web/app/xml_files')),
        'xml_output_dir': Path(os.getenv('XML_OUTPUT_DIR', '../site/web/app/xml_files/enhanced')),
        'image_output_dir': Path(os.getenv('IMAGE_OUTPUT_DIR', '../site/web/app/uploads/ai-processed-images')),
        'max_concurrent_images': int(os.getenv('MAX_CONCURRENT_IMAGES', 5)),
        'prompts': prompts,
        'translation_prompts': translation_prompts,
        'categories': categories,
        'suppliers': suppliers,
        'debug': os.getenv('DEBUG', 'false').lower() == 'true',
        # WooCommerce Auto-Import
        'woo_auto_import': os.getenv('WOO_AUTO_IMPORT', 'false').lower() == 'true',
        'woo_url': os.getenv('WOO_URL', ''),
        'woo_consumer_key': os.getenv('WOO_CONSUMER_KEY', ''),
        'woo_consumer_secret': os.getenv('WOO_CONSUMER_SECRET', ''),
        # Image background removal
        'remove_bg': os.getenv('REMOVE_BG', 'false').lower() == 'true',
        'bg_threshold': int(os.getenv('BG_THRESHOLD', 240))
    }

    return config


def check_ollama_connection(config: dict) -> bool:
    """Check if Ollama is running and models are available"""
    client = OllamaClient(
        host=config['ollama_host'],
        port=config['ollama_port'],
        model=config['ai_model']
    )

    if not client.health_check():
        print(f"❌ Cannot connect to Ollama at {config['ollama_host']}:{config['ollama_port']}")
        print("   Make sure Ollama is running:")
        print(f"   curl http://{config['ollama_host']}:{config['ollama_port']}/api/version")
        return False

    print(f"✅ Connected to Ollama")

    # Check if AI model is available
    models = client.list_models()
    if config['ai_model'] not in models:
        print(f"⚠️  AI Model {config['ai_model']} not found")
        print(f"   Available models: {', '.join(models) if models else 'none'}")
        print(f"   Run: ollama pull {config['ai_model']}")
        return False

    print(f"✅ AI Model {config['ai_model']} is available")

    # Check translation model if in English mode
    if config['language'] == 'en' and config['translation_model']:
        if config['translation_model'] not in models:
            print(f"⚠️  Translation Model {config['translation_model']} not found")
            print(f"   Run: ollama pull {config['translation_model']}")
            return False
        print(f"✅ Translation Model {config['translation_model']} is available")

    return True


def main():
    parser = argparse.ArgumentParser(description='AI-Powered Product XML Processor')

    parser.add_argument(
        '--mode',
        choices=['process', 'generate-mappings', 'test'],
        default='process',
        help='Processing mode'
    )

    parser.add_argument(
        '--input',
        type=Path,
        help='Input XML file (for process/test modes)'
    )

    parser.add_argument(
        '--output',
        type=Path,
        help='Output XML file (for process/test modes)'
    )

    parser.add_argument(
        '--limit',
        type=int,
        help='Limit number of products to process (for testing)'
    )

    parser.add_argument(
        '--skus',
        type=str,
        help='Comma-separated list of SKUs to process (for incremental updates)'
    )

    parser.add_argument(
        '--skip-images',
        action='store_true',
        help='Skip image processing (faster for testing)'
    )

    parser.add_argument(
        '--remove-bg',
        action='store_true',
        help='Remove white backgrounds from product images and save as transparent WebP'
    )

    parser.add_argument(
        '--bg-threshold',
        type=int,
        default=230,
        help='RGB threshold (0-255) for white background detection (default: 240)'
    )

    parser.add_argument(
        '--extend-from-backup',
        action='store_true',
        help='Extend from backup file (copy existing products as-is, add new ones)'
    )

    parser.add_argument(
        '--use-reviewed-mappings',
        action='store_true',
        help='Use manually reviewed category mappings'
    )

    parser.add_argument(
        '--language',
        choices=['el', 'en'],
        default='el',
        help='Source language (el=Greek direct, en=English with translation)'
    )

    parser.add_argument(
        '--debug',
        action='store_true',
        help='Enable debug logging'
    )

    args = parser.parse_args()

    # Setup
    setup_logging(args.debug)
    logger = logging.getLogger(__name__)

    logger.info("=" * 60)
    logger.info("AI-Powered Product XML Processor")
    logger.info("=" * 60)

    # Load configuration
    try:
        config = load_config(language=args.language)
        if args.debug:
            config['debug'] = True

        logger.info(f"Language mode: {config['language']}")
        logger.info(f"AI Model: {config['ai_model']}")
        if config['translation_model']:
            logger.info(f"Translation Model: {config['translation_model']}")
    except Exception as e:
        logger.error(f"Failed to load configuration: {str(e)}")
        return 1

    # Check Ollama connection
    if not check_ollama_connection(config):
        logger.error("Ollama is not available. Exiting.")
        return 1

    # Initialize processor
    config['skip_images'] = args.skip_images
    if args.remove_bg:
        config['remove_bg'] = True
    config['bg_threshold'] = args.bg_threshold
    processor = XMLProcessor(config)

    # Load reviewed mappings if requested
    if args.use_reviewed_mappings:
        reviewed_file = Path(__file__).parent / 'config' / 'mappings-final.yaml'
        if reviewed_file.exists():
            processor.category_mapper.load_reviewed_mappings(reviewed_file)
            logger.info("✅ Loaded reviewed category mappings")
        else:
            logger.warning(f"Reviewed mappings file not found: {reviewed_file}")

    # Execute based on mode
    if args.mode == 'generate-mappings':
        logger.info("Mode: Generate Category Mappings")

        # Find all XML files
        xml_files = list(config['xml_input_dir'].glob('*.xml'))
        logger.info(f"Found {len(xml_files)} XML files")

        output_file = Path(__file__).parent / 'output' / 'mappings-review.yaml'
        output_file.parent.mkdir(parents=True, exist_ok=True)

        processor.generate_category_mappings(xml_files, output_file)

        print("\n" + "=" * 60)
        print(f"✅ Category mappings generated: {output_file}")
        print("=" * 60)
        print("\nNext steps:")
        print("1. Review the generated mappings file")
        print("2. Correct any incorrect mappings")
        print("3. Change status from 'needs_review' to 'manual_corrected' for corrections")
        print("4. Save corrected mappings to: config/mappings-final.yaml")
        print("5. Re-run with --use-reviewed-mappings flag")

    elif args.mode == 'test':
        logger.info("Mode: Test (limited processing)")

        if not args.input:
            logger.error("--input required for test mode")
            return 1

        if not args.input.exists():
            logger.error(f"Input file not found: {args.input}")
            return 1

        output = args.output or (Path(__file__).parent / 'output' / 'test-output.xml')
        output.parent.mkdir(parents=True, exist_ok=True)

        limit = args.limit or 5
        logger.info(f"Processing {limit} products from {args.input}")

        stats = processor.process_xml_file(
            args.input,
            output,
            limit=limit,
            skip_images=args.skip_images
        )

        print("\n" + "=" * 60)
        print("Processing Statistics:")
        print(f"  Total products: {stats['total']}")
        print(f"  Successfully processed: {stats['processed']}")
        print(f"  Failed: {stats['failed']}")
        print(f"  Output: {output}")
        print("=" * 60)

    elif args.mode == 'process':
        logger.info("Mode: Full Processing")

        if args.input:
            # Process single file
            xml_files = [args.input]
        else:
            # Process all XML files
            xml_files = list(config['xml_input_dir'].glob('*.xml'))

        if not xml_files:
            logger.error(f"No XML files found in {config['xml_input_dir']}")
            return 1

        logger.info(f"Processing {len(xml_files)} XML file(s)")

        for xml_file in xml_files:
            logger.info(f"\nProcessing: {xml_file.name}")

            if args.output:
                output = args.output
            else:
                output = config['xml_output_dir'] / f"enhanced_{xml_file.name}"

            output.parent.mkdir(parents=True, exist_ok=True)

            # Parse SKU filter if provided
            sku_filter = None
            if args.skus:
                sku_filter = [sku.strip() for sku in args.skus.split(',')]
                logger.info(f"SKU filter: {len(sku_filter)} SKUs")

            stats = processor.process_xml_file(
                xml_file,
                output,
                limit=args.limit,
                skip_images=args.skip_images,
                sku_filter=sku_filter,
                extend_from_backup=args.extend_from_backup
            )

            print(f"\n✅ Processed {xml_file.name}:")
            print(f"   Total: {stats['total']}, Success: {stats['processed']}, Failed: {stats['failed']}")
            print(f"   Output: {output}")

    return 0


if __name__ == '__main__':
    sys.exit(main())
