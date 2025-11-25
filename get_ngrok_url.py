#!/usr/bin/env python3
"""Get ngrok tunnel URL"""
import requests
import time
import sys

NGROK_API_URL = "http://localhost:4040/api/tunnels"

def get_tunnel_url():
    """Get current ngrok tunnel URL"""
    max_attempts = 10
    for i in range(max_attempts):
        try:
            response = requests.get(NGROK_API_URL, timeout=3)
            if response.status_code == 200:
                data = response.json()
                if data.get('tunnels') and len(data['tunnels']) > 0:
                    return data['tunnels'][0]['public_url']
        except requests.RequestException:
            pass
        
        if i < max_attempts - 1:
            time.sleep(1)
    
    return None

if __name__ == "__main__":
    url = get_tunnel_url()
    if url:
        print("=" * 60)
        print("Ngrok Tunnel Active!")
        print("=" * 60)
        print(f"Public URL: {url}")
        print(f"Local URL: http://localhost:8000")
        print(f"\nYour site is now live at: {url}")
        print("=" * 60)
    else:
        print("Waiting for ngrok tunnel to establish...")
        print("Make sure ngrok is running and port 8000 is active")
        sys.exit(1)

