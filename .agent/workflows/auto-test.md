---
description: Ejecutar todos los tests automáticamente sin intervención
---

# Workflow: Tests Automáticos Completos

Este workflow ejecuta todos los tests del sistema de forma completamente automatizada.

## Tests Automatizados

### 1. Tests de Base de Datos
// turbo
```bash
php artisan test --filter=Database
```

### 2. Tests de Controladores
// turbo
```bash
php artisan test --filter=Controller
```

### 3. Tests de API
// turbo
```bash
php artisan test --filter=Api
```

### 4. Tests de Comandos de Voz
// turbo
```bash
php artisan test --filter=VoiceCommand
```

### 5. Tests de Detección de Vehículos
// turbo
```bash
php artisan test --filter=Vehicle
```

### 6. Tests Completos con Coverage
// turbo
```bash
php artisan test --coverage
```

### 7. Verificar integridad de la base de datos
// turbo
```bash
php artisan tinker --execute="echo 'Tables check:'; echo 'voice_commands: ' . DB::table('voice_commands')->count(); echo PHP_EOL; echo 'detections: ' . DB::table('detections')->count(); echo PHP_EOL; echo 'cameras: ' . DB::table('cameras')->count();"
```

### 8. Verificar rutas
// turbo
```bash
php artisan route:list --compact
```

## Resultado Esperado

✅ Todos los tests deben pasar
✅ No debe haber errores de base de datos
✅ Todas las rutas deben estar registradas
