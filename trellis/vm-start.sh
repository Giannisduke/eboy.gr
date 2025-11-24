#!/bin/bash
# Wrapper script για trellis vm start που αλλάζει το IP σε 127.0.0.1

# Start the VM
trellis vm start

# Replace the IP in the generated hosts file
sed -i '' 's/192\.168\.64\.3/127.0.0.1/g' /Users/eboy/.local/share/trellis/hosts

# Copy to /etc/hosts (requires sudo)
sudo cp /Users/eboy/.local/share/trellis/hosts /etc/hosts

echo ""
echo "✓ VM started and /etc/hosts updated with 127.0.0.1"
