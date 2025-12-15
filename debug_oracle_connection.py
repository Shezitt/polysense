#!/usr/bin/env python3
"""
Script para debuggear la conexión WebSocket con Oracle
"""
import asyncio
import websockets
import json
import time

async def test_oracle_connection():
    """Prueba diferentes configuraciones de conexión"""
    
    # Configuraciones a probar
    configs = [
        ('ws://144.22.56.85:5000/ws/CAM_001', 'WebSocket con CAM_001'),
        ('ws://144.22.56.85:5000/ws', 'WebSocket sin path'),
        ('ws://144.22.56.85:5000/', 'WebSocket raíz'),
        ('ws://144.22.56.85:5000', 'WebSocket sin slash'),
    ]
    
    for uri, description in configs:
        print(f"\n{'='*60}")
        print(f"🔍 Probando: {description}")
        print(f"   URI: {uri}")
        print(f"{'='*60}")
        
        try:
            print(f"⏳ Conectando (timeout: 10s)...")
            async with asyncio.timeout(10):
                async with websockets.connect(uri, ping_interval=None) as websocket:
                    print(f"✅ ¡CONECTADO!")
                    
                    # Intentar recibir un frame
                    print(f"⏳ Esperando datos...")
                    try:
                        data = await asyncio.wait_for(websocket.recv(), timeout=5)
                        print(f"📦 Datos recibidos: {len(data)} bytes")
                        print(f"   Primeros 100 bytes: {data[:100]}")
                    except asyncio.TimeoutError:
                        print(f"⏱️ Timeout esperando datos (5s)")
                    except Exception as e:
                        print(f"❌ Error recibiendo datos: {e}")
                        
        except asyncio.TimeoutError:
            print(f"❌ TIMEOUT: No se pudo conectar en 10s")
        except websockets.exceptions.InvalidStatusCode as e:
            print(f"❌ INVALID STATUS: {e}")
        except websockets.exceptions.WebSocketException as e:
            print(f"❌ WebSocket ERROR: {e}")
        except ConnectionRefusedError as e:
            print(f"❌ CONEXIÓN RECHAZADA: {e}")
        except OSError as e:
            print(f"❌ OS ERROR: {e}")
        except Exception as e:
            print(f"❌ ERROR: {type(e).__name__}: {e}")
        
        time.sleep(1)

async def main():
    print("\n" + "="*60)
    print("🔧 DEBUG: Investigando conexión WebSocket con Oracle")
    print("="*60)
    await test_oracle_connection()
    print("\n" + "="*60)
    print("✓ Test completado")
    print("="*60 + "\n")

if __name__ == '__main__':
    asyncio.run(main())
