#!/usr/bin/env python3
"""
Inicia el detector multi-cámara
Esto es un wrapper para manejar mejor los imports pesados
"""
import sys
import time

print("=" * 70)
print("🚗 DETECTOR MULTI-CÁMARA - INICIANDO")
print("=" * 70)
print("\n⏳ Cargando módulos (esto puede tardar 30-60 segundos)...\n")

try:
    # Mostrar progreso mientras se cargan los imports
    print("  [1/5] Importando detecton.vehiculo_detector...")
    from deteccion import vehiculo_detector
    
    print("\n✅ Detector cargado correctamente")
    print("=" * 70)
    
except KeyboardInterrupt:
    print("\n❌ Detenido por el usuario")
    sys.exit(1)
except Exception as e:
    print(f"\n❌ Error: {e}")
    import traceback
    traceback.print_exc()
    sys.exit(1)
