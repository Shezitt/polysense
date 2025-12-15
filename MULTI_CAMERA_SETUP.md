# Setup Multi-Cámara: Oracle + Skyline

## Resumen de Cambios

### 1. Detector Multi-Cámara (`deteccion/vehiculo_detector.py`)
**Estado**: ✅ Creado y listo para ejecutar

**Características**:
- **CAM_001 (Oracle Server)**: Conexión WebSocket async a `ws://144.22.56.85:5000/ws/stream`
- **CAM_002 (Skyline Cochabamba)**: Extracción dinámica de token con Playwright + streaming OpenCV
- **API Endpoints**: Todos aceptan parámetro `?camera_id=CAM_001|CAM_002`
  - `GET /api/vehicles?camera_id=X` - Lista de vehículos
  - `GET /api/vehicle-monitor/X` - Estadísticas en tiempo real
  - `GET /api/stats?camera_id=X` - Estadísticas detalladas
  - `GET /api/reset?camera_id=X` - Reiniciar cámara
  - `WS /ws/stream?camera_id=X` - WebSocket para frames en vivo

**Estructura de Estado**:
```python
camera_states = {
    'CAM_001': {'frames': deque(), 'tracking': {}, 'stats': {...}, 'fps_buffer': [...], 'history': deque()},
    'CAM_002': {'frames': deque(), 'tracking': {}, 'stats': {...}, 'fps_buffer': [...], 'history': deque()}
}
```

**XML Persistencia**: Ahora incluye campos por cámara
```xml
<deteccion>
    <fecha>2025-12-10 14:30:45</fecha>
    <tipo>Auto</tipo>
    <color>Rojo</color>
    <confianza>92.5</confianza>
    <camara>CAM_002</camara>
    <nombre_camara>Skyline Cochabamba</nombre_camara>
</deteccion>
```

---

### 2. Modulo 1 (`resources/views/modulo1.blade.php`)
**Estado**: ✅ Completado

**Cambios**:
- ✅ Agregado selector de cámara en header
  - Dropdown con opciones: "Skyline Cochabamba" (CAM_002) y "Oracle Server" (CAM_001)
  - Cámara por defecto: CAM_002
  
- ✅ JavaScript actualizado:
  - Variable `currentCamera` con valor seleccionado
  - Listener en `#cameraSelect` que dispara `reconnectWebSocket()`
  - Función `reconnectWebSocket()` para cambiar cámara sin recargar
  - URL WebSocket dinámica: `ws://localhost:8080/ws/stream?camera_id=${currentCamera}`
  - Endpoint stats dinámico: `/api/vehicle-monitor/${currentCamera}`

**Flujo de Uso**:
1. Usuario selecciona cámara en dropdown
2. Se cierra conexión WebSocket anterior
3. Se abre nueva conexión con `?camera_id=CAM_001` o `?camera_id=CAM_002`
4. Stats se recargan automáticamente cada 2 segundos con nueva cámara

---

### 3. Modulo 2 (`resources/views/modulo2.blade.php`)
**Estado**: ✅ Completado

**Cambios**:
- ✅ Agregado selector de cámara en form de filtros
  - Positioned antes de rango de fechas
  - Opciones: "Skyline Cochabamba" (CAM_002, por defecto) y "Oracle Server" (CAM_001)
  - Valor se pasa como parámetro `camera_id` en query string

- ✅ JavaScript mejorado:
  - Función `getSelectedCamera()` para leer parámetro de URL
  - Destrucción de gráficos anteriores antes de recrear (evita memory leaks)
  - Charts se actualizan según filtros: camera_id + tipo + rango de fechas

**Flujo de Uso**:
1. Usuario selecciona cámara + otros filtros en form
2. Hace click en "Filtrar"
3. Página se recarga con `?camera_id=CAM_001&fecha_inicio=...&fecha_fin=...&tipo=...`
4. Datos del XML se filtran por cámara
5. Gráficos se regeneran con datos de la cámara seleccionada

---

### 4. Controller de Reportes (`app/Http/Controllers/ReporteController.php`)
**Estado**: ✅ Actualizado

**Cambios**:
- ✅ Método `obtenerRegistrosFiltrados()` ahora lee campos de cámara:
  - `$det->camara` (ID de cámara: CAM_001 o CAM_002)
  - `$det->nombre_camara` (Nombre legible)
  
- ✅ Nuevo filtro: `$request->camera_id`
  - Solo retorna registros que coincidan con cámara seleccionada
  - Filtro se aplica ANTES de otros (tipo, fecha)

- ✅ Export a Excel incluye identificación de cámara en nombre de archivo

---

## Instrucciones de Uso

### 1. Iniciar el Detector Multi-Cámara
```powershell
cd c:\xampp\htdocs\ProyectoFinal\polysense\deteccion
python vehiculo_detector.py
```

**Esperado en consola**:
```
2025-12-10 14:30:00 - [DETECTOR] INFO - 🚀 Iniciando detector multi-cámara...
2025-12-10 14:30:01 - [DETECTOR] INFO - ✅ CAM_001 (Oracle Server) worker iniciado
2025-12-10 14:30:02 - [DETECTOR] INFO - ✅ CAM_002 (Skyline Cochabamba) worker iniciado
2025-12-10 14:30:03 - [DETECTOR] INFO - 🌐 Servidor Flask corriendo en http://0.0.0.0:8080
```

### 2. Verificar Disponibilidad
- **Oracle**: Intenta conectar a `ws://144.22.56.85:5000/ws/stream`
  - Si falla: logs mostrarán error de conexión pero seguirá esperando reconexión
