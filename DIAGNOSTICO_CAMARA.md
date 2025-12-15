# 🔍 Diagnóstico: Problema de Selección de Cámara

## Síntomas
- Selector muestra "Oracle Server (CAM_001)"
- Pero stream muestra video de Skyline Cochabamba
- Estadísticas también pueden ser incorrectas

## Investigación

### 1. Arquitectura de Broadcasting
```
worker_oracle() → process_frame_generic(frame, 'CAM_001')
                  └─ broadcast a websocket_clients['CAM_001']

worker_skyline() → process_frame_generic(frame, 'CAM_002')
                   └─ broadcast a websocket_clients['CAM_002']
```

### 2. Flujo del WebSocket
```
Cliente selecciona CAM_001
    ↓
JavaScript: currentCamera = 'CAM_001'
    ↓
reconnectWebSocket() cerramos anterior y reconectamos
    ↓
connectWebSocket() → ws://localhost:8080/ws/stream?camera_id=CAM_001
    ↓
@sock.route('/ws/stream') recibe camera_id=CAM_001
    ↓
ws → websocket_clients['CAM_001'].append(ws)
    ↓
Espera frames de CAM_001
    ↓
Si hay timeout → envía placeholder OFFLINE
Si hay frames → envía desde state['CAM_001']['processed_frame']
```

## Posibles Causas

### Causa 1: Oracle no envía frames (CONFIRMADO)
✅ **Oracle server está reachable pero NO envía frames**
- Timeout después de 30s esperando

### Causa 2: WebSocket recibe frames de cámara equivocada
Esto pasaría si:
- Los frames de Skyline se envían a AMBAS listas
- O si el worker_skyline escriben en camera_states['CAM_001']

### Causa 3: Bug en JavaScript - websocket viejo sigue recibiendo
Posible si:
- La desconexión del WebSocket anterior no es limpia
- El onmessage del WebSocket viejo sigue ejecutándose

**SOLUCIÓN APLICADA**: Agregar debugging en JavaScript para verificar

## Pasos de Diagnóstico

### 1. Abre DevTools (F12) en el navegador
### 2. Ve a la pestaña "Console"
### 3. Selecciona "Oracle Server"
### 4. Observa los logs:
```
🔄 Reconectando a CAM_001...
❌ Cerrando WebSocket anterior
🔌 Conectando a: ws://localhost:8080/ws/stream?camera_id=CAM_001
📸 Camera actual: CAM_001
🆔 WebSocket ID: abc123xyz para CAM_001
✅ WebSocket abierto para CAM_001
📦 Frame recibido de CAM_001 (xxxxx bytes)
```

Si ves "Frame recibido de CAM_001", pero el video sigue siendo Skyline, entonces:
- **El problema está en el detector Flask** - está enviando frames equivocados

Si ves logs que dicen:
- "⚠️ Cámara cambió! Era CAM_002, ahora es CAM_001"
- O "Frame recibido" pero del WebSocket viejo
- Entonces **es un problema de JavaScript/timing**

## Solución Rápida

Si el problema persiste:

1. **Reinicia el detector completamente**:
```powershell
# Mata todo Python
Get-Process python -ErrorAction SilentlyContinue | Stop-Process -Force

# Inicia fresh
python .\deteccion\vehiculo_detector.py
```

2. **Vacía el caché del navegador**:
- Abre DevTools (F12)
- Click derecho en el botón de refresh
- Selecciona "Vaciar caché y descargar duro"

3. **Prueba nuevamente**:
- Abre modulo1
- Consola debe mostrar debugging claro

## Logs esperados del Detector

Cuando inicia:
```
🚗 DETECTOR MULTI-CÁMARA
CAM_001: Oracle Server
CAM_002: Skyline Cochabamba
========================================
🚀 CAM_001 (Oracle): Iniciando...
🚀 CAM_002 (Skyline): Iniciando...
```

Cuando seleccionas CAM_001:
```
🔌 CAM_001: Cliente conectado (WebSocket)
📦 Frame recibido de CAM_001 → broadcast a websocket_clients['CAM_001']
```

Si Oracle está offline:
```
⚠️ CAM_001: No data por 30s (1x)
⚠️ CAM_001: No data por 30s (2x)
⚠️ CAM_001: No data por 30s (3x)
❌ CAM_001: Servidor no envía frames, desconectando...
⚠️ CAM_001: Reconectando en 10s...
```

## Siguiente: POST-DIAGNÓSTICO

Después de ejecutar los pasos arriba, **incluye los logs de la consola del navegador** para que podamos identificar dónde está el problema.
