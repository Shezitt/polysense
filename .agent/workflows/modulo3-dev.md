---
description: Desarrollo automatizado del Módulo 3 en rama separada
---

# Workflow: Desarrollo Automatizado del Módulo 3

Este workflow te permite desarrollar el Módulo 3 de forma completamente automatizada, con tests y todo en una rama separada.

## Pasos del Workflow

### 1. Crear y cambiar a la rama de desarrollo
```bash
git checkout -b feature/modulo3-development
```

### 2. Verificar estado actual del módulo 3
```bash
php artisan route:list --path=modulo3
```

### 3. Ejecutar migraciones y seeders
// turbo
```bash
php artisan migrate:fresh --seed
```

### 4. Verificar la base de datos
// turbo
```bash
php artisan tinker --execute="echo 'Voice Commands: ' . DB::table('voice_commands')->count(); echo PHP_EOL; echo 'Detections: ' . DB::table('detections')->count();"
```

### 5. Ejecutar tests de PHPUnit
// turbo
```bash
php artisan test --filter=VoiceCommand
```

### 6. Iniciar servidor de desarrollo (si no está corriendo)
```bash
php artisan serve
```

### 7. Verificar que el detector de Python funciona
```bash
python run_detector.py
```

### 8. Ejecutar tests de integración completos
// turbo
```bash
php artisan test
```

### 9. Verificar cambios realizados
// turbo
```bash
git status
```

### 10. Hacer commit de los cambios (cuando estés listo)
```bash
git add .
git commit -m "feat(modulo3): implementación completa del módulo 3"
```

### 11. Verificar diferencias con main antes del merge
```bash
git diff main..feature/modulo3-development
```

### 12. Cuando todo funcione, hacer merge a main
```bash
git checkout main
git merge feature/modulo3-development
git push origin main
```

## Notas Importantes

- ⚠️ **NO** hagas merge hasta que todos los tests pasen
- ✅ Los pasos marcados con `// turbo` se ejecutarán automáticamente
- 🔍 Revisa siempre el output de los tests antes de continuar
- 🌿 La rama `feature/modulo3-development` es temporal y se puede eliminar después del merge

## Comandos Útiles Durante el Desarrollo

### Ver logs del detector
```bash
Get-Content detector.log -Tail 50
```

### Limpiar caché de Laravel
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Verificar configuración de voz
```bash
php artisan tinker --execute="DB::table('voice_commands')->select('name', 'trigger', 'enabled')->get()"
```