- **Skyline**: Intenta extraer m3u8 de página con Playwright
  - Si falla: logs mostrarán error de Playwright, puede ser por:
    - Página requiere autenticación (actualmente no soportada)
    - JavaScript no genera el token m3u8
    - Problema de conectividad

### 3. Navegar en Módulo 1
1. Abre `http://localhost:8000/modulo1`
2. Verás selector de cámara en header derecho
3. Selecciona **CAM_002 (Skyline)** → debería ver stream en vivo
4. Selecciona **CAM_001 (Oracle)** → debería ver stream del servidor Oracle
5. Si alguna cámara no está disponible, verás gráfica vacía pero sin errores críticos

### 4. Navegar en Módulo 2
1. Abre `http://localhost:8000/modulo2`
2. Verás selector de cámara en form de filtros (primer dropdown)
3. Selecciona cámara + otros filtros (fecha, tipo)
4. Click en "Filtrar"
5. Gráficos y tabla se actualizan solo con datos de esa cámara

### 5. Exportar Reportes
- En Módulo 2, después de filtrar por cámara
- Click en "Exportar Excel"
- Archivo CSV incluye solo registros de cámara seleccionada
- Nombre del archivo incluye identificadores: `reporte_vehiculos_2025-12-10_143000_CAM_002.csv`

---

## Arquitectura de Datos

### Flujo de Frames
```
CAM_001 (Oracle)                          CAM_002 (Skyline)
    ↓                                           ↓
WebSocket Consumer (asyncio)          HLS Extractor (Playwright + OpenCV)
    ↓                                           ↓
process_frame_generic(frame, CAM_001) ← → process_frame_generic(frame, CAM_002)
    ↓                                           ↓
YOLO Inference + Tracking                YOLO Inference + Tracking
    ↓                                           ↓
camera_states['CAM_001']               camera_states['CAM_002']
    ↓                                           ↓
API /api/vehicles?camera_id=CAM_001    API /api/vehicles?camera_id=CAM_002
API /ws/stream?camera_id=CAM_001       API /ws/stream?camera_id=CAM_002
```

### Persistencia XML
```
storage/app/vehiculos_db.xml
├── deteccion (CAM_001)
├── deteccion (CAM_001)
├── deteccion (CAM_002)
└── deteccion (CAM_002)

Cada <deteccion> incluye <camara> y <nombre_camara>
```

### Frontend Query Strings
```
Modulo 1: Selector dropdown
  Selecciona CAM_001 → WebSocket URL: ws://localhost:8080/ws/stream?camera_id=CAM_001
  Selecciona CAM_002 → WebSocket URL: ws://localhost:8080/ws/stream?camera_id=CAM_002

Modulo 2: Form con método GET
  Envía: ?camera_id=CAM_002&fecha_inicio=2025-12-01&fecha_fin=2025-12-31&tipo=Auto
  Controller filtra XML por camera_id, tipo, rango de fechas
  Gráficos se regeneran con datos filtrados
```

---

## Troubleshooting

### El detector no inicia
**Síntoma**: `python vehiculo_detector.py` da error de import
**Solución**: Verificar que todas las dependencias están instaladas:
```powershell
pip install flask flask-sock websockets ultralytics opencv-python playwright
playwright install chromium
```

### CAM_001 (Oracle) no conecta
**Síntoma**: Logs muestran "WebSocket connection failed" repetidamente
**Posibles Causas**:
- Servidor Oracle no está accesible en `144.22.56.85:5000`
- Firewall bloquea conexión
- URL WebSocket es incorrecta

**Workaround**: El detector seguirá corriendo sin Oracle. CAM_002 funcionará normal.

### CAM_002 (Skyline) no ve frames
**Síntoma**: Logs muestran "HLS playlist is empty" o "Playwright extraction failed"
**Posibles Causas**:
- Página de Skyline requiere login (no soportado actualmente)
- Token JavaScript no se genera
- URL de streaming cambió

**Workaround**: Editar `CAMERAS_CONFIG['CAM_002']['hls_url']` con URL correcta o usar HLS directo si está disponible

### Modulo 1 muestra gráfica gris (sin stream)
**Síntoma**: Selector funciona, pero no hay video
**Chequeo**:
1. Abre console del navegador (F12)
2. Verifica que WebSocket conecta: `ws://localhost:8080/ws/stream?camera_id=CAM_002` ✅
3. Si WebSocket conecta pero sin frames: detector no está enviando frames (cámara fuente está caída)
4. Si WebSocket no conecta: Flask no está corriendo en puerto 8080

### Modulo 2 no filtra por cámara
**Síntoma**: Gráficos muestran datos de todas las cámaras
**Solución**:
1. Verifica que el selector tiene opción seleccionada (no vacía)
2. Click en "Filtrar" para recargar con parámetro `?camera_id=`
3. Revisa URL: debe incluir `camera_id=CAM_001` o `camera_id=CAM_002`
4. Si el XML está vacío, no habrá datos para mostrar

---

## Próximos Pasos (Opcional)

1. **Agregar autenticación a Skyline**:
   - Modificar `worker_skyline()` para loguear en página antes de Playwright
   - Usar cookies para mantener sesión

2. **Dashboard unificado**:
   - Nueva vista que muestre ambas cámaras lado a lado
   - Estadísticas combinadas (total vehículos, tipos, colores)

3. **Alertas por cámara**:
   - Notificaciones cuando se detectan vehículos específicos
   - Configuración diferente por cámara (ej: alertar si > 5 autos en CAM_001)

4. **Grabación por cámara**:
   - Guardar frames/video por cámara en carpeta diferente
   - Indexar por timestamp + camera_id

---

## Control de Versión
- **Creado**: 2025-12-10
- **Última Actualización**: 2025-12-10
- **Status**: Listo para Testing
