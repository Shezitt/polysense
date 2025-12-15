#!/usr/bin/env python3
"""
Detector de Vehículos MULTI-CÁMARA
- Soporte para Oracle (CAM_001) + Skyline Cochabamba (CAM_002)
- API con parámetro ?camera_id=CAM_001 o ?camera_id=CAM_002
- WebSocket con parámetro /ws/stream?camera_id=CAM_002
- Tracking único + Color + FPS por cámara
- XML persistente con identificación de cámara
"""

import cv2
import numpy as np
import threading
import time
import logging
import os
import re
from collections import defaultdict, deque
from datetime import datetime
from flask import Flask, jsonify, request
from flask_sock import Sock
from ultralytics import YOLO
import xml.etree.ElementTree as ET

# Configuración Logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - [%(name)s] %(levelname)s - %(message)s')
logger = logging.getLogger('DETECTOR')

# --- Importación Segura de Playwright ---
try:
    from playwright.sync_api import sync_playwright
except ImportError:
    logger.error("❌ ERROR: Playwright no está instalado.")
    print("Ejecuta: pip install playwright && playwright install chromium")
    exit(1)

app = Flask(__name__)
sock = Sock(app)

# ===========================
# CONFIGURACIÓN DE CÁMARAS
# ===========================
CAMERAS_CONFIG = {
    'CAM_001': {
        'name': 'Oracle Server',
        'type': 'websocket',
        'url': 'ws://144.22.56.85:5000/ws/CAM_001'  # Endpoint correcto (con mayúsculas)
    },
    'CAM_002': {
        'name': 'Skyline Cochabamba',
        'type': 'skyline',
        'url': 'https://www.skylinewebcams.com/es/webcam/bolivia/cercado/cochabamba/plaza-14-de-septiembre.html'
    }
}

LOCAL_PORT = 8080

# Configuración YOLO
YOLO_MODEL = "yolov8n.pt"
CONFIDENCE_THRESHOLD = 0.5
VEHICLE_CLASSES = [2, 3, 5, 7]

# XML Database
XML_DB_PATH = os.path.join(os.path.dirname(__file__), '..', 'storage', 'app', 'vehiculos_db.xml')

COCO_CLASSES = {
    0: 'person', 1: 'bicycle', 2: 'car', 3: 'motorcycle',
    5: 'bus', 7: 'truck', 9: 'traffic light'
}

# ===========================
# ESTADO GLOBAL (por cámara)
# ===========================
camera_states = {
    'CAM_001': {
        'raw_frame': None,
        'processed_frame': None,
        'last_update': 0,
        'frame_count': 0,
        'fps': 0,
        'last_fps_time': time.time(),
        'fps_buffer': deque(maxlen=30),
        'vehicle_count': 0,
        'total_vehicles_detected': 0,
        'vehicle_history': deque(maxlen=100),
        'vehicle_colors': defaultdict(int),
        'vehicle_types': defaultdict(int),
        'detected_vehicles': [],
        'tracked_vehicles': {},
        'next_vehicle_id': 1,
        'max_distance': 100,
        'max_frames_missing': 30,
        'status': 'offline'
    },
    'CAM_002': {
        'raw_frame': None,
        'processed_frame': None,
        'last_update': 0,
        'frame_count': 0,
        'fps': 0,
        'last_fps_time': time.time(),
        'fps_buffer': deque(maxlen=30),
        'vehicle_count': 0,
        'total_vehicles_detected': 0,
        'vehicle_history': deque(maxlen=100),
        'vehicle_colors': defaultdict(int),
        'vehicle_types': defaultdict(int),
        'detected_vehicles': [],
        'tracked_vehicles': {},
        'next_vehicle_id': 1,
        'max_distance': 100,
        'max_frames_missing': 30,
        'status': 'offline'
    }
}

data_lock = threading.Lock()
websocket_clients = defaultdict(list)  # {camera_id: [ws1, ws2, ...]}

