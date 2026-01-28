"""
Ollama AI Client
Handles communication with the local Ollama API for text generation tasks.
"""

import requests
import json
import time
import logging
import os
from typing import Optional, Dict, Any

logger = logging.getLogger(__name__)


class OllamaClient:
    """Client for interacting with Ollama API"""

    def __init__(self, host: str = None, port: int = 11434, model: str = "mistral:7b-instruct-q4_K_M"):
        # Auto-detect host based on environment
        if host is None:
            # Check environment variable first
            host = os.getenv("OLLAMA_HOST")

            if not host:
                # Check if we're in Lima VM by testing connectivity
                # Try host.lima.internal first (Lima VM -> Mac host)
                try:
                    import socket
                    # Quick socket test (faster than HTTP)
                    sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
                    sock.settimeout(1)
                    # Resolve host.lima.internal
                    addr_info = socket.getaddrinfo("host.lima.internal", port, socket.AF_INET, socket.SOCK_STREAM)
                    if addr_info:
                        host_ip = addr_info[0][4][0]
                        result = sock.connect_ex((host_ip, port))
                        sock.close()
                        if result == 0:
                            host = "host.lima.internal"
                            logger.info("Detected Lima VM environment via socket test")
                        else:
                            host = "127.0.0.1"
                except:
                    # Fallback to localhost
                    host = "127.0.0.1"
                    logger.info("Using localhost (Lima detection failed)")

        self.base_url = f"http://{host}:{port}"
        self.model = model
        self.timeout = 300  # 5 minutes timeout for AI processing (Greek model is slower)
        self.max_retries = 3

        logger.info(f"Ollama client initialized: {self.base_url}, model: {self.model}")

    def _make_request(self, endpoint: str, data: Dict[str, Any], stream: bool = False) -> Optional[Dict]:
        """Make a request to Ollama API with retry logic"""
        url = f"{self.base_url}/{endpoint}"

        for attempt in range(self.max_retries):
            try:
                response = requests.post(
                    url,
                    json=data,
                    timeout=self.timeout,
                    stream=stream
                )

                if response.status_code == 200:
                    if stream:
                        # For streaming responses, collect all chunks
                        full_response = ""
                        for line in response.iter_lines():
                            if line:
                                chunk = json.loads(line)
                                if 'response' in chunk:
                                    full_response += chunk['response']
                                if chunk.get('done', False):
                                    return {'response': full_response}
                        return {'response': full_response}
                    else:
                        return response.json()
                else:
                    logger.error(f"Ollama API error: {response.status_code} - {response.text}")

            except requests.exceptions.Timeout:
                logger.warning(f"Ollama request timeout (attempt {attempt + 1}/{self.max_retries})")
                if attempt < self.max_retries - 1:
                    time.sleep(2 ** attempt)  # Exponential backoff

            except requests.exceptions.ConnectionError:
                logger.error("Cannot connect to Ollama. Is it running?")
                return None

            except Exception as e:
                logger.error(f"Ollama request failed: {str(e)}")

        return None

    def generate(self, prompt: str, system_prompt: Optional[str] = None, **kwargs) -> Optional[str]:
        """
        Generate text using Ollama

        Args:
            prompt: The user prompt
            system_prompt: Optional system prompt for context
            **kwargs: Additional generation parameters (temperature, top_p, etc.)

        Returns:
            Generated text or None if failed
        """
        data = {
            "model": self.model,
            "prompt": prompt,
            "stream": False,
            "options": {
                "temperature": kwargs.get('temperature', 0.7),
                "top_p": kwargs.get('top_p', 0.9),
                "num_predict": kwargs.get('max_tokens', 1000),
            }
        }

        if system_prompt:
            data['system'] = system_prompt

        logger.debug(f"Sending prompt to Ollama: {prompt[:100]}...")

        response = self._make_request("api/generate", data)

        if response and 'response' in response:
            result = response['response'].strip()
            logger.debug(f"Received response: {result[:100]}...")
            return result

        return None

    def chat(self, messages: list, **kwargs) -> Optional[str]:
        """
        Chat completion using Ollama

        Args:
            messages: List of message dicts with 'role' and 'content'
            **kwargs: Additional generation parameters

        Returns:
            Generated response or None if failed
        """
        data = {
            "model": self.model,
            "messages": messages,
            "stream": False,
            "options": {
                "temperature": kwargs.get('temperature', 0.7),
                "top_p": kwargs.get('top_p', 0.9),
            }
        }

        response = self._make_request("api/chat", data)

        if response and 'message' in response:
            return response['message']['content'].strip()

        return None

    def health_check(self) -> bool:
        """Check if Ollama is running and accessible"""
        try:
            response = requests.get(f"{self.base_url}/api/version", timeout=5)
            return response.status_code == 200
        except:
            return False

    def list_models(self) -> list:
        """List available models"""
        try:
            response = requests.get(f"{self.base_url}/api/tags", timeout=10)
            if response.status_code == 200:
                data = response.json()
                return [model['name'] for model in data.get('models', [])]
        except:
            pass
        return []
