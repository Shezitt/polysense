# 🐛 Corrección: Selector de Cámara no Funcionaba

## Problema Identificado

Seleccionabas **Oracle (CAM_001)** en Módulo 1, pero se mostraba el stream de **Skyline (CAM_002)**.

### Causa Raíz

El **VehicleMonitorController.php** tenía un bug crítico:

```php
// ❌ ANTES (INCORRECTO)
public function getStats($cameraId = 'camera_01')
{
    $response = Http::timeout(5)->get("{$this->detectorUrl}/api/vehicles");
    // El parámetro $cameraId se recibía pero NUNCA se usaba!
}
```

Aunque la URL JavaScript enviaba correctamente `?camera_id=CAM_001`, el controlador:
1. Recibía el parámetro en `$cameraId`
2. Lo IGNORABA completamente
3. Siempre llamaba a `/api/vehicles` SIN el parámetro
4. Por defecto, el detector retornaba datos de **CAM_002**

---

## Soluciones Aplicadas

### 1️⃣ **VehicleMonitorController.php** ✅

```php
// ✅ DESPUÉS (CORRECTO)
public function getStats($cameraId = 'CAM_002')
{
    $cameraId = strtoupper($cameraId);
    if (!in_array($cameraId, ['CAM_001', 'CAM_002'])) {
        $cameraId = 'CAM_002';
    }
    
    // AHORA sí usa camera_id en la URL!
    $url = "{$this->detectorUrl}/api/vehicles?camera_id={$cameraId}";
    $response = Http::timeout(5)->get($url);
    // ...
}
```

**Cambios:**
- Normaliza `$cameraId` a mayúsculas
- Valida que sea `CAM_001` o `CAM_002`
- **Pasar `camera_id` en la URL**: `/api/vehicles?camera_id=CAM_001`

### 2️⃣ **vehiculo_detector.py** - Endpoint `/ws/stream` ✅

Mejoré el manejo de WebSocket:

```python
@sock.route('/ws/stream')
def websocket_stream(ws):
    camera_id = request.args.get('camera_id', 'CAM_002')  # ✅ Lee correctamente
    
    if camera_id not in camera_states:
        logger.error(f"❌ WebSocket: camera_id inválido: {camera_id}")
        ws.close()
        return
    
    logger.info(f"🔌 {camera_id}: Cliente conectado (WebSocket)")
    websocket_clients[camera_id].append(ws)  # ✅ Agrupa por cámara
    
    try:
        while True:
            msg = ws.receive(timeout=5)  # Mantener conexión abierta
            if msg is None:
                break
    finally:
        if ws in websocket_clients[camera_id]:
            websocket_clients[camera_id].remove(ws)
```

### 3️⃣ **vehiculo_detector.py** - Broadcasting de frames ✅

```python
# ✅ BROADCAST ESPECÍFICO POR CÁMARA
if camera_id in websocket_clients and websocket_clients[camera_id] and processed_frame is not None:
    try:
        _, buf = cv2.imencode('.jpg', processed_frame, [cv2.IMWRITE_JPEG_QUALITY, 60])
        frame_bytes = buf.tobytes()
        dead = []
        
        # Enviar SOLO a clientes de ESTA cámara
        for ws in websocket_clients[camera_id]:
            try:
                ws.send(frame_bytes)
            except Exception as send_err:
                dead.append(ws)
        
        # Limpiar conexiones muertas
        for d in dead:
            if d in websocket_clients[camera_id]:
                websocket_clients[camera_id].remove(d)
    except Exception as e:
        logger.warning(f"⚠️ {camera_id}: Error en broadcast - {str(e)[:80]}")
```

---

## Flujo Corregido

```
Módulo 1: Selecciono "Oracle (CAM_001)"
    ↓
JavaScript: currentCamera = 'CAM_001'
    ↓
WebSocket: ws://localhost:8080/ws/stream?camera_id=CAM_001
    ↓
Detector: websocket_clients['CAM_001'].append(ws)
    ↓
Worker Oracle: process_frame_generic(frame, 'CAM_001')
    ↓
Broadcast: Envía frames SOLO a clientes de CAM_001
    ↓
Modulo 1: Recibe stream de Oracle ✅

---

Módulo 1: Fetch stats: /api/vehicle-monitor/CAM_001
    ↓
VehicleMonitorController: $url = "/api/vehicles?camera_id=CAM_001"
    ↓
Detector: Retorna estadísticas de CAM_001
    ↓
Módulo 1: Muestra stats de Oracle ✅
```

---

## Testing

Ejecuta el script de prueba:

```powershell
cd c:\xampp\htdocs\ProyectoFinal\polysense
python .\deteccion\vehiculo_detector.py  # En una terminal
```

En otra terminal:

```powershell
cd c:\xampp\htdocs\ProyectoFinal\polysense
python .\test_detector_api.py
```

Debería ver:
```
✅ /api/vehicles?camera_id=CAM_001
   - Status: online/offline
   - Camera ID (response): CAM_001
   
✅ /api/vehicles?camera_id=CAM_002
   - Status: online/offline
   - Camera ID (response): CAM_002
```

---

## Verificación en Navegador

1. **Módulo 1**: `http://localhost:8000/modulo1`
   - Selector de cámara (arriba a la derecha)
   - Selecciona "Oracle Server" → Debe mostrar stream de Oracle
   - Selecciona "Skyline Cochabamba" → Debe mostrar stream de Skyline
   - Las estadísticas (vehículos, FPS) cambian según cámara

2. **Módulo 2**: `http://localhost:8000/modulo2`
   - Nuevo filtro "Cámara" al inicio del formulario
   - Selecciona cámara + otros filtros
   - Click "Filtrar"
   - Gráficos y tabla muestran solo datos de esa cámara

---

## ¿Qué estaba mal exactamente?

- **Antes**: Laravel siempre pedía datos de cámara RANDOM o por defecto
- **Ahora**: Laravel pasa explícitamente `camera_id` al detector
- **Resultado**: Cada cliente obtiene datos de la cámara que seleccionó

¡La arquitectura multi-cámara ahora funciona correctamente! 🚀