# Cargar YOLO
logger.info("🔄 Cargando modelo YOLOv8...")
model = YOLO(YOLO_MODEL)
logger.info("✅ Modelo YOLO cargado!")

# ===========================
# FUNCIONES DE XML
# ===========================

def init_xml_database():
    """Inicializa XML"""
    if not os.path.exists(XML_DB_PATH):
        os.makedirs(os.path.dirname(XML_DB_PATH), exist_ok=True)
        root = ET.Element('detecciones')
        tree = ET.ElementTree(root)
        ET.indent(tree, space='  ')
        tree.write(XML_DB_PATH, encoding='utf-8', xml_declaration=True)
        logger.info(f"✅ XML creado: {XML_DB_PATH}")

def save_detection_to_xml(vehicle, camera_id='CAM_002'):
    """Guarda detección con identificación de cámara"""
    try:
        tree = ET.parse(XML_DB_PATH) if os.path.exists(XML_DB_PATH) else ET.ElementTree(ET.Element('detecciones'))
        root = tree.getroot()
        
        deteccion = ET.SubElement(root, 'deteccion')
        ET.SubElement(deteccion, 'fecha').text = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        
        tipo_map = {'car': 'Auto', 'motorcycle': 'Moto', 'bus': 'Bus', 'truck': 'Camión'}
        ET.SubElement(deteccion, 'tipo').text = tipo_map.get(vehicle['type'], 'Auto')
        ET.SubElement(deteccion, 'confianza').text = f"{vehicle['confidence'] * 100:.2f}"
        ET.SubElement(deteccion, 'color').text = vehicle['color']
        ET.SubElement(deteccion, 'camara').text = camera_id
        ET.SubElement(deteccion, 'nombre_camara').text = CAMERAS_CONFIG[camera_id]['name']
        
        ET.indent(tree, space='  ')
        tree.write(XML_DB_PATH, encoding='utf-8', xml_declaration=True)
        return True
    except Exception as e:
        logger.error(f"❌ Error XML: {e}")
        return False

# ===========================
# FUNCIONES DE DETECCIÓN
# ===========================

def calculate_bbox_center(bbox):
    x1, y1, x2, y2 = bbox
    return ((x1 + x2) / 2, (y1 + y2) / 2)

def calculate_distance(center1, center2):
    return np.sqrt((center1[0] - center2[0])**2 + (center1[1] - center2[1])**2)

def match_vehicle_to_tracked(bbox, tracked_vehicles, max_distance):
    center = calculate_bbox_center(bbox)
    best_match_id = None
    best_distance = float('inf')
    
    for vehicle_id, vehicle_data in tracked_vehicles.items():
        tracked_center = calculate_bbox_center(vehicle_data['bbox'])
        distance = calculate_distance(center, tracked_center)
        
        if distance < max_distance and distance < best_distance:
            best_distance = distance
            best_match_id = vehicle_id
    
    return best_match_id

def get_dominant_color(image, bbox):
    """Detecta color dominante con HSV"""
    x1, y1, x2, y2 = map(int, bbox)
    h, w = image.shape[:2]
    x1, x2 = max(0, x1), min(w, x2)
    y1, y2 = max(0, y1), min(h, y2)
    
    if x2 <= x1 or y2 <= y1: return "desconocido"
    roi = image[y1:y2, x1:x2]
    if roi.size == 0: return "desconocido"
    
    hsv = cv2.cvtColor(roi, cv2.COLOR_BGR2HSV)
    hist = cv2.calcHist([hsv], [0], None, [180], [0, 180])
    hue = np.argmax(hist)
    
    if hue < 10 or hue > 170: return "rojo"
    elif 10 <= hue < 25: return "naranja"
    elif 25 <= hue < 35: return "amarillo"
    elif 35 <= hue < 85: return "verde"
    elif 85 <= hue < 130: return "azul"
    elif 130 <= hue < 160: return "morado"
    else:
        s = np.mean(hsv[:,:,1])
        v = np.mean(hsv[:,:,2])
        if s < 50 and v > 200: return "blanco"
        elif v < 50: return "negro"
        else: return "gris"

