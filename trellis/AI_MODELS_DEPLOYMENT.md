# AI Models Deployment Guide

Αυτός ο οδηγός εξηγεί πώς να εγκαταστήσεις αυτόματα τα AI μοντέλα στους servers μέσω Trellis provision.

## Τι εγκαθίσταται

Κατά το Trellis provision, εγκαθίστανται αυτόματα:

1. **Ollama Server** - AI model serving platform
2. **Llama 3.1 8B** - English language model για AI processing
3. **Llama-Krikri 8B** - Greek language model για translation

## Περιβάλλοντα

### Development
- **Ollama**: Δεν εγκαθίσταται (χρησιμοποιεί remote Lima VM: `192.168.5.2`)
- **AI Processor**: Συνδέεται στο remote Ollama

### Staging & Production
- **Ollama**: Εγκαθίσταται τοπικά
- **AI Processor**: Χρησιμοποιεί local Ollama (`127.0.0.1`)
- **Models**: Κατεβαίνουν αυτόματα

## Provision Commands

### Staging
```bash
cd trellis
ansible-playbook server.yml -e env=staging
```

Αυτό θα:
1. Εγκαταστήσει το Ollama
2. Κατεβάσει τα μοντέλα (Llama 3.1 8B, Krikri)
3. Deploy το AI processor με `.env` configuration
4. Ρυθμίσει τα WooCommerce API credentials

**Διάρκεια**: ~30-45 λεπτά (τα μοντέλα είναι ~8GB το καθένα)

### Production
```bash
cd trellis
ansible-playbook server.yml -e env=production
```

Το ίδιο με staging, αλλά με production credentials.

## Μόνο AI Components

Αν θέλεις να εγκαταστήσεις μόνο τα AI components χωρίς να αγγίξεις τα υπόλοιπα:

```bash
# Install/Update Ollama και models
ansible-playbook server.yml -e env=staging --tags ollama

# Deploy AI processor configuration
ansible-playbook server.yml -e env=staging --tags ai-processor

# Και τα δύο μαζί
ansible-playbook server.yml -e env=staging --tags ollama,ai-processor
```

## Προσθήκη νέων μοντέλων

Για να προσθέσεις επιπλέον μοντέλα:

1. Άνοιξε το `group_vars/all/ollama.yml`
2. Πρόσθεσε το μοντέλο στη λίστα `ollama_models`:

```yaml
ollama_models:
  - "ilsp/llama-krikri-8b-instruct:latest"
  - "llama3.1:8b"
  - "mistral:7b-instruct-q4_K_M"  # Νέο μοντέλο
```

3. Τρέξε provision:
```bash
ansible-playbook server.yml -e env=staging --tags ollama
```

## Επιβεβαίωση εγκατάστασης

Μετά το provision, σύνδεσου στον server και έλεγξε:

```bash
# SSH στον server
ssh web@staging.simple-city.gr

# Έλεγχος Ollama service
sudo systemctl status ollama

# Λίστα εγκατεστημένων μοντέλων
ollama list

# Test API
curl http://localhost:11434/api/version

# Test με ένα μοντέλο
ollama run llama3.1:8b "Hello, test message"
```

## Troubleshooting

### Τα μοντέλα δεν κατέβηκαν

```bash
# Manual download
sudo -u ollama ollama pull llama3.1:8b
sudo -u ollama ollama pull ilsp/llama-krikri-8b-instruct:latest
```

### Ollama service δεν τρέχει

```bash
# Restart service
sudo systemctl restart ollama

# Check logs
sudo journalctl -u ollama -f
```

### Out of disk space

Τα μοντέλα απαιτούν ~16GB χώρο. Έλεγξε:

```bash
# Check disk usage
df -h

# Check Ollama data directory
du -sh /var/lib/ollama
```

## Performance Tips

### Για καλύτερη απόδοση:

1. **GPU**: Αν ο server έχει GPU, το Ollama θα το χρησιμοποιήσει αυτόματα
2. **RAM**: Τουλάχιστον 16GB RAM συνιστάται
3. **CPU**: Περισσότερα cores = γρηγορότερη inference

### Ρύθμιση threads:

Πρόσθεσε στο `group_vars/{env}/ollama.yml`:

```yaml
ollama_num_threads: 8  # Αριθμός CPU threads
```

## Model Information

### Llama 3.1 8B
- **Size**: ~4.7GB
- **Use**: English text processing, title optimization
- **Speed**: ~30 tokens/sec (CPU)

### Llama-Krikri 8B
- **Size**: ~4.7GB
- **Use**: Greek translation, Greek text processing
- **Speed**: ~25 tokens/sec (CPU)

## Next Steps

Μετά την επιτυχή εγκατάσταση:

1. ✅ Test το AI processor με 3 προϊόντα
2. ✅ Έλεγχος WooCommerce auto-import
3. ✅ Setup cron jobs για automated processing
4. ✅ Monitor logs και performance

## Related Documentation

- `WOOCOMMERCE_API_SETUP.md` - WooCommerce API configuration
- `roles/ai-processor/README.md` - AI Processor role documentation
- `roles/ollama/README.md` - Ollama role documentation
