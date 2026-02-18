# XML Files Directory Structure

This directory contains all XML files used by the AI processor.

## Directory Structure

```
xml_files/
├── en/              # English XML files from suppliers
│   ├── pakoworld-en.xml
│   ├── libertab2b.xml
│   ├── estiahomeart.xml
│   └── b2bmarkt-en.xml
│
├── gr/              # Greek XML files from suppliers
│   ├── pakoworld.xml
│   └── pakoworld-enhanced.xml
│
├── enhanced/        # AI-enhanced output files (generated)
│   └── [supplier]-enhanced.xml
│
└── xml_urls.txt     # List of XML URLs for downloading

## Processing Workflow

### Greek XML Processing (Direct)
Input: `gr/[supplier].xml`
Process: AI enhancement in Greek
Output: `enhanced/[supplier]-enhanced.xml`

### English XML Processing (Translation)
Input: `en/[supplier]-en.xml`
Process:
1. AI enhancement in English (Llama 3.1)
2. Translation to Greek (Krikri)
Output: `enhanced/[supplier]-enhanced.xml`

## Usage

### Process English XML
```bash
python3 main.py \
  --mode process \
  --language en \
  --input scripts/xml_files/en/pakoworld-en.xml \
  --limit 100 \
  --skip-images
```

### Process Greek XML
```bash
python3 main.py \
  --mode process \
  --language el \
  --input scripts/xml_files/gr/pakoworld.xml \
  --limit 100 \
  --skip-images
```

## Environment Configuration

The XML paths are configured in `.env`:

```env
XML_INPUT_DIR=/path/to/scripts/xml_files
XML_OUTPUT_DIR=/path/to/scripts/xml_files/enhanced
```

For local development, use `.env.local` with your local paths.

## Notes

- English XML files should be placed in `en/`
- Greek XML files should be placed in `gr/`
- Enhanced output is automatically saved to `enhanced/`
- The `xml_urls.txt` contains download URLs for all suppliers