def detect_vehicles(frame, tracked_vehicles=None):
    """YOLO + Drawing"""
    if frame is None: return None, []
    
    results = model(frame, conf=CONFIDENCE_THRESHOLD, verbose=False)
    vehicles = []
    
    for result in results:
        for box in result.boxes:
            cls = int(box.cls[0])
            conf = float(box.conf[0])
            
            if cls in VEHICLE_CLASSES:
                bbox = box.xyxy[0].cpu().numpy()
                x1, y1, x2, y2 = map(int, bbox)
                
                vehicle_type = COCO_CLASSES.get(cls, "unknown")
                color = get_dominant_color(frame, bbox)
                
                vehicles.append({
                    'bbox': (x1, y1, x2, y2),
                    'confidence': conf,
                    'type': vehicle_type,
                    'color': color
                })
                
                # Dibujar
                color_bgr = (0, 255, 0)
                cv2.rectangle(frame, (x1, y1), (x2, y2), color_bgr, 2)
                label = f"{vehicle_type} ({color}) {conf:.2f}"
                (tw, th), _ = cv2.getTextSize(label, cv2.FONT_HERSHEY_SIMPLEX, 0.5, 1)
                cv2.rectangle(frame, (x1, y1 - th - 5), (x1 + tw, y1), color_bgr, -1)
                cv2.putText(frame, label, (x1, y1 - 5), cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0, 0, 0), 1)
    
    cv2.rectangle(frame, (5, 5), (250, 40), (0, 0, 0), -1)
    cv2.putText(frame, f"Vehiculos: {len(vehicles)}", (10, 30), cv2.FONT_HERSHEY_SIMPLEX, 0.8, (0, 255, 0), 2)
    
    return frame, vehicles

# ===========================
# EXTRACCIÓN SKYLINE (Playwright)
# ===========================

def get_skyline_stream_url_robust():
    """Extrae m3u8 con Playwright"""
    logger.info("🕵️  Skyline: Iniciando Playwright...")
    found_url = None
    
    def handle_request(request):
        nonlocal found_url
        if ".m3u8" in request.url and "live" in request.url:
            logger.info(f"🎯 Skyline: URL capturada")
            found_url = request.url

    try:
        with sync_playwright() as p:
            browser = p.chromium.launch(headless=True)
            context = browser.new_context(
                user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36"
            )
            page = context.new_page()
            page.on("request", handle_request)
            
            logger.info("🌍 Skyline: Navegando...")
            try:
                page.goto(CAMERAS_CONFIG['CAM_002']['url'], timeout=60000, wait_until="domcontentloaded")
            except: pass

            try:
                page.wait_for_timeout(2000)
                selectors = ["#player", ".play-btn", "video", ".jw-display-icon-container"]
                for sel in selectors:
                    try:
                        if page.is_visible(sel):
                            page.click(sel, force=True)
                            page.wait_for_timeout(500)
                    except: pass
                page.mouse.click(640, 360)
            except: pass

            start_t = time.time()
            while time.time() - start_t < 45:
                if found_url:
                    browser.close()
                    return found_url
                page.wait_for_timeout(1000)
            
            browser.close()
            return None

    except Exception as e:
        logger.error(f"❌ Skyline Playwright: {e}")
        return None

# ===========================
# WORKERS PARA CADA CÁMARA
# ===========================

