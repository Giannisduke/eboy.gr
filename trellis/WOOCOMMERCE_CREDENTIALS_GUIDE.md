# WooCommerce API Credentials Setup Guide

## Overview
The AI processor needs WooCommerce REST API credentials to automatically import products. These credentials must be added to the encrypted vault files for each environment.

## Required Variables

### Staging Environment
Edit: `/trellis/group_vars/staging/vault.yml`

```yaml
# WooCommerce API Credentials for Staging
vault_woo_staging_url: "https://staging.simple-city.gr"
vault_woo_staging_consumer_key: "ck_YOUR_STAGING_CONSUMER_KEY"
vault_woo_staging_consumer_secret: "cs_YOUR_STAGING_CONSUMER_SECRET"
```

### Production Environment
Edit: `/trellis/group_vars/production/vault.yml`

```yaml
# WooCommerce API Credentials for Production
vault_woo_production_url: "https://simple-city.eboy.gr"
vault_woo_production_consumer_key: "ck_YOUR_PRODUCTION_CONSUMER_KEY"
vault_woo_production_consumer_secret: "cs_YOUR_PRODUCTION_CONSUMER_SECRET"
```

## How to Edit Encrypted Vault Files

### 1. Edit Staging Vault
```bash
cd /Users/eboy/sites/eboy.gr/trellis
ansible-vault edit group_vars/staging/vault.yml
```

### 2. Edit Production Vault
```bash
cd /Users/eboy/sites/eboy.gr/trellis
ansible-vault edit group_vars/production/vault.yml
```

You'll be prompted for the vault password. Once opened, add the variables listed above.

## How to Generate WooCommerce API Keys

### For Staging (staging.simple-city.gr):
1. Log in to WordPress admin: `https://staging.simple-city.gr/wp-admin`
2. Navigate to: **WooCommerce → Settings → Advanced → REST API**
3. Click **Add key**
4. Set:
   - Description: "AI Processor - Staging"
   - User: Select admin user
   - Permissions: **Read/Write**
5. Click **Generate API Key**
6. Copy the **Consumer Key** (starts with `ck_`) and **Consumer Secret** (starts with `cs_`)
7. Add to `group_vars/staging/vault.yml`

### For Production (simple-city.eboy.gr):
1. Log in to WordPress admin: `https://simple-city.eboy.gr/wp-admin`
2. Navigate to: **WooCommerce → Settings → Advanced → REST API**
3. Click **Add key**
4. Set:
   - Description: "AI Processor - Production"
   - User: Select admin user
   - Permissions: **Read/Write**
5. Click **Generate API Key**
6. Copy the **Consumer Key** (starts with `ck_`) and **Consumer Secret** (starts with `cs_`)
7. Add to `group_vars/production/vault.yml`

## Verify Configuration

After adding credentials, verify the configuration is correct:

```bash
# Check staging configuration
ansible-inventory -i hosts/staging --list --yaml | grep -A 3 "ai_processor"

# Check production configuration
ansible-inventory -i hosts/production --list --yaml | grep -A 3 "ai_processor"
```

## Test WooCommerce Connection

After deployment, test the connection from the server:

```bash
# SSH to staging
ssh web@staging.simple-city.gr

# Test WooCommerce API
curl -u "ck_xxx:cs_xxx" https://staging.simple-city.gr/wp-json/wc/v3/products

# Or use the Python script
cd /srv/www/simple-city.gr/current/web/app/themes/simple-city/scripts/product-ai-processor
source venv/bin/activate
python3 -c "from src.woocommerce_api import WooCommerceAPI; api = WooCommerceAPI(); print(api.test_connection())"
```

## Security Notes

1. **Never commit vault files unencrypted** - They should always remain encrypted
2. **Use Read/Write permissions** - The AI processor needs to create and update products
3. **Separate keys per environment** - Use different API keys for staging and production
4. **Rotate keys periodically** - Generate new keys every 6-12 months

## Current Configuration Status

### Staging
- ✅ Configuration file: `/trellis/group_vars/staging/ai_processor.yml` (updated)
- ⏳ Vault credentials: **Need to be added**
- ⚙️ Auto-import: **Disabled** (set `woo_auto_import: false`)
- 🔗 URL: `https://staging.simple-city.gr`

### Production
- ✅ Configuration file: `/trellis/group_vars/production/ai_processor.yml` (updated)
- ⏳ Vault credentials: **Need to be added**
- ⚙️ Auto-import: **Enabled** (set `woo_auto_import: true`)
- 🔗 URL: `https://simple-city.eboy.gr` (corrected)

## Next Steps

1. ✅ Configuration files are ready
2. 📝 Generate WooCommerce API keys in both environments
3. 🔐 Add credentials to encrypted vault files
4. 🚀 Run Trellis provision: `ansible-playbook server.yml -e env=staging`
5. ✅ Test the AI processor with WooCommerce import enabled
