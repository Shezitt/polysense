#!/usr/bin/env python3
"""
Detector de Vehículos LOCAL - Consume video de Oracle y procesa en tu PC
- Descarga video del servidor Oracle vía WebSocket
- Detecta vehículos con YOLOv8
- Expone API local para Laravel
- Streaming procesado vía WebSocket
"""

import cv2
import numpy as np
import asyncio
import websockets
import threading
import time
from collections import defaultdict, deque
from flask import Flask, jsonify, Response
from flask_sock import Sock
from ultralytics import YOLO
import io

app = Flask(__name__)
sock = Sock(app)

# ===========================
# CONFIGURACIÓN
# ===========================
ORACLE_SERVER = "ws://144.22.56.85:5000"  # Tu servidor Oracle
CAMERA_ID = "CAM_001"  # ID de tu cámara ESP32
LOCAL_PORT = 8080  # Puerto local para API y WebSocket

# Configuración YOLO
YOLO_MODEL = "yolov8n.pt"  # Modelo nano (rápido)
CONFIDENCE_THRESHOLD = 0.5
VEHICLE_CLASSES = [2, 3, 5, 7]  # car, motorcycle, bus, truck

COCO_CLASSES = {
    0: 'person', 1: 'bicycle', 2: 'car', 3: 'motorcycle',
    5: 'bus', 7: 'truck', 9: 'traffic light'
}

# ===========================
# ALMACENAMIENTO
# ===========================
camera_data = {
    'raw_frame': None,
    'processed_frame': None,
    'last_update': 0,
    'frame_count': 0,
    'fps': 0,
    'last_fps_time': time.time(),
    'fps_buffer': deque(maxlen=30),
    # Estadísticas de vehículos
    'vehicle_count': 0,
    'total_vehicles_detected': 0,
    'vehicle_history': deque(maxlen=100),
    'vehicle_colors': defaultdict(int),
    'vehicle_types': defaultdict(int),
    'detected_vehicles': []
}

data_lock = threading.Lock()
websocket_clients = []

# Cargar YOLO
print("🔄 Cargando modelo YOLOv8...")
model = YOLO(YOLO_MODEL)
print("✅ Modelo cargado!")

# ===========================
# FUNCIONES DE DETECCIÓN
# ===========================

def get_dominant_color(image, bbox):
    """Extrae el color dominante del vehículo"""
    x1, y1, x2, y2 = map(int, bbox)
    
    # Validar coordenadas
    h, w = image.shape[:2]
    x1, x2 = max(0, x1), min(w, x2)
    y1, y2 = max(0, y1), min(h, y2)
    
    if x2 <= x1 or y2 <= y1:
        return "desconocido"
    
    roi = image[y1:y2, x1:x2]
    
    if roi.size == 0:
        return "desconocido"
    
    # Convertir a HSV
    hsv = cv2.cvtColor(roi, cv2.COLOR_BGR2HSV)
    
    # Calcular histograma del canal H (tono)
    hist = cv2.calcHist([hsv], [0], None, [180], [0, 180])
    dominant_hue = np.argmax(hist)
    
    # Mapear a nombre de color
    if dominant_hue < 10 or dominant_hue > 170:
        return "rojo"
    elif 10 <= dominant_hue < 25:
        return "naranja"
    elif 25 <= dominant_hue < 40:
        return "amarillo"
    elif 40 <= dominant_hue < 80:
        return "verde"
    elif 80 <= dominant_hue < 130:
        return "azul"
    elif 130 <= dominant_hue < 160:
        return "morado"
    else:
        # Verificar saturación para blanco/gris/negro
        s_mean = np.mean(hsv[:, :, 1])
        v_mean = np.mean(hsv[:, :, 2])
        
        if s_mean < 50 and v_mean > 200:
            return "blanco"
        elif s_mean < 50 and v_mean < 50:
            return "negro"
        else:
            return "gris"