def process_frame_generic(frame, camera_id):
    """Procesa un frame: YOLO + Tracking + Color"""
    if frame is None: return None, []
    
    with data_lock:
        tracked = camera_states[camera_id]['tracked_vehicles'].copy()
    
    processed_frame, vehicles = detect_vehicles(frame.copy(), tracked)
    current_time = time.time()
    
    with data_lock:
        state = camera_states[camera_id]
        
        dt = current_time - state['last_fps_time']
        if dt > 0:
            state['fps_buffer'].append(1.0/dt)
            state['fps'] = sum(state['fps_buffer'])/len(state['fps_buffer'])
        state['last_fps_time'] = current_time
        
        state['raw_frame'] = frame
        state['processed_frame'] = processed_frame
        state['last_update'] = current_time
        state['frame_count'] += 1
        
        # TRACKING
        current_fc = state['frame_count']
        
        for v in vehicles:
            bbox = v['bbox']
            vid = match_vehicle_to_tracked(bbox, state['tracked_vehicles'], state['max_distance'])
            
            if vid:
                state['tracked_vehicles'][vid]['bbox'] = bbox
                state['tracked_vehicles'][vid]['last_seen'] = current_fc
            else:
                nid = state['next_vehicle_id']
                state['tracked_vehicles'][nid] = {
                    'bbox': bbox,
                    'last_seen': current_fc,
                    'type': v['type'],
                    'color': v['color'],
                    'confidence': v['confidence']
                }
                state['next_vehicle_id'] += 1
                
                state['total_vehicles_detected'] += 1
                state['vehicle_types'][v['type']] += 1
                state['vehicle_colors'][v['color']] += 1
                
                if v['confidence'] > 0.7:
                    save_detection_to_xml(v, camera_id)
        
        # Cleanup
        to_remove = []
        for vid, vdata in state['tracked_vehicles'].items():
            if current_fc - vdata['last_seen'] > state['max_frames_missing']:
                to_remove.append(vid)
        for vid in to_remove:
            del state['tracked_vehicles'][vid]
        
        state['vehicle_count'] = len(vehicles)
        state['vehicle_history'].append(len(vehicles))
        state['detected_vehicles'] = vehicles

        # WebSocket Broadcast ÚNICAMENTE a clientes de ESTA cámara
        if camera_id in websocket_clients:
            clients_count = len(websocket_clients[camera_id])
            if clients_count > 0 and processed_frame is not None:
                try:
                    _, buf = cv2.imencode('.jpg', processed_frame, [cv2.IMWRITE_JPEG_QUALITY, 60])
                    frame_bytes = buf.tobytes()
                    dead = []
                    
                    # Log: enviando frame a esta cámara
                    if state['frame_count'] % 100 == 0:  # Log cada 100 frames
                        logger.info(f"📤 {camera_id}: Enviando frame ({len(frame_bytes)} bytes) a {clients_count} cliente(s)")
                    
                    for ws in websocket_clients[camera_id]:
                        try:
                            ws.send(frame_bytes)
                        except Exception as send_err:
                            dead.append(ws)
                    
                    # Limpiar conexiones muertas
                    for d in dead:
                        if d in websocket_clients[camera_id]:
                            websocket_clients[camera_id].remove(d)
                except Exception as e:
                    logger.warning(f"⚠️ {camera_id}: Error en broadcast WebSocket - {str(e)[:80]}")
    
    return processed_frame, vehicles

