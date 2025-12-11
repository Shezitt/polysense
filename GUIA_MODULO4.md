# 🎤 Guía del Módulo 4 - Comandos de Voz

## ¿Qué es el Módulo 4?

El Módulo 4 te permite **controlar el sistema Polysense con tu voz** sin usar el mouse ni el teclado. Puedes navegar entre módulos, exportar datos y más, solo hablando.

---

## 🚀 Inicio Rápido

### 1. Iniciar el servidor de voz

Abre una terminal y ejecuta:

```bash
cd /home/shezitt/Documents/polysense
python deteccion/voice_server.py
```

Verás:
```
✅ Modelo cargado
🎤 Audio iniciado - siempre activo
 * Running on http://127.0.0.1:5001
```

### 2. Verificar conexión

- Abre tu navegador en `http://localhost:8000`
- Busca el **indicador azul** en la esquina inferior izquierda que dice **"Voz activa"**
- Si lo ves, ¡estás listo!

### 3. Usar comandos

Simplemente **habla** uno de los comandos configurados:

- **"inicio"** → Va al Módulo 1
- **"módulo dos"** → Va al Módulo 2  
- **"exportar"** → Descarga Excel (solo en Módulo 2)
- **"comandos de voz"** → Abre el Módulo 4

---

## ⚙️ Configurar Nuevos Comandos

### Paso 1: Ir al Módulo 4

1. Ve a `http://localhost:8000/modulo4`
2. O di: **"comandos de voz"**

### Paso 2: Crear comando

1. Haz clic en **"Nuevo Comando"**
2. Completa el formulario:

| Campo | Descripción | Ejemplo |
|-------|-------------|---------|
| **Nombre** | Nombre descriptivo del comando | "Ir al Módulo 1" |
| **Palabras clave** | Frases que activan el comando (separadas por espacios) | `inicio home página principal` |
| **Acción** | Qué hará el comando | Navegar a URL |
| **Objetivo** | Depende de la acción (URL, mensaje, función) | `/modulo1` |
| **Módulos activos** | En qué módulos funciona | ✅ Módulo 1, 2, 4 |

### Paso 3: Guardar

Haz clic en **"Guardar"**. El comando estará activo inmediatamente.

---

## 📋 Tipos de Acciones

### 1. Navegar a URL
**Uso:** Ir a otra página del sistema

**Ejemplo:**
- Nombre: `Ir al Módulo 1`
- Palabras clave: `inicio home página principal`
- Acción: `Navegar a URL`
- Objetivo: `/modulo1`

### 2. Mostrar Alerta
**Uso:** Mostrar un mensaje en pantalla

**Ejemplo:**
- Nombre: `Recordatorio importante`
- Palabras clave: `recordatorio aviso`
- Acción: `Mostrar alerta`
- Objetivo: `No olvides revisar los datos`

### 3. Ejecutar Función
**Uso:** Ejecutar código JavaScript personalizado

**Ejemplo:**
- Nombre: `Actualizar página`
- Palabras clave: `refrescar actualizar recargar`
- Acción: `Ejecutar función`
- Función: `location.reload`

---

## 🎯 Mejores Prácticas

### ✅ Palabras clave efectivas

**Bueno:**
```
inicio, home, página principal, volver al inicio
```
- Múltiples variantes
- Palabras naturales
- Frases comunes

**Malo:**
```
iralinic
```
- Una sola palabra rara
- Difícil de pronunciar

### ✅ Nombres descriptivos

**Bueno:**
```
"Ir al Módulo 2 - Historial"
```

**Malo:**
```
"M2"
```

### ✅ Módulos específicos

- Si un comando solo tiene sentido en un módulo (como "exportar" en Módulo 2), actívalo solo ahí
- Los comandos de navegación deberían estar en **todos los módulos**

---

## 🔧 Troubleshooting

### ❌ No aparece el indicador "Voz activa"

**Solución:**
1. Verifica que el servidor esté corriendo: `python deteccion/voice_server.py`
2. Revisa que no haya errores en la terminal
3. Recarga la página (F5)

### ❌ El sistema no reconoce mi voz

**Solución:**
1. Habla más cerca del micrófono
2. Habla más despacio y claro
3. Verifica que el micrófono funcione: `arecord -l` (Linux)

### ❌ El comando no se ejecuta

**Solución:**
1. Ve al Módulo 4 y verifica que el comando esté **habilitado** (toggle verde)
2. Revisa que las palabras clave incluyan lo que dijiste
3. Mira la consola del navegador (F12) para ver qué texto se reconoció

### ❌ Error: "Servidor inactivo"

**Solución:**
1. El servidor de voz no está corriendo
2. Ejecuta: `python deteccion/voice_server.py`
3. Espera a ver "✅ Stream de audio iniciado"

---

## 📊 Comandos Predeterminados

Estos comandos vienen pre-configurados:

| Comando | Palabras clave | Acción |
|---------|----------------|--------|
| Ir al Inicio | inicio, home, página principal | → Módulo 1 |
| Ir al Módulo 2 | módulo dos, historial, estadísticas | → Módulo 2 |
| Ir al Módulo 4 | módulo cuatro, comandos de voz, configurar voz | → Módulo 4 |
| Exportar Excel | exportar, descargar excel, guardar datos | Descarga Excel (solo Módulo 2) |

---

## 🎤 Sistema de Reconocimiento

**Tecnología:** Vosk (reconocimiento local en español)
- ✅ 100% offline - no requiere internet
- ✅ Privacidad total - nada se envía a servidores externos
- ✅ Rápido y preciso
- ✅ Funciona en español

**Arquitectura:**
```
Micrófono → Python (Vosk) → WebSocket → Laravel → Acción
```

---

## 💡 Ideas de Comandos Útiles

1. **"ayuda"** → Muestra alerta con lista de comandos
2. **"cerrar sesión"** → Hace logout
3. **"modo oscuro"** → Cambia tema (si implementado)
4. **"buscar placa ABC123"** → Busca vehículo (función JS)
5. **"última hora"** → Filtra datos de última hora

---

## 🆘 Soporte

Si tienes problemas:
1. Revisa los logs del servidor: `python deteccion/voice_server.py`
2. Abre la consola del navegador (F12)
3. Verifica que el modelo de Vosk esté descargado en `/deteccion/model/`

**Archivos importantes:**
- `/deteccion/voice_server.py` - Servidor de reconocimiento
- `/public/js/voice-websocket.js` - Cliente JavaScript
- `/app/Http/Controllers/VoiceCommandController.php` - Lógica de comandos
- `/database/migrations/*_voice_commands_table.php` - Estructura de BD
