# Testing Guide

## Quick Start

```bash
cd /Users/eboy/sites/eboy.gr/scripts/product-ai-processor

# Run the test script
./test.sh
```

## Manual Testing

### 1. Check Ollama

```bash
# Verify Ollama is running
curl http://127.0.0.1:11434/api/version

# List installed models
ollama list

# Should show:
# mistral:7b-instruct-q4_K_M
```

### 2. Test with 3 Products (Fast)

```bash
source venv/bin/activate

python3 main.py --mode test \
    --input ../../site/web/app/xml_files/pakoworld.xml \
    --limit 3 \
    --skip-images \
    --debug
```

**What it does:**
- Processes 3 products from Pakoworld XML
- Skips image downloading (faster)
- Shows debug output
- Saves to: `output/test-output.xml`

### 3. Test with Images (Slower)

```bash
python3 main.py --mode test \
    --input ../../site/web/app/xml_files/pakoworld.xml \
    --limit 3
```

**What it does:**
- Downloads and optimizes images
- Saves to: `../../site/web/app/uploads/ai-processed-images/`

### 4. Generate Category Mappings

```bash
python3 main.py --mode generate-mappings
```

**What it does:**
- Analyzes all XML files
- Generates category mappings
- Saves to: `output/mappings-review.yaml`

### 5. Review and Test Mappings

```bash
# Review the mappings
cat output/mappings-review.yaml

# Edit if needed
nano output/mappings-review.yaml

# Save corrected mappings
cp output/mappings-review.yaml config/mappings-final.yaml

# Test with corrected mappings
python3 main.py --mode test \
    --input ../../site/web/app/xml_files/pakoworld.xml \
    --limit 10 \
    --use-reviewed-mappings \
    --skip-images
```

## Verify Results

### Check Enhanced XML

```bash
# View enhanced XML
cat output/test-output.xml | less

# Search for specific enhancements
grep "<woo_category>" output/test-output.xml
grep "<tags>" output/test-output.xml
```

### Check Logs

```bash
# View logs
cat logs/processor.log

# Follow logs in real-time
tail -f logs/processor.log
```

### What to Look For

**Title Optimization:**
- Original: "Παπουτσοθήκη-ντουλάπι SANTE pakoworld 20 ζεύγων χρώμα sonoma 80x37x123εκ"
- Optimized: "Παπουτσοθήκη SANTE Pakoworld 20 Ζεύγων Sonoma"
- Length: 50-60 characters

**Description:**
- Well-structured HTML
- 250-350 words
- Includes features, specifications, call-to-action

**Tags:**
- 10-15 tags
- Lowercase Greek
- Relevant (e.g., "παπουτσοθήκη", "χωλ", "οργάνωση", "ξύλο")

**Category:**
- One of 9 main categories
- "Έπιπλο", "Γραφείο", "Κήπος", etc.

**Images (if not skipped):**
- Downloaded locally
- Multiple sizes (full, large, medium, thumbnail)
- WebP + JPEG formats

## Troubleshooting

### Ollama Not Responding

```bash
# Check if running
curl http://127.0.0.1:11434/api/version

# Restart Ollama
pkill ollama
ollama serve &

# Or use macOS app
# Open /Applications/Ollama.app
```

### Import Errors

```bash
# Reinstall dependencies
source venv/bin/activate
pip install -r requirements.txt
```

### Model Not Found

```bash
# Check models
ollama list

# Pull model if missing
ollama pull mistral:7b-instruct-q4_K_M
```

### Slow Performance

```bash
# Test with fewer products
python3 main.py --mode test --limit 1 --skip-images

# Check system resources
top
# Look for ollama process - should use ~2GB RAM
```

## Performance Benchmarks

Expected processing time per product:

| Task | Time |
|------|------|
| Title optimization | ~0.5-1s |
| Category mapping | ~0.5s |
| Description enhancement | ~2-3s |
| Tag generation | ~1-2s |
| Image download + optimization | ~3-5s |
| **Total per product (with images)** | **~7-12s** |
| **Total per product (no images)** | **~4-7s** |

For 3 products (no images): **~15-20 seconds**

## Next Steps After Testing

1. **Review Results**: Check `output/test-output.xml`
2. **Generate Mappings**: Run `--mode generate-mappings`
3. **Review Mappings**: Edit `output/mappings-review.yaml`
4. **Full Test**: Process 50-100 products with mappings
5. **Production**: Process all XMLs
6. **Automate**: Setup cron job

## Common Commands

```bash
# Quick test (3 products, no images)
./test.sh

# Test specific supplier
python3 main.py --mode test --input ../../site/web/app/xml_files/b2bmarkt.xml --limit 5 --skip-images

# Generate mappings
python3 main.py --mode generate-mappings

# Full processing (all XMLs)
python3 main.py --mode process --use-reviewed-mappings

# Process with limit
python3 main.py --mode process --limit 100 --use-reviewed-mappings
```
