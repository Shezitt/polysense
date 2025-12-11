# 🎤 Sistema de Comandos de Voz - Módulo 3

## Descripción

El Módulo 3 permite configurar comandos de voz personalizados para controlar toda la aplicación PolySense mediante reconocimiento de voz. Utiliza la **Web Speech API** del navegador para reconocer comandos en español.

## ✨ Características

### 1. **Gestión de Comandos**
- ✅ Crear comandos personalizados
- ✅ Editar comandos existentes
- ✅ Eliminar comandos
- ✅ Activar/Desactivar comandos
- ✅ Grabar palabras clave por voz

### 2. **Tipos de Acciones**
- **Navegación**: Cambiar entre módulos
- **Exportar**: Descargar datos a Excel
- **Toggle**: Activar/Desactivar funciones
- **Personalizada**: Ejecutar funciones JavaScript

### 3. **Configuración Flexible**
- Múltiples palabras clave por comando (separadas por coma)
- Seleccionar módulos donde el comando estará activo
- Sistema de similitud para reconocer variaciones de palabras

## 🚀 Uso

### Activar Comandos de Voz

**3 formas de activar:**

1. **Atajo de teclado**: `Ctrl + Shift + V`
2. **Botón global**: Click en el icono 🎤 en la barra de navegación
3. **Botón en Módulo 3**: "Probar Reconocimiento"

### Comandos Predeterminados

| Comando | Palabras Clave | Acción | Módulos |
|---------|---------------|--------|---------|
| Ir al Módulo 1 | módulo uno, ir al monitor, monitoreo | Navega al Módulo 1 | Todos |
| Ir al Módulo 2 | módulo dos, ir al historial, estadísticas | Navega al Módulo 2 | Todos |
| Ir al Módulo 3 | módulo tres, configurar voz, comandos de voz | Navega al Módulo 3 | Todos |
| Exportar a Excel | exportar, descargar excel, guardar datos | Descarga datos | Módulo 2 |
| Ir al Inicio | inicio, página principal, home | Navega al inicio | Todos |

## 📋 Crear Nuevo Comando

1. Accede al **Módulo 3**
2. Click en **"Agregar Nuevo Comando"**
3. Completa el formulario:
   - **Nombre**: Descripción del comando
   - **Palabras Clave**: Una o varias palabras separadas por coma
   - **Tipo de Acción**: Selecciona qué hará el comando
   - **Configuración específica**: Según el tipo de acción
   - **Módulos activos**: Dónde estará disponible
4. Click en **"Guardar Comando"**

### Ejemplo: Crear comando "Actualizar Datos"

```
Nombre: Actualizar Datos
Palabras Clave: actualizar, refrescar, recargar
Tipo de Acción: Personalizada
Función JavaScript: location.reload()
Módulos: Todos
```

## 🎯 Tipos de Acción en Detalle

### 1. Navegación
Cambia de módulo o página.

**Configuración:**
- **Target**: URL destino (`/modulo1`, `/modulo2`, etc.)

**Ejemplo:**
- Palabra: "ir al inicio"
- Target: `/`

### 2. Exportar
Descarga datos a Excel.

**Configuración:**
- Automático para Módulo 2
- Llama a `window.exportToExcel()`

**Ejemplo:**
- Palabra: "exportar datos"

### 3. Toggle
Activa/desactiva funciones.

**Configuración:**
- **Target**: Nombre de la función a alternar

**Ejemplo:**
- Palabra: "alternar cámara"
- Target: "camera"

### 4. Personalizada
Ejecuta cualquier función JavaScript.

**Configuración:**
- **Función**: Nombre de función global

**Ejemplo:**
- Palabra: "limpiar filtros"
- Función: `clearFilters()`

## 🔧 Integración Técnica

### Estructura de Base de Datos

```sql
voice_commands
├── id
├── name              VARCHAR(255)    -- Nombre descriptivo
├── trigger           VARCHAR(500)    -- Palabras clave (separadas por coma)
├── action            ENUM            -- navigate, export, toggle, custom
├── target            VARCHAR(255)    -- URL o nombre de función
├── function_name     VARCHAR(255)    -- Función JS personalizada
├── modules           VARCHAR(255)    -- Módulos donde está activo
├── enabled           BOOLEAN         -- Si está activo
├── created_at
└── updated_at
```

### API Endpoints

```
GET    /api/voice-commands              # Listar todos
GET    /api/voice-commands/active/{module} # Comandos activos
POST   /api/voice-commands              # Crear comando
GET    /api/voice-commands/{id}         # Obtener uno
PUT    /api/voice-commands/{id}         # Actualizar
DELETE /api/voice-commands/{id}         # Eliminar
POST   /api/voice-commands/{id}/toggle  # Activar/Desactivar
```

### JavaScript Global

El archivo `/public/js/voice-commands.js` define la clase `VoiceCommandSystem`:

```javascript
// Sistema global disponible en toda la app
window.voiceCommandSystem

// Métodos principales:
.startListening()       // Iniciar escucha
.stopListening()        // Detener escucha
.processCommand(text)   // Procesar comando
.loadCommands()         // Recargar comandos
```

### Agregar Funciones Personalizadas

Para que un comando personalizado funcione, la función debe estar en el scope global:

```javascript
// En tu módulo, agrega:
window.tuFuncion = function() {
    // Tu código aquí
    console.log('Función ejecutada por voz');
};
```

## 🎨 Personalización

### Cambiar Idioma de Reconocimiento

Edita `/public/js/voice-commands.js`:

```javascript
this.recognition.lang = 'es-ES'; // Español (España)
// Opciones:
// 'es-MX' - Español (México)
// 'es-AR' - Español (Argentina)
// 'en-US' - Inglés
```

### Ajustar Sensibilidad

```javascript
// Cambiar umbral de similitud (0.0 - 1.0)
return similarity > 0.8; // 80% de similitud

// Más estricto: 0.9 (90%)
// Más flexible: 0.7 (70%)
```

### Cambiar Atajo de Teclado

```javascript
// En voice-commands.js, cambia:
if (e.ctrlKey && e.shiftKey && e.key === 'V') {
    // Por ejemplo: Alt + V
    if (e.altKey && e.key === 'v') {
```

## 🐛 Solución de Problemas

### "Web Speech API no soportada"
- Usa Chrome, Edge o Safari (versiones recientes)
- Firefox tiene soporte limitado

### "Permiso de micrófono denegado"
1. Click en el candado 🔒 junto a la URL
2. Permitir acceso al micrófono
3. Recargar la página

### "Comando no reconocido"
- Verifica que el comando esté **activo** (✅)
- Habla claro y despacio
- Prueba con diferentes palabras clave
- Revisa que el módulo esté configurado correctamente

### Comandos no se cargan
```bash
# Verificar que la tabla existe
php artisan migrate

# Ver comandos en BD
php artisan tinker
DB::table('voice_commands')->get();
```

## 🌐 Compatibilidad de Navegadores

| Navegador | Soporte | Notas |
|-----------|---------|-------|
| Chrome | ✅ Completo | Recomendado |
| Edge | ✅ Completo | Chromium |
| Safari | ✅ Completo | macOS/iOS |
| Firefox | ⚠️ Parcial | Soporte limitado |
| Opera | ✅ Completo | Chromium |

## 📱 Móviles

- ✅ Android Chrome: Funciona perfectamente
- ✅ iOS Safari: Funciona (requiere interacción del usuario)
- ❌ Apps WebView: Soporte limitado

## 🔐 Seguridad

- Los comandos personalizados usan `eval()` con precaución
- Solo funciones en el scope global pueden ejecutarse
- Validación de entrada en el backend
- Sin almacenamiento de audio (solo texto)

## 📚 Recursos

- [Web Speech API - MDN](https://developer.mozilla.org/en-US/docs/Web/API/Web_Speech_API)
- [SpeechRecognition](https://developer.mozilla.org/en-US/docs/Web/API/SpeechRecognition)
- [Can I Use - Speech Recognition](https://caniuse.com/speech-recognition)

## 💡 Ideas Futuras

- [ ] Comandos con parámetros dinámicos
- [ ] Feedback de voz (Text-to-Speech)
- [ ] Hotwords siempre activos
- [ ] Macros (secuencia de comandos)
- [ ] Importar/Exportar configuración
- [ ] Modo manos libres continuo
- [ ] Reconocimiento multilenguaje
- [ ] Comandos por usuario

## 👨‍💻 Desarrollo

### Agregar Nuevo Tipo de Acción

1. **Backend** - `VoiceCommandController.php`:
```php
'action' => 'required|in:navigate,export,toggle,custom,tuNuevaAccion'
```

2. **Frontend** - `modulo3.blade.php`:
```html
<option value="tuNuevaAccion">Tu Nueva Acción</option>
```

3. **JavaScript** - `voice-commands.js`:
```javascript
case 'tuNuevaAccion':
    this.ejecutarNuevaAccion(command);
    break;
```

### Testing

```bash
# Probar reconocimiento
1. Ir al Módulo 3
2. Click en "Probar Reconocimiento"
3. Decir una palabra clave

# Ver logs del navegador
F12 > Console
# Verás: 🎤 Escuchando...
#       📝 Reconocido: "tu texto"
#       ✅ Comando encontrado: ...
```

## 📞 Soporte

Si encuentras problemas:
1. Revisa la consola del navegador (F12)
2. Verifica permisos del micrófono
3. Comprueba que los comandos están activos
4. Revisa los logs de Laravel

---

**Desarrollado para PolySense** 🚗📡
Sistema de Monitoreo Vehicular con IA
