#!/usr/bin/env python3
"""
Script para investigar qué WebSocket endpoints tiene el servidor Oracle
"""

import asyncio
import websockets
import time

async def test_websocket_endpoints():
    """Prueba diferentes endpoints WebSocket"""
    
    base_url = "ws://144.22.56.85:5000"
    
    # Endpoints a probar
    endpoints = [
        "/ws/stream",           # Nuestro endpoint actual
        "/ws",                  # Endpoint raíz
        "/stream",              # Sin /ws
        "/ws/camera",           # Variación
        "/ws/cam_001",          # Con nombre de cámara
        "/ws/CAM_001",          # Mayúsculas
        "/camera/stream",       # Estructura diferente
    ]
    
    print("="*70)
    print("🔍 Investigando endpoints WebSocket del servidor Oracle")
    print("="*70)
    print(f"Base URL: {base_url}\n")
    
    for endpoint in endpoints:
        uri = f"{base_url}{endpoint}"
        print(f"🧪 Intentando: {uri}")
        
        try:
            async with asyncio.timeout(5):
                async with websockets.connect(uri, ping_interval=None) as ws:
                    print(f"   ✅ CONECTADO!")
                    
                    # Intentar recibir un frame
                    try:
                        frame = await asyncio.wait_for(ws.recv(), timeout=3)
                        print(f"   📦 Recibido: {len(frame)} bytes")
                        print(f"   ✨ ESTE ES EL ENDPOINT CORRECTO!\n")
                        return uri
                    except asyncio.TimeoutError:
                        print(f"   ⏱️ Conectado pero sin frames (timeout)\n")
                    except Exception as e:
                        print(f"   ⚠️ Error recibiendo: {str(e)[:50]}\n")
                        
        except asyncio.TimeoutError:
            print(f"   ⏱️ Timeout de conexión\n")
        except Exception as e:
            print(f"   ❌ {str(e)[:60]}\n")
        
        await asyncio.sleep(0.5)
    
    print("="*70)
    print("❌ No se encontró un endpoint funcional")
    print("="*70)
    return None

if __name__ == "__main__":
    result = asyncio.run(test_websocket_endpoints())
    if result:
        print(f"\n🎯 Endpoint funcional encontrado: {result}")
    else:
        print("\n⚠️ Necesitas verificar la documentación del servidor Oracle")