def detect_vehicles(frame):
    """Detecta vehículos en el frame"""
    if frame is None:
        return None, []
    
    # Detección YOLO
    results = model(frame, conf=CONFIDENCE_THRESHOLD, verbose=False)
    
    vehicles = []
    
    for result in results:
        boxes = result.boxes
        for box in boxes:
            cls = int(box.cls[0])
            conf = float(box.conf[0])
            
            # Filtrar solo vehículos
            if cls in VEHICLE_CLASSES:
                bbox = box.xyxy[0].cpu().numpy()
                x1, y1, x2, y2 = map(int, bbox)
                
                # Tipo de vehículo
                vehicle_type = COCO_CLASSES.get(cls, "unknown")
                
                # Color dominante
                color = get_dominant_color(frame, bbox)
                
                vehicles.append({
                    'bbox': (x1, y1, x2, y2),
                    'confidence': conf,
                    'type': vehicle_type,
                    'color': color
                })
                
                # Dibujar bounding box
                color_bgr = (0, 255, 0)
                cv2.rectangle(frame, (x1, y1), (x2, y2), color_bgr, 2)
                
                # Label
                label = f"{vehicle_type} ({color}) {conf:.2f}"
                
                # Fondo para el texto
                (text_w, text_h), _ = cv2.getTextSize(label, cv2.FONT_HERSHEY_SIMPLEX, 0.5, 2)
                cv2.rectangle(frame, (x1, y1 - text_h - 10), (x1 + text_w, y1), color_bgr, -1)
                cv2.putText(frame, label, (x1, y1 - 5),
                           cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0, 0, 0), 2)
    
    # Contador en la esquina
    count_text = f"Vehiculos: {len(vehicles)}"
    cv2.rectangle(frame, (5, 5), (250, 45), (0, 0, 0), -1)
    cv2.putText(frame, count_text, (10, 35),
               cv2.FONT_HERSHEY_SIMPLEX, 1, (0, 255, 0), 2)
    
    return frame, vehicles

# ===========================
# WEBSOCKET CLIENT (Oracle)
# ===========================

async def consume_oracle_stream():
    """Conecta al servidor Oracle y consume el stream"""
    uri = f"{ORACLE_SERVER}/ws/{CAMERA_ID}"
    
    print(f"🔄 Conectando a Oracle: {uri}")
    
    while True:
        try:
            async with websockets.connect(uri, ping_interval=None) as websocket:
                print(f"✅ Conectado a Oracle!")
                
                frame_counter = 0
                
                while True:
                    # Recibir frame del servidor
                    frame_data = await websocket.recv()
                    
                    frame_counter += 1
                    if frame_counter % 30 == 0:  # Log cada 30 frames
                        print(f"📦 Recibidos {frame_counter} frames desde Oracle")
                    
                    # Decodificar JPEG
                    nparr = np.frombuffer(frame_data, np.uint8)
                    frame = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
                    
                    if frame is None:
                        print("⚠️ Frame corrupto recibido")
                        continue
                    
                    # Detectar vehículos
                    processed_frame, vehicles = detect_vehicles(frame.copy())
                    
                    current_time = time.time()
                    
                    # Actualizar datos
                    with data_lock:
                        # FPS
                        time_delta = current_time - camera_data['last_fps_time']
                        if time_delta > 0:
                            instant_fps = 1.0 / time_delta
                            camera_data['fps_buffer'].append(instant_fps)
                            camera_data['fps'] = sum(camera_data['fps_buffer']) / len(camera_data['fps_buffer'])
                        
                        camera_data['raw_frame'] = frame
                        camera_data['processed_frame'] = processed_frame
                        camera_data['last_update'] = current_time
                        camera_data['last_fps_time'] = current_time
                        camera_data['frame_count'] += 1
                        
                        # Estadísticas de vehículos
                        camera_data['vehicle_count'] = len(vehicles)
                        camera_data['total_vehicles_detected'] += len(vehicles)
                        camera_data['vehicle_history'].append(len(vehicles))
                        camera_data['detected_vehicles'] = vehicles
                        
                        # Contar tipos y colores
                        for vehicle in vehicles:
                            camera_data['vehicle_types'][vehicle['type']] += 1
                            camera_data['vehicle_colors'][vehicle['color']] += 1
                        
                        # Broadcast a clientes WebSocket
                        if processed_frame is not None and len(websocket_clients) > 0:
                            _, buffer = cv2.imencode('.jpg', processed_frame, [cv2.IMWRITE_JPEG_QUALITY, 85])
                            frame_bytes = buffer.tobytes()
                            
                            print(f"📤 Broadcasting a {len(websocket_clients)} cliente(s)")
                            
                            dead_clients = []
                            for ws in websocket_clients:
                                try:
                                    ws.send(frame_bytes)
                                except Exception as e:
                                    print(f"❌ Error enviando a cliente: {e}")
                                    dead_clients.append(ws)
                            
                            for ws in dead_clients:
                                websocket_clients.remove(ws)
                    
        except Exception as e:
            print(f"❌ Error: {e}")
            print("🔄 Reconectando en 3 segundos...")
            await asyncio.sleep(3)