def worker_oracle():
    """Worker para CAM_001 (Oracle) - Consumidor de WebSocket del servidor Oracle"""
    logger.info("🚀 CAM_001 (Oracle): Iniciando worker...")
    
    import asyncio
    import websockets
    
    async def main():
        """Loop principal de reconexión"""
        while True:
            uri = CAMERAS_CONFIG['CAM_001']['url']
            logger.info(f"🔄 CAM_001: Conectando a {uri}...")
            
            try:
                async with websockets.connect(uri, ping_interval=None) as websocket:
                    logger.info("✅ CAM_001: ¡Conectado al servidor Oracle!")
                    
                    with data_lock:
                        camera_states['CAM_001']['status'] = 'online'
                    logger.info("✅ CAM_001: Status actualizado a ONLINE")
                    
                    frame_counter = 0
                    consecutive_errors = 0
                    
                    while True:
                        try:
                            # Recibir frame del servidor Oracle
                            frame_data = await asyncio.wait_for(websocket.recv(), timeout=30)
                            
                            consecutive_errors = 0
                            frame_counter += 1
                            
                            if frame_counter % 30 == 0:
                                with data_lock:
                                    status_check = camera_states['CAM_001']['status']
                                logger.info(f"📦 CAM_001: Recibidos {frame_counter} frames | Status={status_check}")
                            
                            # Decodificar JPEG
                            try:
                                nparr = np.frombuffer(frame_data, np.uint8)
                                frame = cv2.imdecode(nparr, cv2.IMREAD_COLOR)
                                
                                if frame is not None:
                                    # Procesar el frame (YOLO + tracking + broadcast)
                                    process_frame_generic(frame, 'CAM_001')
                                else:
                                    logger.warning("⚠️ CAM_001: Frame corrupto (decode error)")
                                    consecutive_errors += 1
                            except Exception as e:
                                logger.warning(f"⚠️ CAM_001: Error procesando frame - {str(e)[:80]}")
                                consecutive_errors += 1
                                
                                if consecutive_errors > 10:
                                    raise Exception("Demasiados errores decodificando frames")
                            
                        except asyncio.TimeoutError:
                            logger.warning("⚠️ CAM_001: Timeout esperando frames (30s)")
                            with data_lock:
                                camera_states['CAM_001']['status'] = 'offline'
                            break
                        except websockets.exceptions.ConnectionClosed:
                            logger.warning("⚠️ CAM_001: Conexión cerrada por servidor")
                            with data_lock:
                                camera_states['CAM_001']['status'] = 'offline'
                            break
                        except Exception as e:
                            logger.warning(f"⚠️ CAM_001: {str(e)[:80]}")
                            with data_lock:
                                camera_states['CAM_001']['status'] = 'offline'
                            break
                
            except Exception as e:
                logger.error(f"❌ CAM_001: Error en websocket.connect - {str(e)[:120]}")
                with data_lock:
                    camera_states['CAM_001']['status'] = 'offline'
            
            logger.info("⏳ CAM_001: Esperando 5s antes de reconectar...")
            await asyncio.sleep(5)
    
    # Ejecutar el loop async
    try:
        logger.info("🔧 CAM_001: Iniciando asyncio.run()...")
        asyncio.run(main())
    except KeyboardInterrupt:
        logger.info("⏹️ CAM_001: Detenido por usuario")
        with data_lock:
            camera_states['CAM_001']['status'] = 'offline'
    except Exception as e:
        logger.error(f"❌ CAM_001: Error fatal en worker - {str(e)[:150]}")
        with data_lock:
            camera_states['CAM_001']['status'] = 'offline'

def worker_skyline():
    """Worker para CAM_002 (Skyline)"""
    logger.info("🚀 CAM_002 (Skyline): Iniciando...")
    
    while True:
        stream_url = get_skyline_stream_url_robust()
        
        if not stream_url:
            logger.warning("💤 CAM_002: No URL. Reintentando en 10s...")
            time.sleep(10)
            continue
        
        logger.info(f"🎥 CAM_002: Abriendo OpenCV...")
        cap = cv2.VideoCapture(stream_url)
        
        if not cap.isOpened():
            logger.error("❌ CAM_002: OpenCV no abrió")
            time.sleep(10)
            continue
        
        logger.info("✅ CAM_002: Streaming activo")
        with data_lock:
            camera_states['CAM_002']['status'] = 'online'
        
        frame_counter = 0
        consecutive_errors = 0
        
        while True:
            ret, frame = cap.read()
            
            if not ret:
                consecutive_errors += 1
                if consecutive_errors > 30:
                    logger.warning("⚠️ CAM_002: Stream perdido")
                    with data_lock:
                        camera_states['CAM_002']['status'] = 'offline'
                    break
                time.sleep(0.01)
                continue
            
            consecutive_errors = 0
            frame_counter += 1
            
            if frame_counter % 3 != 0: continue  # Procesar 1 de cada 3
            
            process_frame_generic(frame, 'CAM_002')
            
            if frame_counter % 60 == 0:
                logger.info(f"📦 CAM_002: {frame_counter} frames")
        
        cap.release()
        time.sleep(1)

