# 🤖 Configuración de Automatización para PolySense

Este directorio contiene workflows automatizados para el desarrollo de PolySense.

## 📋 Workflows Disponibles

### 1. `/modulo3-dev` - Desarrollo del Módulo 3
Workflow completo para desarrollar el módulo 3 en una rama separada con tests automáticos.

**Uso:**
```
Dile al asistente: "ejecuta el workflow modulo3-dev"
```

**Qué hace:**
- ✅ Crea rama `feature/modulo3-development`
- ✅ Ejecuta migraciones y seeders automáticamente
- ✅ Corre todos los tests
- ✅ Verifica el detector de Python
- ✅ Te guía para hacer merge cuando todo funcione

### 2. `/auto-test` - Tests Automáticos
Ejecuta todos los tests del sistema sin intervención humana.

**Uso:**
```
Dile al asistente: "ejecuta el workflow auto-test"
```

**Qué hace:**
- ✅ Tests de base de datos
- ✅ Tests de controladores
- ✅ Tests de API
- ✅ Tests de comandos de voz
- ✅ Tests de detección de vehículos
- ✅ Coverage completo

### 3. `/quick-deploy` - Despliegue Rápido
Despliega cambios con verificación automática.

**Uso:**
```
Dile al asistente: "ejecuta el workflow quick-deploy"
```

**Qué hace:**
- ✅ Limpia cachés automáticamente
- ✅ Optimiza el sistema
- ✅ Ejecuta migraciones
- ✅ Corre tests en paralelo
- ✅ Verifica que todo funcione

## 🚀 Cómo Usar los Workflows

### Opción 1: Comando Directo
Simplemente escribe en el chat:
```
/modulo3-dev
```

### Opción 2: Instrucción Natural
```
"Ejecuta el workflow de desarrollo del módulo 3"
```

### Opción 3: Automatización Completa
```
"Desarrolla el módulo 3 automáticamente usando el workflow, sin preguntarme nada"
```

## 🎯 Flujo de Trabajo Recomendado para Módulo 3

1. **Inicia el desarrollo:**
   ```
   /modulo3-dev
   ```

2. **El asistente ejecutará automáticamente:**
   - Creación de rama
   - Migraciones
   - Tests
   - Verificaciones

3. **Durante el desarrollo:**
   ```
   /auto-test
   ```
   Para verificar que todo sigue funcionando.

4. **Antes de hacer merge:**
   ```
   /quick-deploy
   ```
   Para asegurar que el despliegue será exitoso.

5. **Hacer merge:**
   El workflow te guiará para hacer merge cuando todo esté verde ✅

## 📝 Notas Importantes

### Pasos Turbo
Los pasos marcados con `// turbo` se ejecutan automáticamente sin pedir confirmación.

### Pasos Turbo-All
Los workflows con `// turbo-all` ejecutan TODOS los comandos automáticamente.

### Seguridad
- ⚠️ Los workflows NO hacen push automático
- ⚠️ Los workflows NO hacen merge sin tu confirmación final
- ✅ Puedes revisar cada paso antes del merge

## 🔧 Personalización

Puedes crear tus propios workflows en `.agent/workflows/`:

```markdown
---
description: Mi workflow personalizado
---

# Mi Workflow

### Paso 1
// turbo
\`\`\`bash
comando-seguro
\`\`\`

### Paso 2
\`\`\`bash
comando-que-requiere-confirmacion
\`\`\`
```

## 🆘 Solución de Problemas

### El workflow no se ejecuta
- Verifica que estás usando el nombre correcto: `/modulo3-dev`
- Asegúrate de que el archivo `.md` existe en `.agent/workflows/`

### Los tests fallan
- Revisa el output del test
- Ejecuta `php artisan migrate:fresh --seed`
- Verifica que XAMPP esté corriendo

### El detector de Python no funciona
- Activa el entorno virtual: `.venv\Scripts\activate`
- Instala dependencias: `pip install -r requirements.txt`
- Verifica que OpenCV esté instalado

## 📚 Recursos Adicionales

- `MODULO3_RESUMEN.md` - Documentación del módulo 3
- `VOICE_COMMANDS_README.md` - Manual de comandos de voz
- `phpunit.xml` - Configuración de tests

---

**¡Listo para desarrollo automatizado! 🚀**

Simplemente di: **"/modulo3-dev"** y el asistente hará todo el trabajo.
