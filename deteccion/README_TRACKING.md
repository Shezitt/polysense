# Sistema de Tracking de Vehículos Únicos

## 🎯 Problema Resuelto

**Antes:** El sistema contaba cada detección en cada fotograma como un vehículo nuevo, causando que el contador "Total Detectados" aumentara a la velocidad de los FPS.

**Ahora:** Sistema de tracking inteligente que identifica vehículos únicos y los cuenta solo una vez.

## 🔧 Cómo Funciona

### 1. **Asignación de ID Único**
- Cada vehículo detectado por primera vez recibe un ID único (1, 2, 3, ...)
- El ID se muestra en el video: `ID:1 car (blanco) 0.85`

### 2. **Tracking por Posición**
- Calcula el centro de cada bounding box detectado
- Compara con vehículos previamente rastreados
- Si la distancia es menor a 100 píxeles, se considera el mismo vehículo
- Solo se actualiza la posición, NO se incrementa el contador

### 3. **Limpieza Automática**
- Si un vehículo no se detecta por 30 frames consecutivos, se elimina del tracking
- Esto permite que vehículos que salen y vuelven a entrar se cuenten nuevamente

## ⚙️ Parámetros Configurables

```python
'max_distance': 100      # Distancia máxima (px) para considerar mismo vehículo
'max_frames_missing': 30 # Frames sin ver antes de eliminar del tracking
```

## 📊 Nuevos Datos en el API

```json
{
  "total_detected": 5,              // Vehículos únicos contados
  "unique_vehicles_tracked": 3,     // Vehículos actualmente siendo rastreados
  "current_vehicles": 3              // Vehículos detectados en el frame actual
}
```

## 🔄 Endpoint de Reset

Para resetear el contador si es necesario:

```bash
curl -X POST http://localhost:8080/api/reset
```

## 📈 Flujo de Detección

```
Frame N: Detecta vehículo en (100, 200)
  ├─> No existe tracking cercano
  ├─> Crear ID:1
  └─> total_detected = 1

Frame N+1: Detecta vehículo en (102, 205)
  ├─> Existe ID:1 a 5px de distancia
  ├─> Actualizar posición de ID:1
  └─> total_detected = 1 (sin cambio)

Frame N+2: Detecta vehículo en (105, 210) y (500, 300)
  ├─> ID:1 actualizado (cerca de su última posición)
  ├─> Nuevo vehículo detectado a 400px de ID:1
  ├─> Crear ID:2
  └─> total_detected = 2
```

## 🎨 Visualización

En el video procesado verás:
- **Bounding boxes** verdes alrededor de cada vehículo
- **ID único** de cada vehículo: `ID:1`, `ID:2`, etc.
- **Tipo y color**: `car (blanco)`, `motorcycle (negro)`
- **Confianza**: `0.85` (85% de certeza)

## 🔍 Verificación

Para verificar que funciona correctamente:

1. Inicia el detector: `python vehiculo_detector.py`
2. Observa el video en Laravel (Módulo 1)
3. Verás que los vehículos mantienen su ID mientras están en pantalla
4. El contador "Total Detectados" solo aumenta cuando aparece un vehículo NUEVO
5. Si un vehículo sale y vuelve después de 30 frames, se contará como nuevo

## 💡 Mejoras Futuras Posibles

- **Tracking más sofisticado**: Usar algoritmos como DeepSORT o ByteTrack
- **Líneas de conteo**: Contar solo cuando cruzan una línea específica
- **Dirección del movimiento**: Detectar si van hacia arriba/abajo/izquierda/derecha
- **Persistencia de IDs**: Guardar IDs en base de datos para sesiones largas