# ===========================
# API FLASK
# ===========================

@app.route('/')
def index():
    return f"""
    <html>
    <head><title>Detector Multi-Cámara</title></head>
    <body style="background: #1a1a1a; color: #00ff00; font-family: monospace; padding: 20px;">
        <h1>🚗 Detector Multi-Cámara</h1>
        <h2>Cámaras Disponibles:</h2>
        <ul>
            <li><strong>CAM_001:</strong> {CAMERAS_CONFIG['CAM_001']['name']}</li>
            <li><strong>CAM_002:</strong> {CAMERAS_CONFIG['CAM_002']['name']}</li>
        </ul>
        <h2>Endpoints:</h2>
        <ul>
            <li><code>/api/vehicles?camera_id=CAM_002</code></li>
            <li><code>/stats?camera_id=CAM_002</code></li>
            <li><code>ws://localhost:8080/ws/stream?camera_id=CAM_002</code></li>
        </ul>
        <h3>Estado: ✅ Online</h3>
    </body>
    </html>
    """

@app.route('/api/vehicles')
def api_vehicles():
    """API con soporte para parámetro camera_id"""
    camera_id = request.args.get('camera_id', 'CAM_002')
    
    if camera_id not in camera_states:
        return jsonify({'error': 'camera_id no válido'}), 400
    
    with data_lock:
        state = camera_states[camera_id]
        status_value = state['status']
        frame_count = state['frame_count']
        
        # Debug logging
        if camera_id == 'CAM_001':
            logger.info(f"🔍 API CAM_001: status={status_value}, frames={frame_count}, vehicle_count={state['vehicle_count']}")
        
        avg_v = (sum(state['vehicle_history'])/len(state['vehicle_history'])) if state['vehicle_history'] else 0
        
        return jsonify({
            'camera_id': camera_id,
            'camera_name': CAMERAS_CONFIG[camera_id]['name'],
            'status': status_value,
            'timestamp': time.time(),
            'current_vehicles': state['vehicle_count'],
            'total_detected': state['total_vehicles_detected'],
            'unique_tracked': len(state['tracked_vehicles']),
            'fps': round(state['fps'], 2),
            'avg_vehicles': round(avg_v, 2),
            'vehicle_types': dict(state['vehicle_types']),
            'vehicle_colors': dict(state['vehicle_colors']),
            'history': list(state['vehicle_history']),
            'detected_vehicles': state['detected_vehicles']
        })

@app.route('/stats')
def stats():
    """Estadísticas de cámara específica"""
    camera_id = request.args.get('camera_id', 'CAM_002')
    
    if camera_id not in camera_states:
        return jsonify({'error': 'camera_id no válido'}), 400
    
    with data_lock:
        state = camera_states[camera_id]
        return jsonify({
            'camera_id': camera_id,
            'status': state['status'],
            'fps': round(state['fps'], 2),
            'frame_count': state['frame_count'],
            'current_vehicles': state['vehicle_count'],
            'total_detected': state['total_vehicles_detected'],
            'tracked_count': len(state['tracked_vehicles'])
        })

@app.route('/api/reset', methods=['POST'])
def reset_counter():
    """Reset contadores"""
    camera_id = request.args.get('camera_id', 'CAM_002')
    
    if camera_id not in camera_states:
        return jsonify({'error': 'camera_id no válido'}), 400
    
    with data_lock:
        state = camera_states[camera_id]
        state['total_vehicles_detected'] = 0
        state['vehicle_types'].clear()
        state['vehicle_colors'].clear()
        state['tracked_vehicles'].clear()
        state['next_vehicle_id'] = 1
    
    return jsonify({'status': 'success', 'message': f'Contador {camera_id} reseteado'})

