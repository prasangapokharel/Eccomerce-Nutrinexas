#!/usr/bin/env python3
"""
Monitor System - Optional Ngrok Tunnel
=====================================

This script provides optional online access via Ngrok tunnel.
Run this in a separate terminal after starting the main server.

Usage: python routes/ngrok.py
"""

import os
import sys
import time
import json
import logging
import subprocess
import requests
from http.server import HTTPServer, BaseHTTPRequestHandler

# Configuration
NGROK_AUTHTOKEN = "2vrNVi3RK0lj1sW9EzyPi4KNuYd_4GB25NyF8wysugSiHLuzf"
LOCAL_PORT = 8000
NGROK_API_URL = "http://localhost:4040/api/tunnels"

# Setup logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

class NgrokTunnel:
    def __init__(self):
        self.process = None
        self.tunnel_url = None
        self.reconnect_attempts = 0
        self.max_reconnect_attempts = 10
        
    def check_ngrok_installed(self):
        """Check if ngrok is installed"""
        try:
            ngrok_cmd = 'ngrok.exe' if sys.platform.startswith('win') and os.path.exists('ngrok.exe') else 'ngrok'
            result = subprocess.run([ngrok_cmd, 'version'], 
                                  capture_output=True, text=True, timeout=5)
            return result.returncode == 0
        except (subprocess.TimeoutExpired, FileNotFoundError):
            return False
    
    def is_process_alive(self):
        """Check if ngrok process is still running"""
        if not self.process:
            return False
        return self.process.poll() is None
    
    def check_tunnel_health(self):
        """Check if tunnel is healthy by testing the API"""
        try:
            response = requests.get(NGROK_API_URL, timeout=3)
            if response.status_code == 200:
                data = response.json()
                if data.get('tunnels') and len(data['tunnels']) > 0:
                    tunnel = data['tunnels'][0]
                    # Check if tunnel is active
                    if tunnel.get('proto') == 'https' and tunnel.get('public_url'):
                        return True
            return False
        except requests.RequestException:
            return False
    
    def check_local_server(self):
        """Check if local server is running"""
        try:
            response = requests.get(f'http://localhost:{LOCAL_PORT}', timeout=2)
            return True
        except requests.RequestException:
            return False
    
    def install_ngrok(self):
        """Install ngrok if not present"""
        logger.info("🌐 Installing Ngrok...")
        
        try:
            if sys.platform.startswith('win'):
                # Windows installation
                url = "https://bin.equinox.io/c/bNyj1mQVY4c/ngrok-v3-stable-windows-amd64.zip"
                zip_file = "ngrok.zip"
                
                # Download
                response = requests.get(url, timeout=30)
                with open(zip_file, 'wb') as f:
                    f.write(response.content)
                
                # Extract using PowerShell
                subprocess.run([
                    'powershell', '-command', 
                    f'Expand-Archive -Path {zip_file} -DestinationPath . -Force'
                ], check=True)
                
                os.remove(zip_file)
                return os.path.exists('ngrok.exe')
                
            else:
                # Linux/Unix installation
                install_cmd = [
                    'curl', '-s', 'https://ngrok-agent.s3.amazonaws.com/ngrok.asc',
                    '|', 'sudo', 'tee', '/etc/apt/trusted.gpg.d/ngrok.asc', '>/dev/null',
                    '&&', 'echo', '"deb https://ngrok-agent.s3.amazonaws.com buster main"',
                    '|', 'sudo', 'tee', '/etc/apt/sources.list.d/ngrok.list',
                    '&&', 'sudo', 'apt', 'update',
                    '&&', 'sudo', 'apt', 'install', '-y', 'ngrok'
                ]
                subprocess.run(' '.join(install_cmd), shell=True, check=True)
                return True
                
        except Exception as e:
            logger.error(f"❌ Failed to install Ngrok: {e}")
            return False
    
    def setup_auth_token(self):
        """Setup ngrok auth token"""
        try:
            ngrok_cmd = 'ngrok.exe' if sys.platform.startswith('win') and os.path.exists('ngrok.exe') else 'ngrok'
            subprocess.run([ngrok_cmd, 'config', 'add-authtoken', NGROK_AUTHTOKEN], 
                         capture_output=True, check=True)
            return True
        except subprocess.CalledProcessError as e:
            logger.error(f"❌ Failed to setup auth token: {e}")
            return False
    
    def start_tunnel(self):
        """Start ngrok tunnel with improved error handling"""
        try:
            # Clean up any existing process
            if self.process and self.is_process_alive():
                self.stop_tunnel()
            
            ngrok_cmd = 'ngrok.exe' if sys.platform.startswith('win') and os.path.exists('ngrok.exe') else 'ngrok'
            
            # Start ngrok in background with better error handling
            self.process = subprocess.Popen([
                ngrok_cmd, 'http', str(LOCAL_PORT), 
                '--log=stdout',
                '--log-format=logfmt',
                '--log-level=info'
            ], stdout=subprocess.PIPE, stderr=subprocess.PIPE, 
               creationflags=subprocess.CREATE_NO_WINDOW if sys.platform.startswith('win') else 0)
            
            # Wait for tunnel to establish with retries
            max_wait = 10
            wait_interval = 0.5
            waited = 0
            
            while waited < max_wait:
                if not self.is_process_alive():
                    logger.error("Ngrok process died during startup")
                    return False
                
                # Check if tunnel is ready
                try:
                    response = requests.get(NGROK_API_URL, timeout=2)
                    if response.status_code == 200:
                        data = response.json()
                        if data.get('tunnels') and len(data['tunnels']) > 0:
                            self.tunnel_url = data['tunnels'][0]['public_url']
                            return True
                except requests.RequestException:
                    pass
                
                time.sleep(wait_interval)
                waited += wait_interval
                
            logger.error("Tunnel failed to establish within timeout")
            return False
            
        except Exception as e:
            logger.error(f"❌ Failed to start tunnel: {e}")
            if self.process:
                self.stop_tunnel()
            return False
    
    def stop_tunnel(self):
        """Stop ngrok tunnel with proper cleanup"""
        if self.process:
            try:
                if self.is_process_alive():
                    self.process.terminate()
                    try:
                        self.process.wait(timeout=5)
                    except subprocess.TimeoutExpired:
                        self.process.kill()
                        self.process.wait()
            except Exception as e:
                logger.warning(f"Error stopping tunnel: {e}")
                try:
                    if self.process and self.is_process_alive():
                        self.process.kill()
                except:
                    pass
            finally:
                self.process = None
                self.tunnel_url = None
    
    def get_tunnel_url(self):
        """Get current tunnel URL"""
        try:
            response = requests.get(NGROK_API_URL, timeout=5)
            if response.status_code == 200:
                data = response.json()
                if data.get('tunnels'):
                    return data['tunnels'][0]['public_url']
        except requests.RequestException:
            pass
        return None

