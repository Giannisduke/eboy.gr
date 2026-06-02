# AI-Powered Product XML Processor

Αυτόματη επεξεργασία XML feeds προϊόντων με χρήση τοπικού AI (Ollama) για:
- Βελτιστοποίηση τίτλων (SEO-friendly, 50-60 χαρακτήρες)
- Δημιουργία πειστικών περιγραφών (250-350 λέξεις)
- Αυτόματη παραγωγή tags (10-15 tags)
- Αντιστοίχιση σε 9 WooCommerce κατηγορίες
- Βελτιστοποίηση εικόνων (resize, compress, WebP)

## Εγκατάσταση

### 1. Ollama (στο VM)

```bash
# Χρησιμοποιήστε το Ansible role που δημιουργήσαμε
cd /Users/eboy/sites/eboy.gr/trellis
ansible-playbook server.yml -e env=staging --tags=ollama

# Ή manually για testing
curl https://ollama.com/download/ollama-linux-amd64 -o /tmp/ollama
sudo mv /tmp/ollama /usr/local/bin/ollama
sudo chmod +x /usr/local/bin/ollama

# Start Ollama
ollama serve &

# Pull model
ollama pull mistral:7b-instruct-q4_K_M
```

### 2. Python Dependencies

```bash
cd /Users/eboy/sites/eboy.gr/scripts/product-ai-processor

# Create virtual environment (recommended)
python3 -m venv venv
source venv/bin/activate

# Install dependencies
pip install -r requirements.txt
```

### 3. Configuration

```bash
# Copy example env file
cp .env.example .env

# Edit paths for your environment
nano .env
```

## Χρήση

### Δοκιμή με περιορισμένα προϊόντα

```bash
python3 main.py --mode test --input ../site/web/app/xml_files/pakoworld.xml --limit 10 --skip-images
```

### Δημιουργία Category Mappings για Review

```bash
# Βήμα 1: Παραγωγή mappings
python3 main.py --mode generate-mappings

# Βήμα 2: Έλεγχος και διόρθωση
nano output/mappings-review.yaml

# Βήμα 3: Αποθήκευση διορθωμένων mappings
cp output/mappings-review.yaml config/mappings-final.yaml
```

### Πλήρης Επεξεργασία

```bash
# Όλα τα XML files
python3 main.py --mode process --use-reviewed-mappings

# Συγκεκριμένο file
python3 main.py --mode process --input ../site/web/app/xml_files/pakoworld.xml --output output/enhanced_pakoworld.xml

# Με περιορισμό προϊόντων
python3 main.py --mode process --limit 100
```

### Debug Mode

```bash
python3 main.py --mode test --input ../site/web/app/xml_files/pakoworld.xml --limit 5 --debug
```

## Δομή Project

```
product-ai-processor/
├── main.py                 # CLI entry point
├── requirements.txt        # Python dependencies
├── .env.example           # Environment variables template
├── config/
│   ├── prompts.yaml       # AI prompts για κάθε task
│   ├── categories.yaml    # 9 WooCommerce κατηγορίες
│   └── mappings-final.yaml # Reviewed category mappings
├── src/
│   ├── ai_client.py       # Ollama API client
│   ├── xml_processor.py   # Main orchestrator
│   ├── title_optimizer.py
│   ├── description_enhancer.py
│   ├── tag_generator.py
│   ├── category_mapper.py
│   └── image_optimizer.py
├── output/
│   └── mappings-review.yaml  # Generated mappings για review
├── logs/
│   └── processor.log
└── tests/
```

## Workflow

### 1. Πρώτη Εκτέλεση - Category Mapping

```bash
# Παραγωγή category mappings από όλα τα XMLs
python3 main.py --mode generate-mappings

# Review και διόρθωση
nano output/mappings-review.yaml

# Αλλαγή status για διορθωμένα mappings:
# status: "needs_review" → status: "manual_corrected"

# Αποθήκευση
cp output/mappings-review.yaml config/mappings-final.yaml
```

### 2. Test Run

```bash
# Δοκιμή με 10 προϊόντα, χωρίς εικόνες
python3 main.py --mode test \
    --input ../site/web/app/xml_files/pakoworld.xml \
    --limit 10 \
    --skip-images \
    --use-reviewed-mappings
```

### 3. Production Run

```bash
# Πλήρης επεξεργασία
python3 main.py --mode process --use-reviewed-mappings
```

## Automated Cron Job

Μετά την επιτυχή δοκιμή, προσθέστε στο cron:

```bash
# Κάθε μέρα στις 2:00 π.μ.
0 2 * * * cd /path/to/scripts/product-ai-processor && /path/to/venv/bin/python3 main.py --mode process --use-reviewed-mappings >> logs/cron.log 2>&1
```

## Troubleshooting

### Ollama δεν απαντά

```bash
# Check if running
curl http://127.0.0.1:11434/api/version

# Check logs
tail -f /var/log/ollama/ollama.log

# Restart
sudo systemctl restart ollama
```

### AI responses είναι κακής ποιότητας

1. Ελέγξτε το prompt στο `config/prompts.yaml`
2. Προσαρμόστε την temperature (0.2-0.8)
3. Δοκιμάστε διαφορετικό model (mistral vs llama)

### Αργή επεξεργασία

1. Μειώστε batch size
2. Skip images για testing
3. Χρησιμοποιήστε μικρότερο model (Q4 vs Q5)
4. Αυξήστε concurrent image downloads

### Memory issues

1. Μειώστε `php_fpm_max_children` στο staging/main.yml
2. Μειώστε `ollama_max_loaded_models` σε 1
3. Προσθέστε swap space

## Performance

Με 8GB RAM VM:
- **Ollama (Mistral Q4)**: ~2GB RAM
- **Python processor**: ~500MB RAM
- **Processing time**: ~2-3 seconds per product
- **1000 προϊόντα**: ~1-2 ώρες

## Output

Τα enhanced XML περιέχουν:
- Βελτιστοποιημένο τίτλο
- AI-generated περιγραφή σε HTML
- 10-15 tags
- WooCommerce κατηγορία
- Processed image paths (local)

Παράδειγμα:

```xml
<product>
    <name>Παπουτσοθήκη SANTE Pakoworld 20 Ζεύγων Sonoma</name>
    <woo_category>Έπιπλο</woo_category>
    <tags>παπουτσοθήκη, χωλ, οργάνωση, ξύλο, sonoma, αποθήκευση, 20 ζευγάρια</tags>
    <description><![CDATA[
        <p>Ανακαλύψτε την τέλεια λύση οργάνωσης για το χώρο εισόδου...</p>
        <ul>
            <li>Χωρητικότητα 20 ζευγαριών</li>
            <li>Κατασκευή από MDF με επένδυση sonoma</li>
            ...
        </ul>
    ]]></description>
    <processed_main_image>/path/to/uploads/ai-processed-images/pakoworld/SKU/main_large.jpg</processed_main_image>
</product>
```

## Επόμενα Βήματα

1. Test Ollama installation
2. Run category mapping generation
3. Review and correct mappings
4. Test with 10-20 products
5. Full run on staging
6. Setup cron automation
7. Monitor logs and performance