@sock.route('/ws/stream')
def websocket_stream(ws):
    """WebSocket con parámetro camera_id - envía frames de la cámara seleccionada"""
    logger.info(f"📡 WebSocket REQUEST RECIBIDO!")
    logger.info(f"   Args: {request.args}")
    logger.info(f"   URL: {request.url}")
    
    camera_id = request.args.get('camera_id', 'CAM_002')
    
    logger.info(f"📡 camera_id extraído: {camera_id}")
    
    if camera_id not in camera_states:
        logger.error(f"❌ WebSocket: camera_id inválido: {camera_id}")
        ws.close()
        return
    
    logger.info(f"🔌 {camera_id}: Cliente WebSocket conectado! (enviando frames...)")
    websocket_clients[camera_id].append(ws)
    
    try:
        frame_count = 0
        last_log_time = time.time()
        frame_send_interval = 0.033  # ~30 FPS
        
        while True:
            try:
                # NO esperamos input del cliente, enviamos frames constantemente
                # Solo hacer un receive() sin timeout para detectar desconexiones
                try:
                    msg = ws.receive(timeout=1)
                    # Si recibimos algo (heartbeat), ignorar
                    if msg is None:
                        break
                except:
                    # Timeout es normal, continue
                    pass
                
                # Obtener el último frame procesado de esta cámara
                with data_lock:
                    state = camera_states[camera_id]
                    processed_frame = state['processed_frame']
                    cam_status = state['status']
                
                # Enviar frame si existe
                if processed_frame is not None:
                    try:
                        _, buf = cv2.imencode('.jpg', processed_frame, [cv2.IMWRITE_JPEG_QUALITY, 60])
                        ws.send(buf.tobytes())
                        frame_count += 1
                        
                        # Log cada 30 frames enviados
                        now = time.time()
                        if now - last_log_time >= 5:
                            logger.info(f"📤 {camera_id} WebSocket: {frame_count} frames enviados en 5s")
                            frame_count = 0
                            last_log_time = now
                    except Exception as send_err:
                        logger.warning(f"⚠️ {camera_id}: Error enviando frame WebSocket - {str(send_err)[:50]}")
                        break
                else:
                    # Si la cámara está offline, enviar placeholder
                    if cam_status == 'offline':
                        placeholder = np.zeros((480, 640, 3), dtype=np.uint8)
                        cv2.putText(placeholder, CAMERAS_CONFIG[camera_id]['name'], 
                                   (50, 200), cv2.FONT_HERSHEY_SIMPLEX, 1.5, (0, 0, 255), 3)
                        cv2.putText(placeholder, "OFFLINE", 
                                   (150, 280), cv2.FONT_HERSHEY_SIMPLEX, 2, (0, 0, 255), 3)
                        try:
                            _, buf = cv2.imencode('.jpg', placeholder, [cv2.IMWRITE_JPEG_QUALITY, 90])
                            ws.send(buf.tobytes())
                        except:
                            break
                
                time.sleep(frame_send_interval)
                    
            except Exception as e:
                logger.warning(f"⚠️ {camera_id}: Error en WebSocket loop - {str(e)[:80]}")
                break
                
    except Exception as e:
        logger.error(f"❌ {camera_id}: Error en WebSocket - {str(e)[:100]}")
    finally:
        if ws in websocket_clients[camera_id]:
            websocket_clients[camera_id].remove(ws)
        logger.info(f"👋 {camera_id}: Cliente desconectado (WebSocket)")

# ===========================
# MAIN
# ===========================

if __name__ == '__main__':
    logger.info("\n" + "="*70)
    logger.info("🚗 DETECTOR MULTI-CÁMARA")
    logger.info("="*70)
    for cam_id, config in CAMERAS_CONFIG.items():
        logger.info(f"{cam_id}: {config['name']}")
    logger.info("="*70)
    
    init_xml_database()
    
    # Workers para cada cámara
    t1 = threading.Thread(target=worker_oracle, daemon=True)
    t2 = threading.Thread(target=worker_skyline, daemon=True)
    t1.start()
    t2.start()
    
    # Flask
    app.run(host='0.0.0.0', port=LOCAL_PORT, threaded=True, debug=False, use_reloader=False)
