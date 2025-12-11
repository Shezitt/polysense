# 🎙️ Sistema de Reconocimiento de Voz LOCAL - Polysense

## ✅ Sistema Instalado y Funcionando

### 📦 Componentes

1. **Vosk** - Motor de reconocimiento de voz offline (sin internet)
2. **Flask-SocketIO** - Servidor WebSocket en Python
3. **Modelo español** - 50 MB para reconocimiento en español
4. **Cliente JavaScript** - Integración en Laravel

---

## 🚀 Inicio Rápido

### 1. Iniciar Servidor de Voz

```bash
cd /home/shezitt/Documents/polysense/deteccion
python voice_server.py
```

Verás:
```
✅ Modelo cargado
🚀 SERVIDOR DE RECONOCIMIENTO DE VOZ
📡 WebSocket: http://localhost:5001
```

### 2. Iniciar Laravel (nueva terminal)

```bash
cd /home/shezitt/Documents/polysense
php artisan serve
```

### 3. Usar la Aplicación

1. Abre: **http://localhost:8000**
2. Verás 🎤 **"Voz activa"** (esquina inferior izquierda)
3. Habla claramente cerca del micrófono

---

## 🎯 Comandos Disponibles

| **Di esto** | **Acción** |
|-------------|------------|
| "módulo uno" o "ir al monitor" | → Módulo 1 |
| "módulo dos" o "historial" | → Módulo 2 |
| "módulo tres" o "comandos de voz" | → Módulo 3 |
| "inicio" o "home" | → Página principal |
| "exportar" o "descargar excel" | Exportar datos |

---

## 🔍 Cómo Funciona

```
Micrófono → Python (Vosk) → WebSocket → JavaScript → Acción
```

1. **Python escucha** tu micrófono con Vosk
2. **Reconoce** el texto sin usar internet
3. **Envía** el comando por WebSocket
4. **JavaScript** busca el comando en la base de datos
5. **Ejecuta** la acción (navegar, exportar, etc.)

---

## 🧪 Probar el Servidor

### Página de Prueba
```bash
# Abre en el navegador:
http://localhost:5001
```

Verás una interfaz simple que muestra:
- Estado de conexión
- Comandos reconocidos
- Texto mientras hablas

### Terminal
En la terminal donde corre `voice_server.py` verás:
```
💬 Reconocido: módulo uno
✅ Cliente conectado (Total: 1)
🎤 Iniciando captura de audio...
```

---

## 🎨 Indicadores Visuales

### 1. Indicador de Voz Activa
- **Ubicación:** Esquina inferior izquierda
- **Color:** Azul con micrófono pulsante
- **Texto:** "Voz activa"

### 2. Texto Parcial
- **Ubicación:** Sobre el indicador
- **Muestra:** Lo que vas diciendo en tiempo real
- **Desaparece:** Al completar la frase

### 3. Notificación de Comando
- **Ubicación:** Esquina superior derecha
- **Duración:** 3 segundos
- **Muestra:** Comando ejecutado + texto reconocido

---

## ⚙️ Configuración

### Agregar Nuevos Comandos

1. Ve a **Módulo 3** (http://localhost:8000/modulo3)
2. Click en **"+ Nuevo Comando"**
3. Completa el formulario:
   - **Nombre:** "Ir al Dashboard"
   - **Triggers:** "dashboard,panel,tablero"
   - **Acción:** Navigate
   - **Target:** /dashboard
4. Guardar

¡Ahora puedes decir "dashboard" para ir a esa página!

### Modificar Comandos Existentes

Todos los comandos están en: `/api/voice-commands`

---

## 🐛 Solución de Problemas

### No se conecta al servidor
```bash
# Verifica que el servidor esté corriendo:
curl http://localhost:5001

# Debe responder con la página de prueba
```

### No reconoce comandos
1. **Habla más fuerte** - El micrófono debe captarte bien
2. **Acércate** al micrófono
3. **Di claramente** las palabras
4. **Revisa triggers** - Debe coincidir exactamente

### Error "Modelo no encontrado"
```bash
# Verifica que exista la carpeta:
ls -la /home/shezitt/Documents/polysense/deteccion/model/

# Debe tener carpetas: am, conf, graph, ivector
```

### No aparece el indicador azul
1. Abre **Consola del Navegador** (F12)
2. Busca errores de conexión
3. Verifica que Socket.IO se cargue correctamente

---

## 📊 Monitoreo

### Ver Logs del Servidor
```bash
# En la terminal donde corre voice_server.py verás:
✅ Cliente conectado (Total: 1)
💬 Reconocido: inicio
   ➡️  Acción: Ir al inicio
```

### Ver Logs en el Navegador
```javascript
// Abre Consola (F12) y verás:
✅ Conectado al servidor de voz
📋 5 comandos cargados
💬 Comando recibido: inicio
✅ Comando encontrado: Ir al Inicio
➡️  Navegando a: /
```

---

## 🚀 Ventajas del Sistema

✅ **100% Offline** - No requiere internet  
✅ **Sin bloqueos** - No depende del navegador  
✅ **Privado** - Nada se envía a servidores externos  
✅ **Rápido** - Latencia < 100ms  
✅ **Configurable** - Agrega comandos desde la interfaz  
✅ **Multi-módulo** - Funciona en toda la aplicación  

---

## 📝 Archivos Importantes

```
deteccion/
├── voice_server.py              # Servidor WebSocket
├── voice_recognition_local.py   # Script standalone
├── model/                       # Modelo de Vosk (50 MB)
└── voice_requirements.txt       # Dependencias

public/js/
└── voice-websocket.js           # Cliente JavaScript

resources/views/
└── layouts/app.blade.php        # Layout con indicadores
```

---

## 🎓 Próximos Pasos

1. ✅ **Sistema funcionando** - Ya tienes todo instalado
2. 🔧 **Agregar comandos** - Ve a Módulo 3
3. 🎨 **Personalizar** - Modifica colores/posiciones de indicadores
4. 📱 **Optimizar** - Ajusta triggers para mejor precisión
5. 🚀 **Producción** - Usa Gunicorn en lugar de Flask dev server

---

## 🌟 Comandos Rápidos

### Iniciar Todo
```bash
# Terminal 1 - Servidor de Voz
cd /home/shezitt/Documents/polysense/deteccion
python voice_server.py

# Terminal 2 - Laravel
cd /home/shezitt/Documents/polysense
php artisan serve

# Terminal 3 - Detector de Vehículos (opcional)
cd /home/shezitt/Documents/polysense/deteccion
python vehiculo_detector.py
```

### Verificar Estado
```bash
# Servidor de voz
curl http://localhost:5001

# API de comandos
curl http://localhost:8000/api/voice-commands

# Laravel
curl http://localhost:8000
```

---

## ✨ ¡Disfruta tu Sistema de Voz!

Ahora puedes controlar tu aplicación completamente con comandos de voz, sin depender de internet ni de servicios externos. 🎉
