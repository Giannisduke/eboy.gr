#!/bin/bash
# Quick Test Script for AI Product Processor

set -e

echo "=========================================="
echo "AI Product Processor - Test Script"
echo "=========================================="
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Navigate to script directory
cd "$(dirname "$0")"

# Activate virtual environment
echo -e "${YELLOW}Activating virtual environment...${NC}"
source venv/bin/activate

# Check Ollama connection
echo ""
echo -e "${YELLOW}Checking Ollama connection...${NC}"
python3 -c "
from src.ai_client import OllamaClient
client = OllamaClient()
if client.health_check():
    print('✅ Ollama is running')
    models = client.list_models()
    print(f'✅ Available models: {models}')
else:
    print('❌ Cannot connect to Ollama')
    exit(1)
"

if [ $? -ne 0 ]; then
    echo -e "${RED}Ollama is not running. Please start it first.${NC}"
    exit 1
fi

echo ""
echo -e "${GREEN}Running test with 3 products (no images)...${NC}"
echo ""

python3 main.py --mode test \
    --input ../../site/web/app/xml_files/pakoworld.xml \
    --limit 3 \
    --skip-images \
    --debug

echo ""
echo -e "${GREEN}Test completed!${NC}"
echo ""
echo "Output file: output/test-output.xml"
echo "Log file: logs/processor.log"
echo ""
echo "To view enhanced XML:"
echo "  cat output/test-output.xml | less"
echo ""
echo "To view logs:"
echo "  tail -f logs/processor.log"