def main():
    """Main function"""
    print("🌍 Monitor System - Ngrok Tunnel")
    print("================================")
    print()
    
    tunnel = NgrokTunnel()
    
    # Check if ngrok is installed
    if not tunnel.check_ngrok_installed():
        if not tunnel.install_ngrok():
            print("❌ Failed to install Ngrok")
            return 1
    
    # Setup auth token
    if not tunnel.setup_auth_token():
        print("❌ Failed to setup auth token")
        return 1
    
    # Check if local server is running
    print("🔍 Checking local server...")
    if not tunnel.check_local_server():
        print(f"⚠️  Local server not running on port {LOCAL_PORT}")
        print("💡 Please start your server first, then run this script")
        return 1
    print("✅ Local server is running")
    print()
    
    # Start tunnel
    print("🚀 Starting Ngrok tunnel...")
    if not tunnel.start_tunnel():
        print("❌ Failed to start tunnel")
        return 1
    
    # Display tunnel info
    tunnel_url = tunnel.get_tunnel_url()
    if tunnel_url:
        print("✅ Ngrok tunnel established!")
        print()
        print("🌍 Online URL:", tunnel_url)
        print("📍 Local URL: http://localhost:8000")
        print()
        print("💡 Press Ctrl+C to stop tunnel")
        print()
        
        try:
            # Keep tunnel running with improved monitoring
            check_interval = 5  # Check every 5 seconds
            consecutive_failures = 0
            max_consecutive_failures = 3
            
            while True:
                time.sleep(check_interval)
                
                # Check if process is alive
                if not tunnel.is_process_alive():
                    consecutive_failures += 1
                    logger.warning(f"Ngrok process died (failure {consecutive_failures}/{max_consecutive_failures})")
                    
                    if consecutive_failures >= max_consecutive_failures:
                        print("❌ Too many consecutive failures. Stopping...")
                        break
                    
                    print("⚠️  Tunnel process died, attempting to reconnect...")
                    tunnel.stop_tunnel()
                    
                    # Exponential backoff for reconnection
                    backoff_time = min(2 ** tunnel.reconnect_attempts, 30)
                    time.sleep(backoff_time)
                    
                    tunnel.reconnect_attempts += 1
                    if tunnel.reconnect_attempts > tunnel.max_reconnect_attempts:
                        print("❌ Max reconnection attempts reached. Stopping...")
                        break
                    
                    if tunnel.start_tunnel():
                        tunnel_url = tunnel.get_tunnel_url()
                        if tunnel_url:
                            print(f"✅ Reconnected! New URL: {tunnel_url}")
                            consecutive_failures = 0
                            tunnel.reconnect_attempts = 0
                        else:
                            print("⚠️  Tunnel started but URL not available yet")
                    else:
                        print("❌ Failed to reconnect tunnel")
                        continue
                
                # Check tunnel health via API
                elif not tunnel.check_tunnel_health():
                    consecutive_failures += 1
                    logger.warning(f"Tunnel health check failed (failure {consecutive_failures}/{max_consecutive_failures})")
                    
                    if consecutive_failures >= max_consecutive_failures:
                        print("⚠️  Tunnel health check failed multiple times, restarting...")
                        tunnel.stop_tunnel()
                        time.sleep(2)
                        
                        if tunnel.start_tunnel():
                            tunnel_url = tunnel.get_tunnel_url()
                            if tunnel_url:
                                print(f"✅ Restarted! New URL: {tunnel_url}")
                                consecutive_failures = 0
                            else:
                                print("⚠️  Tunnel restarted but URL not available")
                        else:
                            print("❌ Failed to restart tunnel")
                            break
                else:
                    # Tunnel is healthy, reset failure counter
                    if consecutive_failures > 0:
                        consecutive_failures = 0
                        tunnel.reconnect_attempts = 0
                    
        except KeyboardInterrupt:
            print("\n🛑 Stopping tunnel...")
            tunnel.stop_tunnel()
            print("✅ Tunnel stopped")
            
    else:
        print("❌ Failed to get tunnel URL")
        tunnel.stop_tunnel()
        return 1
    
    return 0

if __name__ == "__main__":
    sys.exit(main())