def start_websocket_client():
    """Inicia el cliente WebSocket en un thread"""
    loop = asyncio.new_event_loop()
    asyncio.set_event_loop(loop)
    loop.run_until_complete(consume_oracle_stream())

# ===========================
# API FLASK
# ===========================

@app.route('/')
def index():
    """Página de inicio"""
    return """
    <html>
    <head><title>Detector Local de Vehículos</title></head>
    <body style="background: #1a1a1a; color: #00ff00; font-family: monospace; padding: 20px;">
        <h1>🚗 Detector Local de Vehículos</h1>
        <h2>Endpoints Disponibles:</h2>
        <ul>
            <li><a href="/stats" style="color: #00ff00;">/stats</a> - Estadísticas</li>
            <li><a href="/api/vehicles" style="color: #00ff00;">/api/vehicles</a> - API para Laravel</li>
            <li><code>ws://localhost:8080/ws/stream</code> - WebSocket stream</li>
        </ul>
        <h3>Estado: <span style="color: #00ff00;">✅ Online</span></h3>
    </body>
    </html>
    """

@app.route('/stats')
def stats():
    """Estadísticas generales"""
    with data_lock:
        return jsonify({
            'status': 'online',
            'camera_id': CAMERA_ID,
            'fps': camera_data['fps'],
            'frame_count': camera_data['frame_count'],
            'current_vehicles': camera_data['vehicle_count'],
            'total_detected': camera_data['total_vehicles_detected'],
            'vehicle_types': dict(camera_data['vehicle_types']),
            'vehicle_colors': dict(camera_data['vehicle_colors'])
        })

@app.route('/api/vehicles')
def api_vehicles():
    """API para Laravel"""
    with data_lock:
        avg_vehicles = (sum(camera_data['vehicle_history']) / len(camera_data['vehicle_history'])) if camera_data['vehicle_history'] else 0
        
        return jsonify({
            'camera_id': CAMERA_ID,
            'timestamp': time.time(),
            'current_vehicles': camera_data['vehicle_count'],
            'total_detected': camera_data['total_vehicles_detected'],
            'fps': camera_data['fps'],
            'avg_vehicles': avg_vehicles,
            'vehicle_types': dict(camera_data['vehicle_types']),
            'vehicle_colors': dict(camera_data['vehicle_colors']),
            'history': list(camera_data['vehicle_history']),
            'detected_vehicles': camera_data['detected_vehicles']
        })

@sock.route('/ws/stream')
def websocket_stream(ws):
    """WebSocket para streaming"""
    print(f"✅ Nuevo cliente WebSocket conectado. Total: {len(websocket_clients) + 1}")
    websocket_clients.append(ws)
    
    try:
        while True:
            # Mantener conexión viva
            data = ws.receive(timeout=30)
            if data is None:
                break
    except Exception as e:
        print(f"⚠️ Cliente WebSocket desconectado: {e}")
    finally:
        if ws in websocket_clients:
            websocket_clients.remove(ws)
        print(f"👋 Cliente desconectado. Quedan: {len(websocket_clients)}")

# ===========================
# MAIN
# ===========================

if __name__ == '__main__':
    print("\n" + "="*70)
    print("🚗 DETECTOR LOCAL DE VEHÍCULOS")
    print("="*70)
    print(f"📡 Conectando a Oracle: {ORACLE_SERVER}")
    print(f"🌐 API Local: http://localhost:{LOCAL_PORT}")
    print(f"📊 Stats: http://localhost:{LOCAL_PORT}/stats")
    print(f"🔌 WebSocket: ws://localhost:{LOCAL_PORT}/ws/stream")
    print(f"🎯 API Laravel: http://localhost:{LOCAL_PORT}/api/vehicles")
    print("="*70)
    print("\n📦 Instalación: pip install ultralytics opencv-python websockets flask flask-sock\n")
    
    # Iniciar cliente WebSocket en thread
    ws_thread = threading.Thread(target=start_websocket_client, daemon=True)
    ws_thread.start()
    
    print("⏳ Esperando conexión con Oracle...\n")
    time.sleep(2)
    
    # Iniciar servidor Flask
    app.run(host='0.0.0.0', port=LOCAL_PORT, threaded=True, debug=False)