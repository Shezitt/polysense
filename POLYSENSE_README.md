# 📡 Polysense - Monitor de Vehículos Inteligente

Sistema de monitoreo de vehículos con detección por IA y control por voz.

## ✨ Características

- 🚗 **Detección de vehículos** con YOLOv8
- 🎙️ **Control por voz** offline (Vosk)
- 📊 **Análisis estadístico** en tiempo real
- 📥 **Exportación** a Excel
- 🌐 **Interfaz web** moderna con Tailwind CSS

---

## 🚀 Inicio Rápido

### Opción 1: Script automático
```bash
./start.sh
```

### Opción 2: Manual
```bash
# Terminal 1 - Servidor de voz
cd deteccion && python voice_server.py

# Terminal 2 - Laravel
php artisan serve
```

Luego abre: **http://localhost:8000**

---

## 🎤 Comandos de Voz

Una vez en la aplicación, di:

- **"módulo uno"** → Monitor en vivo
- **"módulo dos"** → Historial y estadísticas
- **"módulo tres"** → Configuración de voz
- **"inicio"** → Página principal
- **"exportar"** → Descargar Excel

El indicador 🎤 **"Voz activa"** aparece cuando el sistema está listo.

---

## 📁 Estructura del Proyecto

```
polysense/
├── app/                    # Backend Laravel
├── resources/              # Vistas y assets
├── public/                 # Assets públicos
│   └── js/
│       └── voice-websocket.js
├── deteccion/              # Sistema de IA
│   ├── voice_server.py     # Servidor de voz
│   ├── vehiculo_detector.py
│   └── model/              # Modelo Vosk (50 MB)
├── database/
│   └── migrations/         # Incluye voice_commands
└── start.sh               # Inicio automático
```

---

## ⚙️ Requisitos

- Python 3.8+
- PHP 8.1+ con SQLite
- Micrófono (para comandos de voz)
- 200 MB de espacio (modelo de voz incluido)

---

## 📖 Documentación

- **GUIA_VOZ_LOCAL.md** - Sistema de reconocimiento de voz completo
- **deteccion/** - Detector de vehículos y servidor de voz

---

## 🔧 Configuración

### Agregar nuevos comandos de voz

1. Ve a **Módulo 3** (http://localhost:8000/modulo3)
2. Click en **"+ Nuevo Comando"**
3. Define nombre, triggers y acción
4. ¡Listo! El comando está disponible inmediatamente

### Comandos disponibles

Todos los comandos están en la tabla `voice_commands` (SQLite).

---

## 🐛 Troubleshooting

### No aparece el indicador de voz
- Verifica que `voice_server.py` esté corriendo
- El servidor debe mostrar: `Running on http://127.0.0.1:5001`

### No reconoce comandos
- Habla claro y cerca del micrófono
- Verifica que los comandos estén habilitados en Módulo 3
- Los triggers deben coincidir con lo que dices

### Error al iniciar
- Instala dependencias: `pip install -r deteccion/voice_requirements.txt`
- Verifica que el modelo esté en `deteccion/model/`

---

## 🌟 Funcionalidades Principales

### Módulo 1: Monitor en Vivo
- Detección en tiempo real
- Conteo de vehículos por tipo
- WebSocket para actualizaciones instantáneas

### Módulo 2: Historial
- Estadísticas por día/mes
- Gráficos interactivos
- Exportación a Excel

### Módulo 3: Comandos de Voz
- CRUD de comandos personalizados
- Configuración de triggers
- Panel de pruebas

---

## 📄 Licencia

MIT

---

## 👨‍💻 Desarrollado con

- Laravel 11
- Python + Vosk + YOLOv8
- Tailwind CSS
- Flask-SocketIO
- Socket.IO Client
