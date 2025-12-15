#!/usr/bin/env python3
"""
Detector de Vehículos FINAL - Skyline Webcams (Cochabamba)
----------------------------------------------------------
Fusión Completa:
1. Adquisición: Playwright (Modo Interactivo/Click) para obtener token de Skyline.
2. Procesamiento: YOLOv8 + Tracking ID + Detección de Color + FPS.
3. Backend: Flask API (endpoints originales) + WebSocket Streaming Local.
4. Persistencia: Log en XML.

Requisitos: pip install playwright ultralytics flask flask-sock opencv-python numpy
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
from flask import Flask, jsonify, Response
from flask_sock import Sock
from ultralytics import YOLO
import xml.etree.ElementTree as ET

# Configuración de Logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')

# --- Importación Segura de Playwright ---
try:
    from playwright.sync_api import sync_playwright
except ImportError:
    logging.error("❌ ERROR: Playwright no está instalado.")
    print("Ejecuta: pip install playwright && playwright install chromium")
    exit(1)

app = Flask(__name__)
sock = Sock(app)

# ===========================
# CONFIGURACIÓN
# ===========================
# Reemplazamos Oracle por Skyline
SKYLINE_URL = 'https://www.skylinewebcams.com/es/webcam/bolivia/cercado/cochabamba/plaza-14-de-septiembre.html'
LOCAL_PORT = 8080

# Configuración YOLO
YOLO_MODEL = "yolov8n.pt"
CONFIDENCE_THRESHOLD = 0.5
VEHICLE_CLASSES = [2, 3, 5, 7]  # car, motorcycle, bus, truck

# Ruta del archivo XML (Laravel storage)
XML_DB_PATH = os.path.join(os.path.dirname(__file__), '..', 'storage', 'app', 'vehiculos_db.xml')

COCO_CLASSES = {
    0: 'person', 1: 'bicycle', 2: 'car', 3: 'motorcycle',
    5: 'bus', 7: 'truck', 9: 'traffic light'
}

# ===========================
# ALMACENAMIENTO (ESTADO GLOBAL)
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
    'detected_vehicles': [],
    # Sistema de tracking
    'tracked_vehicles': {},  # {id: {'bbox': ..., 'last_seen': ...}}
    'next_vehicle_id': 1,
    'max_distance': 100,  # Distancia para tracking
    'max_frames_missing': 30
}

data_lock = threading.Lock()
websocket_clients = []

# Cargar YOLO
logging.info("🔄 Cargando modelo YOLOv8...")
model = YOLO(YOLO_MODEL)
logging.info("✅ Modelo cargado!")

# ===========================
# FUNCIONES DE BASE DE DATOS (XML)
# ===========================

def init_xml_database():
    """Inicializa el archivo XML si no existe"""
    if not os.path.exists(XML_DB_PATH):
        os.makedirs(os.path.dirname(XML_DB_PATH), exist_ok=True)
        root = ET.Element('detecciones')
        tree = ET.ElementTree(root)
        ET.indent(tree, space='  ')
        tree.write(XML_DB_PATH, encoding='utf-8', xml_declaration=True)
        logging.info(f"✅ Archivo XML creado: {XML_DB_PATH}")

def save_detection_to_xml(vehicle):
    """Guarda una detección de vehículo en el XML"""
    try:
        if os.path.exists(XML_DB_PATH):
            tree = ET.parse(XML_DB_PATH)
            root = tree.getroot()
        else:
            root = ET.Element('detecciones')
            tree = ET.ElementTree(root)
        
        deteccion = ET.SubElement(root, 'deteccion')
        
        ET.SubElement(deteccion, 'fecha').text = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        
        tipo_map = {'car': 'Auto', 'motorcycle': 'Moto', 'bus': 'Bus', 'truck': 'Camión'}
        ET.SubElement(deteccion, 'tipo').text = tipo_map.get(vehicle['type'], 'Auto')
        ET.SubElement(deteccion, 'confianza').text = f"{vehicle['confidence'] * 100:.2f}"
        ET.SubElement(deteccion, 'color').text = vehicle['color']
        ET.SubElement(deteccion, 'origen').text = "SkylineWebcams"
        
        ET.indent(tree, space='  ')
        tree.write(XML_DB_PATH, encoding='utf-8', xml_declaration=True)
        return True
    except Exception as e:
        logging.error(f"❌ Error guardando en XML: {e}")
        return False

# ===========================
# FUNCIONES DE DETECCIÓN, TRACKING Y COLOR
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
    """Extrae el color dominante del vehículo (Heurística HSV)"""
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
    
    # Mapeo ajustado
    if hue < 10 or hue > 170: return "rojo"
    elif 10 <= hue < 25: return "naranja"
    elif 25 <= hue < 35: return "amarillo"
    elif 35 <= hue < 85: return "verde"
    elif 85 <= hue < 130: return "azul"
    elif 130 <= hue < 160: return "morado"
    else:
        # Chequeo rápido de saturación/brillo para grises
        s = np.mean(hsv[:,:,1])
        v = np.mean(hsv[:,:,2])
        if s < 50 and v > 200: return "blanco"
        elif v < 50: return "negro"
        else: return "gris"

def detect_vehicles(frame, tracked_vehicles=None):
    """Detecta vehículos y dibuja bounding boxes + tracking info"""
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
                
                # --- Lógica de Visualización en el Frame Procesado ---
                # Intentamos predecir el ID para dibujarlo
                v_id_label = ""
                if tracked_vehicles:
                    # Búsqueda rápida solo para visualización (el tracking real se hace después)
                    center = calculate_bbox_center((x1, y1, x2, y2))
                    best_d = 100
                    for vid, vdata in tracked_vehicles.items():
                        td = calculate_distance(center, calculate_bbox_center(vdata['bbox']))
                        if td < best_d:
                            v_id_label = f"ID:{vid}"
                            best_d = td
                            break

                # Dibujar
                color_bgr = (0, 255, 0)
                cv2.rectangle(frame, (x1, y1), (x2, y2), color_bgr, 2)
                
                label = f"{v_id_label} {vehicle_type} ({color})"
                (tw, th), _ = cv2.getTextSize(label, cv2.FONT_HERSHEY_SIMPLEX, 0.5, 1)
                cv2.rectangle(frame, (x1, y1 - th - 5), (x1 + tw, y1), color_bgr, -1)
                cv2.putText(frame, label, (x1, y1 - 5), cv2.FONT_HERSHEY_SIMPLEX, 0.5, (0, 0, 0), 1)
    
    # Overlay de contador en esquina
    cv2.rectangle(frame, (5, 5), (250, 40), (0, 0, 0), -1)
    cv2.putText(frame, f"Vehiculos: {len(vehicles)}", (10, 30), cv2.FONT_HERSHEY_SIMPLEX, 0.8, (0, 255, 0), 2)
    
    return frame, vehicles

# ===========================
# LÓGICA DE EXTRACCIÓN ROBUSTA (PLAYWRIGHT)
# ===========================

def get_skyline_stream_url_robust():
    """
    Usa Playwright con interacción (clicks) para obtener la URL 'live.m3u8'.
    Simula ser Edge/Chrome en Windows.
    """
    logging.info("🕵️  Iniciando navegador oculto para capturar token...")
    found_url = None
    
    def handle_request(request):
        nonlocal found_url
        if ".m3u8" in request.url and "live" in request.url:
            logging.info(f"🎯 URL Detectada: {request.url[:60]}...")
            found_url = request.url

    try:
        with sync_playwright() as p:
            # Lanzar navegador con argumentos anti-detección
            browser = p.chromium.launch(
                headless=True,
                args=['--no-sandbox', '--disable-blink-features=AutomationControlled']
            )
            
            # Contexto con User-Agent REAL (el que funcionó en tu curl)
            context = browser.new_context(
                user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0",
                viewport={'width': 1280, 'height': 720}
            )
            
            page = context.new_page()
            page.on("request", handle_request)
            
            logging.info(f"🌍 Navegando a Skyline...")
            try:
                page.goto(SKYLINE_URL, timeout=60000, wait_until="domcontentloaded")
            except Exception:
                pass # Continuar aunque el timeout de carga salte, si el request ya salió

            # --- INTERACCIÓN HUMANA SIMULADA ---
            logging.info("👆 Simulando clicks para iniciar video...")
            try:
                page.wait_for_timeout(2000)
                # Intentar clicks en elementos comunes
                selectors = ["#player", ".play-btn", "video", ".jw-display-icon-container"]
                clicked = False
                for sel in selectors:
                    try:
                        if page.is_visible(sel):
                            page.click(sel, force=True)
                            clicked = True
                            page.wait_for_timeout(500)
                    except: pass
                
                # Clic genérico al centro si no encontramos botones
                if not clicked:
                    page.mouse.click(640, 360)
            except Exception as e:
                logging.warning(f"⚠️ Error en clicks: {e}")

            # Esperar tráfico
            start_t = time.time()
            while time.time() - start_t < 45:
                if found_url:
                    browser.close()
                    return found_url
                page.wait_for_timeout(1000)
            
            browser.close()
            return None

    except Exception as e:
        logging.error(f"❌ Error Playwright: {e}")
        return None

# ===========================
# BUCLE PRINCIPAL DE PROCESAMIENTO
# ===========================

def consume_skyline_stream():
    """Bucle principal: Obtiene URL -> Captura Video -> Procesa YOLO -> Tracking -> WebSocket"""
    logging.info("🚀 Iniciando Sistema de Visión...")
    
    while True:
        # 1. Obtener URL válida
        stream_url = get_skyline_stream_url_robust()
        
        if not stream_url:
            logging.warning("💤 No se pudo obtener URL. Reintentando en 10s...")
            time.sleep(10)
            continue
            
        # 2. Iniciar Captura OpenCV
        logging.info("🎥 Conectando OpenCV al stream...")
        cap = cv2.VideoCapture(stream_url)
        
        if not cap.isOpened():
            logging.error("❌ OpenCV no pudo abrir la URL.")
            continue

        logging.info("✅ STREAMING OPERATIVO. Procesando frames...")
        frame_counter = 0
        consecutive_errors = 0
        skip_frames = 3 # Procesar 1 de cada 3 frames para rendimiento
        
        while True:
            ret, frame = cap.read()
            
            if not ret:
                consecutive_errors += 1
                if consecutive_errors > 30: # ~1 seg de error
                    logging.warning("⚠️ Stream interrumpido. Renovando conexión...")
                    break
                time.sleep(0.01)
                continue
            
            consecutive_errors = 0
            frame_counter += 1
            if frame_counter % skip_frames != 0: continue

            # --- PROCESAMIENTO CORE (YOLO + TRACKING) ---
            try:
                # 1. Copia segura de datos de tracking actuales
                with data_lock:
                    tracked = camera_data['tracked_vehicles'].copy()
                
                # 2. Detección YOLO
                processed_frame, vehicles = detect_vehicles(frame.copy(), tracked)
                
                current_time = time.time()
                
                with data_lock:
                    # 3. Cálculo FPS
                    dt = current_time - camera_data['last_fps_time']
                    if dt > 0:
                        camera_data['fps_buffer'].append(1.0/dt)
                        camera_data['fps'] = sum(camera_data['fps_buffer'])/len(camera_data['fps_buffer'])
                    camera_data['last_fps_time'] = current_time
                    
                    camera_data['raw_frame'] = frame
                    camera_data['processed_frame'] = processed_frame
                    camera_data['last_update'] = current_time
                    camera_data['frame_count'] += 1
                    
                    # 4. TRACKING LOGIC
                    current_fc = camera_data['frame_count']
                    
                    for v in vehicles:
                        bbox = v['bbox']
                        vid = match_vehicle_to_tracked(bbox, camera_data['tracked_vehicles'], camera_data['max_distance'])
                        
                        if vid:
                            # Actualizar existente
                            camera_data['tracked_vehicles'][vid]['bbox'] = bbox
                            camera_data['tracked_vehicles'][vid]['last_seen'] = current_fc
                        else:
                            # Crear nuevo
                            nid = camera_data['next_vehicle_id']
                            camera_data['tracked_vehicles'][nid] = {
                                'bbox': bbox,
                                'last_seen': current_fc,
                                'counted': True,
                                'type': v['type'],
                                'color': v['color'],
                                'confidence': v['confidence']
                            }
                            camera_data['next_vehicle_id'] += 1
                            
                            # Actualizar contadores globales
                            camera_data['total_vehicles_detected'] += 1
                            camera_data['vehicle_types'][v['type']] += 1
                            camera_data['vehicle_colors'][v['color']] += 1
                            
                            # Guardar XML si confianza alta
                            if v['confidence'] > 0.7:
                                save_detection_to_xml(v)

                    # 5. Limpieza de tracking
                    to_remove = []
                    for vid, vdata in camera_data['tracked_vehicles'].items():
                        if current_fc - vdata['last_seen'] > camera_data['max_frames_missing']:
                            to_remove.append(vid)
                    for vid in to_remove:
                        del camera_data['tracked_vehicles'][vid]
                    
                    # Actualizar estado actual
                    camera_data['vehicle_count'] = len(vehicles)
                    camera_data['vehicle_history'].append(len(vehicles))
                    camera_data['detected_vehicles'] = vehicles

                    # 6. WebSocket Broadcast (Envío de imagen procesada)
                    if websocket_clients:
                        _, buf = cv2.imencode('.jpg', processed_frame, [cv2.IMWRITE_JPEG_QUALITY, 60])
                        frame_bytes = buf.tobytes()
                        dead = []
                        for ws in websocket_clients:
                            try: ws.send(frame_bytes)
                            except: dead.append(ws)
                        for d in dead:
                            if d in websocket_clients: websocket_clients.remove(d)

            except Exception as e:
                logging.error(f"Error en bucle de procesamiento: {e}")

        cap.release()
        time.sleep(1)

# ===========================
# API FLASK (Tus endpoints originales)
# ===========================

@app.route('/')
def index():
    return """
    <html>
    <head><title>Detector Skyline Cochabamba</title></head>
    <body style="background: #1a1a1a; color: #00ff00; font-family: monospace; padding: 20px;">
        <h1>🚗 Detector Skyline Cochabamba (Versión Híbrida)</h1>
        <h2>Endpoints Disponibles:</h2>
        <ul>
            <li><a href="/stats" style="color: #00ff00;">/stats</a> - Estadísticas JSON</li>
            <li><a href="/api/vehicles" style="color: #00ff00;">/api/vehicles</a> - API Laravel</li>
            <li><code>ws://localhost:8080/ws/stream</code> - Stream MJPEG (WebSocket)</li>
        </ul>
        <h3>Estado: <span style="color: #00ff00;">✅ Online</span></h3>
    </body>
    </html>
    """

@app.route('/stats')
def stats():
    with data_lock:
        return jsonify({
            'status': 'online',
            'fps': round(camera_data['fps'], 2),
            'frame_count': camera_data['frame_count'],
            'current_vehicles': camera_data['vehicle_count'],
            'total_detected': camera_data['total_vehicles_detected'],
            'vehicle_types': dict(camera_data['vehicle_types']),
            'vehicle_colors': dict(camera_data['vehicle_colors']),
            'tracked_count': len(camera_data['tracked_vehicles']),
            'next_id': camera_data['next_vehicle_id']
        })

@app.route('/api/vehicles')
def api_vehicles():
    """Endpoint principal consumido por Laravel"""
    with data_lock:
        avg_v = (sum(camera_data['vehicle_history'])/len(camera_data['vehicle_history'])) if camera_data['vehicle_history'] else 0
        return jsonify({
            'timestamp': time.time(),
            'current_vehicles': camera_data['vehicle_count'],
            'total_detected': camera_data['total_vehicles_detected'],
            'unique_vehicles_tracked': len(camera_data['tracked_vehicles']),
            'fps': round(camera_data['fps'], 2),
            'avg_vehicles': avg_v,
            'vehicle_types': dict(camera_data['vehicle_types']),
            'vehicle_colors': dict(camera_data['vehicle_colors']),
            'history': list(camera_data['vehicle_history']),
            'detected_vehicles': camera_data['detected_vehicles']
        })

@app.route('/api/reset', methods=['POST'])
def reset_counter():
    with data_lock:
        camera_data['total_vehicles_detected'] = 0
        camera_data['vehicle_types'].clear()
        camera_data['vehicle_colors'].clear()
        camera_data['tracked_vehicles'].clear()
        camera_data['next_vehicle_id'] = 1
        return jsonify({'status': 'success', 'message': 'Contador reseteado'})

@sock.route('/ws/stream')
def websocket_stream(ws):
    logging.info(f"🔌 Cliente conectado. Total: {len(websocket_clients)+1}")
    websocket_clients.append(ws)
    try:
        while True:
            if ws.receive(timeout=30) is None: break
    except: pass
    finally:
        if ws in websocket_clients: websocket_clients.remove(ws)
        logging.info("👋 Cliente desconectado")

# ===========================
# MAIN
# ===========================

if __name__ == '__main__':
    print("\n" + "="*70)
    print("🚗 DETECTOR SKYLINE COCHABAMBA - SISTEMA COMPLETO")
    print("="*70)
    print(f"📡 Fuente: {SKYLINE_URL}")
    print(f"🌐 API: http://localhost:{LOCAL_PORT}/api/vehicles")
    print(f"🔌 Stream WS: ws://localhost:{LOCAL_PORT}/ws/stream")
    print(f"💾 XML DB: {XML_DB_PATH}")
    print("="*70)
    
    # 1. Base de datos
    init_xml_database()
    
    # 2. Hilo de Procesamiento de Video (Consume Skyline -> Procesa YOLO)
    # Usamos threading normal porque OpenCV es bloqueante
    t = threading.Thread(target=consume_skyline_stream, daemon=True)
    t.start()
    
    # 3. Servidor Web Flask (Bloqueante en Main Thread)
    app.run(host='0.0.0.0', port=LOCAL_PORT, threaded=True, debug=False, use_reloader=False)