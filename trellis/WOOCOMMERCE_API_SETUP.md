# WooCommerce API Setup για AI Processor

Τα WooCommerce API credentials είναι διαφορετικά για κάθε περιβάλλον και αποθηκεύονται στα encrypted vault files.

## Βήμα 1: Δημιουργία WooCommerce API Keys

Για κάθε περιβάλλον (development, staging, production):

1. Πήγαινε στο WordPress Admin
2. WooCommerce → Settings → Advanced → REST API
3. Πάτα "Add key"
4. Ρυθμίσεις:
   - Description: `AI Product Processor`
   - User: Διαχειριστής
   - Permissions: `Read/Write`
5. Αντίγραψε το **Consumer key** και **Consumer secret**

## Βήμα 2: Προσθήκη credentials στα Vault files

### Development
```bash
cd /Users/eboy/sites/eboy.gr/trellis
ansible-vault edit group_vars/development/vault.yml
```

Πρόσθεσε στο τέλος του αρχείου:
```yaml
# WooCommerce API - Development
vault_woo_dev_url: "http://eboy.test"
vault_woo_dev_consumer_key: "ck_xxxxxxxxxxxxxxxxxxxxx"
vault_woo_dev_consumer_secret: "cs_xxxxxxxxxxxxxxxxxxxxx"
```

### Staging
```bash
ansible-vault edit group_vars/staging/vault.yml
```

Πρόσθεσε:
```yaml
# WooCommerce API - Staging
vault_woo_staging_url: "https://staging.simple-city.gr"
vault_woo_staging_consumer_key: "ck_xxxxxxxxxxxxxxxxxxxxx"
vault_woo_staging_consumer_secret: "cs_xxxxxxxxxxxxxxxxxxxxx"
```

### Production
```bash
ansible-vault edit group_vars/production/vault.yml
```

Πρόσθεσε:
```yaml
# WooCommerce API - Production
vault_woo_production_url: "https://simple-city.gr"
vault_woo_production_consumer_key: "ck_xxxxxxxxxxxxxxxxxxxxx"
vault_woo_production_consumer_secret: "cs_xxxxxxxxxxxxxxxxxxxxx"
```

## Βήμα 3: Δημιουργία Ansible template για .env

Δημιούργησε το αρχείο `trellis/roles/ai-processor/templates/env.j2`:

```bash
# Ollama Configuration
OLLAMA_HOST={{ ai_processor.ollama_host }}
OLLAMA_PORT={{ ai_processor.ollama_port }}
OLLAMA_MODEL={{ ai_processor.ai_model_greek }}

# English mode configuration
AI_MODEL_EN={{ ai_processor.ai_model_english }}
TRANSLATION_MODEL={{ ai_processor.translation_model }}

# File Paths
XML_INPUT_DIR={{ www_root }}/web/app/plugins/eboy-product-importer/data/xml_files
XML_OUTPUT_DIR={{ www_root }}/web/app/plugins/eboy-product-importer/data/xml_files/enhanced
IMAGE_OUTPUT_DIR={{ www_root }}/web/app/uploads/ai-processed-images
LOG_DIR={{ www_root }}/web/app/plugins/eboy-product-importer/product-ai-processor/logs

# Processing Configuration
BATCH_SIZE={{ ai_processor.batch_size }}
MAX_CONCURRENT_IMAGES={{ ai_processor.max_concurrent_images }}
IMAGE_QUALITY={{ ai_processor.image_quality }}
IMAGE_MAX_WIDTH={{ ai_processor.image_max_width }}
IMAGE_MAX_HEIGHT={{ ai_processor.image_max_height }}

# WooCommerce Auto-Import
WOO_AUTO_IMPORT={{ ai_processor.woo_auto_import | lower }}
WOO_URL={{ ai_processor.woo_url }}
WOO_CONSUMER_KEY={{ ai_processor.woo_consumer_key }}
WOO_CONSUMER_SECRET={{ ai_processor.woo_consumer_secret }}

# Debug
DEBUG={{ 'true' if ai_processor.debug | default(false) else 'false' }}
```

Πρόσθεσε στο `roles/ai-processor/tasks/main.yml`:

```yaml
- name: Deploy .env file for AI processor
  template:
    src: env.j2
    dest: "{{ www_root }}/web/app/plugins/eboy-product-importer/product-ai-processor/.env"
    owner: "{{ web_user }}"
    group: "{{ web_group }}"
    mode: '0640'
  tags: [ai-processor, env]
```

## Βήμα 4: Deploy με Ansible

```bash
# Για development
ansible-playbook server.yml -e env=development --tags ai-processor

# Για staging
ansible-playbook server.yml -e env=staging --tags ai-processor

# Για production
ansible-playbook server.yml -e env=production --tags ai-processor
```

## Βήμα 5: Ενεργοποίηση Auto-Import

Για να ενεργοποιήσεις το auto-import σε κάποιο περιβάλλον:

1. Άνοιξε το `group_vars/{environment}/ai_processor.yml`
2. Άλλαξε το `woo_auto_import: false` σε `woo_auto_import: true`
3. Deploy με Ansible

**Προσοχή:** Στο production είναι ήδη ενεργοποιημένο!

## Testing WooCommerce Connection

```bash
cd /srv/www/eboy.gr/current/web/app/plugins/eboy-product-importer/product-ai-processor
source venv/bin/activate

python3 <<EOF
from src.woocommerce_api import WooCommerceAPI
from dotenv import load_dotenv
import os

load_dotenv()

api = WooCommerceAPI(
    os.getenv('WOO_URL'),
    os.getenv('WOO_CONSUMER_KEY'),
    os.getenv('WOO_CONSUMER_SECRET')
)

# Test connection
import requests
response = requests.get(
    f'{api.api_url}/products',
    auth=api.auth,
    headers=api.headers,
    params={'per_page': 1}
)

print(f'Connection test: {response.status_code}')
if response.status_code == 200:
    print('✅ WooCommerce API connection successful!')
    products = response.json()
    print(f'Found {len(products)} product(s)')
else:
    print(f'❌ Failed: {response.text}')
EOF
```

## Τρόπος λειτουργίας

Με το `woo_auto_import: true`:

1. Python script επεξεργάζεται προϊόν με AI
2. Μεταφράζει (αν EN→EL)
3. Γράφει στο enhanced XML
4. **Κάνει αυτόματα import στο WooCommerce μέσω REST API**
5. Λογάρει αποτέλεσμα

Κάθε προϊόν εισάγεται αμέσως μετά την επεξεργασία του!
