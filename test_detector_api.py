#!/usr/bin/env python3
"""
Script de prueba para verificar que la API del detector 
responde correctamente con los parámetros de camera_id
"""

import requests
import json
import time

BASE_URL = "http://localhost:8080"

def test_endpoints():
    print("\n" + "="*70)
    print("🧪 TESTING DETECTOR API - MULTI-CÁMARA")
    print("="*70)
    
    cameras = ['CAM_001', 'CAM_002']
    
    for cam in cameras:
        print(f"\n🔍 Testando {cam}:")
        print("-" * 70)
        
        # Test /api/vehicles
        try:
            url = f"{BASE_URL}/api/vehicles?camera_id={cam}"
            response = requests.get(url, timeout=5)
            if response.status_code == 200:
                data = response.json()
                print(f"  ✅ /api/vehicles?camera_id={cam}")
                print(f"     - Status: {data.get('status')}")
                print(f"     - Current vehicles: {data.get('current_vehicles')}")
                print(f"     - Total detected: {data.get('total_detected')}")
                print(f"     - FPS: {data.get('fps')}")
                print(f"     - Camera ID (response): {data.get('camera_id')}")
            else:
                print(f"  ❌ /api/vehicles?camera_id={cam} - Status {response.status_code}")
        except Exception as e:
            print(f"  ❌ /api/vehicles?camera_id={cam} - Error: {str(e)[:80]}")
        
        # Test /stats
        try:
            url = f"{BASE_URL}/stats?camera_id={cam}"
            response = requests.get(url, timeout=5)
            if response.status_code == 200:
                data = response.json()
                print(f"  ✅ /stats?camera_id={cam}")
                print(f"     - Status: {data.get('status')}")
                print(f"     - FPS: {data.get('fps')}")
                print(f"     - Frame count: {data.get('frame_count')}")
            else:
                print(f"  ❌ /stats?camera_id={cam} - Status {response.status_code}")
        except Exception as e:
            print(f"  ❌ /stats?camera_id={cam} - Error: {str(e)[:80]}")
    
    # Test main index
    try:
        response = requests.get(BASE_URL, timeout=5)
        if response.status_code == 200:
            print(f"\n  ✅ / (index)")
    except Exception as e:
        print(f"\n  ❌ / (index) - Error: {str(e)[:80]}")
    
    print("\n" + "="*70)
    print("✨ Test completado")
    print("="*70 + "\n")

if __name__ == '__main__':
    print("Esperando a que el detector esté listo...")
    time.sleep(2)
    test_endpoints()
