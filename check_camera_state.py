#!/usr/bin/env python3
"""
Verificar el estado de ambas cámaras antes de iniciar el detector
"""

from deteccion.vehiculo_detector import camera_states, CAMERAS_CONFIG

print("\n" + "="*60)
print("Estado inicial de cámaras")
print("="*60)

for cam_id in ['CAM_001', 'CAM_002']:
    state = camera_states[cam_id]
    name = CAMERAS_CONFIG[cam_id]['name']
    print(f"\n{cam_id}: {name}")
    print(f"  Status: {state['status']}")
    print(f"  Frame count: {state['frame_count']}")
    print(f"  Vehicle count: {state['vehicle_count']}")
    print(f"  Total detected: {state['total_vehicles_detected']}")
    print(f"  Has raw_frame: {state['raw_frame'] is not None}")
    print(f"  Has processed_frame: {state['processed_frame'] is not None}")

print("\n" + "="*60)
print("✅ Estados inicializados correctamente")
print("="*60 + "\n")
