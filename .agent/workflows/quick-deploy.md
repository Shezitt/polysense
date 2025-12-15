---
description: Despliegue rápido con verificación automática
---

# Workflow: Despliegue Rápido

Despliega cambios rápidamente con verificación automática.

## Pasos de Despliegue

### 1. Limpiar cachés
// turbo-all
```bash
php artisan cache:clear
```

### 2. Limpiar configuración
```bash
php artisan config:clear
```

### 3. Limpiar vistas compiladas
```bash
php artisan view:clear
```

### 4. Optimizar autoloader
```bash
composer dump-autoload -o
```

### 5. Ejecutar migraciones pendientes
```bash
php artisan migrate --force
```

### 6. Ejecutar tests rápidos
```bash
php artisan test --parallel
```

### 7. Verificar que el servidor está corriendo
```bash
curl -s http://localhost:8000 | Select-String -Pattern "PolySense" -Quiet
```

## Post-Despliegue

Si todos los pasos anteriores pasan:
- ✅ El sistema está listo para usar
- ✅ Puedes acceder a http://localhost:8000
- ✅ Todos los módulos deberían funcionar correctamente
