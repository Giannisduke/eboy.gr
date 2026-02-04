# Ollama Troubleshooting Guide

Αν το Ollama service δεν ξεκινάει κατά το provision, ακολούθησε αυτά τα βήματα.

## Γρήγορος Έλεγχος

SSH στον server και τρέξε:

```bash
# Check service status
sudo systemctl status ollama

# Check logs
sudo journalctl -u ollama -n 100 --no-pager

# Check if port is in use
sudo netstat -tulpn | grep 11434

# Test Ollama manually
sudo -u ollama /usr/local/bin/ollama serve
```

## Συνήθη Προβλήματα

### 1. Service δεν ξεκινάει (Connection refused)

**Αιτία**: Το service έχει σταματήσει ή δεν ξεκίνησε ποτέ.

**Λύση**:
```bash
# Restart service
sudo systemctl restart ollama

# Wait 10 seconds
sleep 10

# Check if running
curl http://localhost:11434/api/version
```

### 2. Out of Memory

**Αιτία**: Το VM δεν έχει αρκετή RAM για τα models.

**Λύση**:
```bash
# Check memory
free -h

# Adjust memory limits in service
sudo systemctl edit ollama

# Add:
[Service]
MemoryMax=4G
MemoryHigh=3.5G

# Reload and restart
sudo systemctl daemon-reload
sudo systemctl restart ollama
```

### 3. Permission denied

**Αιτία**: Ο χρήστης `ollama` δεν έχει πρόσβαση.

**Λύση**:
```bash
# Fix permissions
sudo chown -R ollama:ollama /var/lib/ollama
sudo chown -R ollama:ollama /var/log/ollama
sudo chmod 755 /var/lib/ollama /var/log/ollama

# Restart
sudo systemctl restart ollama
```

### 4. Models δεν κατέβηκαν

**Αιτία**: Το provision έτρεξε πριν το service ξεκινήσει πλήρως.

**Λύση**:
```bash
# Manual download
sudo systemctl start ollama
sleep 10

sudo -u ollama /usr/local/bin/ollama pull llama3.1:8b
sudo -u ollama /usr/local/bin/ollama pull ilsp/llama-krikri-8b-instruct:latest

# Verify
sudo -u ollama /usr/local/bin/ollama list
```

### 5. Port already in use

**Αιτία**: Κάποια άλλη διεργασία χρησιμοποιεί το port 11434.

**Λύση**:
```bash
# Find what's using the port
sudo lsof -i :11434

# Kill the process (if not Ollama)
sudo kill -9 <PID>

# Or change Ollama port
sudo systemctl edit ollama

# Add:
[Service]
Environment="OLLAMA_HOST=0.0.0.0:11435"

# Update group_vars and redeploy
```

## Re-run Provision

Αν διορθώσεις το πρόβλημα manually, ξανατρέξε το provision:

```bash
# From Trellis directory
ansible-playbook server.yml -e env=staging --tags ollama
```

## Complete Reinstall

Αν τίποτα δεν δουλεύει, κάνε clean install:

```bash
# SSH to server
ssh web@staging.simple-city.gr

# Stop service
sudo systemctl stop ollama
sudo systemctl disable ollama

# Remove everything
sudo rm -rf /var/lib/ollama
sudo rm -rf /var/log/ollama
sudo rm -f /etc/systemd/system/ollama.service
sudo rm -f /usr/local/bin/ollama
sudo userdel ollama
sudo groupdel ollama

# Reload systemd
sudo systemctl daemon-reload

# Re-run provision
cd /path/to/trellis
ansible-playbook server.yml -e env=staging --tags ollama
```

## Verify Everything Works

```bash
# 1. Service is running
sudo systemctl is-active ollama

# 2. API responds
curl http://localhost:11434/api/version

# 3. Models are loaded
sudo -u ollama /usr/local/bin/ollama list

# 4. Test inference
sudo -u ollama /usr/local/bin/ollama run llama3.1:8b "Hello"
```

## Post-Provision Manual Fix

Αν το provision τελείωσε αλλά το Ollama δεν τρέχει:

```bash
# SSH to server
ssh web@staging.simple-city.gr

# Start service
sudo systemctl start ollama

# Download models manually
sudo -u ollama /usr/local/bin/ollama pull llama3.1:8b &
sudo -u ollama /usr/local/bin/ollama pull ilsp/llama-krikri-8b-instruct:latest &

# Wait for downloads (can take 30 minutes)
wait

# Verify
sudo -u ollama /usr/local/bin/ollama list
```

## Monitoring

```bash
# Watch service status
watch -n 2 'sudo systemctl status ollama'

# Follow logs
sudo journalctl -u ollama -f

# Check resource usage
htop -p $(pgrep ollama)
```

## Get Help

Αν το πρόβλημα παραμένει:

1. Αντίγραψε τα logs: `sudo journalctl -u ollama -n 200 > ollama-logs.txt`
2. Αντίγραψε το service file: `cat /etc/systemd/system/ollama.service`
3. Check system resources: `free -h && df -h`
