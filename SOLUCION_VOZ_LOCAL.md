# 🎙️ SOLUCIÓN: Reconocimiento de Voz LOCAL (Sin Internet)

## ❌ Problema
El navegador bloquea Web Speech API con error "network" a pesar de tener internet.

## ✅ Solución Alternativa: VOSK (Reconocimiento Local)

### Ventajas
- ✅ **100% OFFLINE** - No requiere internet
- ✅ No hay problemas con extensiones del navegador
- ✅ No depende de servidores de Google
- ✅ Privacidad total - nada se envía a servidores externos
- ✅ Funciona en español e inglés

### Desventajas
- ⚠️ Requiere descargar modelo (50-150 MB)
- ⚠️ Precisión ligeramente menor que Google Speech
- ⚠️ Corre en Python (backend) no en navegador

---

## 📥 Instalación

### 1. Instalar dependencias
```bash
cd /home/shezitt/Documents/polysense/deteccion
pip install -r voice_requirements.txt
```

### 2. Descargar modelo de español
```bash
# Modelo pequeño español (50 MB)
wget https://alphacephei.com/vosk/models/vosk-model-small-es-0.42.zip

# Descomprimir
unzip vosk-model-small-es-0.42.zip

# Renombrar carpeta
mv vosk-model-small-es-0.42 model
```

### 3. Probar reconocimiento
```bash
python voice_recognition_local.py
```

---

## 🎯 Uso

### Modo básico (español)
```bash
python voice_recognition_local.py
```

### Modo inglés
```bash
python voice_recognition_local.py en
```

Cuando veas "🎤 Escuchando...", simplemente habla:
- "módulo uno" → Ir al Módulo 1
- "módulo dos" → Ir al Módulo 2  
- "exportar" → Exportar a Excel
- "inicio" → Ir a la página principal

---

## 🔗 Integración con Laravel

### Opción A: WebSocket Server
Crear un servidor WebSocket que:
1. Escuche comandos de voz con Vosk
2. Envíe comandos al navegador vía WebSocket
3. JavaScript ejecuta la acción

### Opción B: HTTP Polling
1. Python escribe comandos en archivo JSON
2. JavaScript hace polling cada 500ms
3. Ejecuta comandos encontrados

### Opción C: API REST
1. Python ejecuta en background
2. Expone endpoint `/voice/status`
3. Laravel consulta último comando

---

## 📊 Modelos Disponibles

### Español
- **Small** (50 MB): `vosk-model-small-es-0.42.zip` ← **Recomendado**
- **Large** (1.5 GB): `vosk-model-es-0.42.zip` (mejor precisión)

### Inglés
- **Small** (40 MB): `vosk-model-small-en-us-0.15.zip`
- **Large** (1.8 GB): `vosk-model-en-us-0.22.zip`

Descarga desde: https://alphacephei.com/vosk/models

---

## 🐛 Troubleshooting

### Error: "Modelo no encontrado"
```bash
# Verifica que exista la carpeta model/
ls -la /home/shezitt/Documents/polysense/deteccion/model/

# Debe contener archivos como:
# am/final.mdl, graph/HCLG.fst, ivector/, etc.
```

### Error: "No se encuentra sounddevice"
```bash
# Instalar dependencias del sistema
sudo apt-get install portaudio19-dev python3-pyaudio
pip install sounddevice
```

### No reconoce nada
- Verifica micrófono: `arecord -l`
- Habla más cerca del micrófono
- Usa el modelo Large para mejor precisión

---

## 🚀 Próximos Pasos

1. **Instalar Vosk**: `pip install -r voice_requirements.txt`
2. **Descargar modelo**: Ejecuta comandos de "Instalación"
3. **Probar**: `python voice_recognition_local.py`
4. **Integrar**: Elige Opción A, B o C según tu preferencia

---

## 💡 Recomendación

Para tu proyecto, sugiero **Opción A (WebSocket)**:
- Respuesta en tiempo real
- Baja latencia
- Fácil de implementar con Flask-SocketIO

¿Quieres que implemente la integración WebSocket?
