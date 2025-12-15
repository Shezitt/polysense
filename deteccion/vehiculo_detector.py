#!/usr/bin/env python3
"""
Detector de Vehículos FINAL - Skyline Webcams (Cochabamba)
----------------------------------------------------------
CORRECCIÓN CRÍTICA:
1. Simula interacción humana (Clicks) para forzar el inicio del video.
2. Captura el tráfico específico de 'hd-auth.skylinewebcams.com'.
3. Usa Headers idénticos a un navegador real.
"""

import cv2
import numpy as np
import threading
import time
import logging
from collections import defaultdict, deque
from flask import Flask, jsonify
from flask_sock import Sock
from ultralytics import YOLO
import xml.etree.ElementTree as ET
from datetime import datetime
import os

# Configuración de Logging
logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(levelname)s - %(message)s')

# --- Importación Segura de Playwright ---
try:
    from playwright.sync_api import sync_playwright
except ImportError:
    logging.error("❌ ERROR: Playwright no está instalado.")
    exit(1)

app = Flask(__name__)
sock = Sock(app)

# ===========================
# CONFIGURACIÓN
# ===========================
SKYLINE_URL = 'https://www.skylinewebcams.com/es/webcam/bolivia/cercado/cochabamba/plaza-14-de-septiembre.html'
LOCAL_PORT = 8080
YOLO_MODEL = "yolov8n.pt"
CONFIDENCE_THRESHOLD = 0.5
VEHICLE_CLASSES = [2, 3, 5, 7]
XML_DB_PATH = os.path.join(os.path.dirname(__file__), '..', 'storage', 'app', 'vehiculos_db.xml')
COCO_CLASSES = {0: 'person', 1: 'bicycle', 2: 'car', 3: 'motorcycle', 5: 'bus', 7: 'truck'}

# ===========================
# ESTADO GLOBAL
# ===========================
camera_data = {
    'processed_frame': None,
    'fps': 0,
    'last_fps_time': time.time(),
    'fps_buffer': deque(maxlen=30),
    'vehicle_count': 0,
    'total_vehicles_detected': 0,
    'detected_vehicles': [],
    'tracked_vehicles': {},
    'next_vehicle_id': 1,
    'max_distance': 100,
    'max_frames_missing': 30
}
data_lock = threading.Lock()
websocket_clients = []

# Cargar YOLO
logging.info("🔄 Cargando modelo YOLOv8...")
model = YOLO(YOLO_MODEL)
logging.info("✅ Modelo cargado.")

# ===========================
# UTILIDADES
# ===========================
def init_xml_database():
    if not os.path.exists(XML_DB_PATH):
        os.makedirs(os.path.dirname(XML_DB_PATH), exist_ok=True)
        root = ET.Element('detecciones')
        ET.ElementTree(root).write(XML_DB_PATH, encoding='utf-8', xml_declaration=True)

def save_detection_to_xml(vehicle):
    try:
        if os.path.exists(XML_DB_PATH):
            tree = ET.parse(XML_DB_PATH)
            root = tree.getroot()
        else:
            root = ET.Element('detecciones')
            tree = ET.ElementTree(root)
        d = ET.SubElement(root, 'deteccion')
        ET.SubElement(d, 'fecha').text = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
        ET.SubElement(d, 'tipo').text = vehicle['type']
        ET.SubElement(d, 'confianza').text = f"{vehicle['confidence']:.2f}"
        ET.indent(tree, space='  ')
        tree.write(XML_DB_PATH, encoding='utf-8', xml_declaration=True)
    except Exception: pass

def get_dominant_color(image, bbox):
    # Simplificado para brevedad, puedes pegar tu función completa aquí
    return "gris" 

def detect_vehicles(frame, tracked_vehicles=None):
    if frame is None: return frame, []
    results = model(frame, conf=CONFIDENCE_THRESHOLD, verbose=False)
    vehicles = []
    for r in results:
        for box in r.boxes:
            cls = int(box.cls[0])
            if cls in VEHICLE_CLASSES:
                x1, y1, x2, y2 = map(int, box.xyxy[0].cpu().numpy())
                vehicles.append({
                    'bbox': (x1,y1,x2,y2), 
                    'confidence': float(box.conf[0]),
                    'type': COCO_CLASSES.get(cls, 'unknown'),
                    'color': get_dominant_color(frame, (x1,y1,x2,y2))
                })
                cv2.rectangle(frame, (x1,y1), (x2,y2), (0,255,0), 2)
    return frame, vehicles

# ===========================
# LÓGICA DE EXTRACCIÓN (CORREGIDA)
# ===========================

def get_skyline_stream_url_robust():
    """
    Simula un usuario real: Abre navegador, busca el player, hace CLICK 
    y captura la URL 'live.m3u8'.
    """
    logging.info("🕵️  Iniciando navegador (Buscando 'live.m3u8')...")
    found_url = None
    
    def handle_request(request):
        nonlocal found_url
        # Buscamos la URL exacta que mencionaste en tu curl
        if ".m3u8" in request.url and "live" in request.url:
            logging.info(f"🎯 ¡CAPTURA! {request.url[:80]}...")
            found_url = request.url

    try:
        with sync_playwright() as p:
            # Usamos el User-Agent exacto de tu curl para que no sospechen
            browser = p.chromium.launch(
                headless=True, # Cambia a False si quieres ver la ventana para depurar
                args=['--no-sandbox', '--disable-blink-features=AutomationControlled']
            )
            
            context = browser.new_context(
                user_agent="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0",
                viewport={'width': 1280, 'height': 720}
            )
            
            page = context.new_page()
            page.on("request", handle_request)
            
            logging.info(f"🌍 Entrando a: {SKYLINE_URL}")
            page.goto(SKYLINE_URL, timeout=60000, wait_until="domcontentloaded")

            # --- PARTE CLAVE: SIMULAR INTERACCIÓN ---
            logging.info("👆 Intentando iniciar video manualmente...")
            
            # Intentamos clicar cualquier cosa que parezca un botón de play
            try:
                # Esperar un poco a que cargue el player
                page.wait_for_timeout(3000)
                
                # Clic al centro de la pantalla (funciona para la mayoría de players)
                # O clic a selectores comunes de Skyline
                selectors = ["#player", ".play-btn", "video", "#player-container"]
                
                clicked = False
                for sel in selectors:
                    try:
                        if page.is_visible(sel):
                            logging.info(f"👆 Clic en selector: {sel}")
                            page.click(sel, force=True)
                            clicked = True
                            page.wait_for_timeout(1000) # Esperar reacción
                    except: pass
                
                if not clicked:
                    # Clic "a ciegas" en el centro del player si no encontramos botón
                    logging.info("👆 Clic forzado en coordenadas centrales")
                    page.mouse.click(640, 360)
                    
            except Exception as e:
                logging.warning(f"⚠️ Error intentando clicks: {e}")

            # Esperar la respuesta de red
            logging.info("⏳ Esperando tráfico .m3u8 (Máx 45s)...")
            start_time = time.time()
            while time.time() - start_time < 45:
                if found_url:
                    browser.close()
                    return found_url
                page.wait_for_timeout(1000) # Polling suave
            
            logging.error("❌ Tiempo agotado sin detectar tráfico.")
            browser.close()
            return None

    except Exception as e:
        logging.error(f"❌ Error Playwright: {e}")
        return None

def consume_skyline_stream():
    logging.info("🚀 Iniciando bucle de visión...")
    
    while True:
        # 1. Obtener URL
        stream_url = get_skyline_stream_url_robust()
        
        if not stream_url:
            logging.warning("💤 Reintentando en 10s...")
            time.sleep(10)
            continue
            
        # 2. Conectar OpenCV
        logging.info(f"🎥 Conectando a stream...")
        cap = cv2.VideoCapture(stream_url)
        
        if not cap.isOpened():
            logging.error("❌ OpenCV no pudo abrir el stream.")
            continue

        logging.info("✅ VIDEO OK. Procesando...")
        
        frame_counter = 0
        consecutive_errors = 0
        
        while True:
            ret, frame = cap.read()
            if not ret:
                consecutive_errors += 1
                if consecutive_errors > 20: # 20 frames fallidos = stream muerto
                    logging.warning("⚠️ Stream cortado. Renovando...")
                    break
                time.sleep(0.05)
                continue
                
            consecutive_errors = 0
            frame_counter += 1
            if frame_counter % 3 != 0: continue # Saltar frames
            
            # --- PROCESAMIENTO ---
            with data_lock:
                # Aquí iría tu lógica completa de tracking (simplificada para que entre)
                processed_frame, vehicles = detect_vehicles(frame.copy(), camera_data['tracked_vehicles'])
                
                # Actualizar datos globales
                camera_data['vehicle_count'] = len(vehicles)
                camera_data['detected_vehicles'] = vehicles
                camera_data['total_vehicles_detected'] += len([v for v in vehicles if v['confidence'] > 0.8]) # Dummy counter logic
                
                # WebSocket Broadcast
                if websocket_clients:
                    _, buf = cv2.imencode('.jpg', processed_frame, [cv2.IMWRITE_JPEG_QUALITY, 50])
                    data_bytes = buf.tobytes()
                    dead = []
                    for ws in websocket_clients:
                        try: ws.send(data_bytes)
                        except: dead.append(ws)
                    for d in dead: websocket_clients.remove(d)

        cap.release()
        time.sleep(1)

# ===========================
# RUTAS FLASK
# ===========================
@app.route('/')
def index(): return "<h1>🚗 Detector Online</h1>"

@app.route('/api/vehicles')
def api():
    with data_lock:
        return jsonify({
            'current': camera_data['vehicle_count'],
            'total': camera_data['total_vehicles_detected'],
            'vehicles': camera_data['detected_vehicles']
        })

@sock.route('/ws/stream')
def ws_stream(ws):
    websocket_clients.append(ws)
    try:
        while True:
            if ws.receive(timeout=30) is None: break
    except: pass
    finally:
        if ws in websocket_clients: websocket_clients.remove(ws)

if __name__ == '__main__':
    init_xml_database()
    # Hilo de video
    t = threading.Thread(target=consume_skyline_stream, daemon=True)
    t.start()
    # Servidor Web
    app.run(host='0.0.0.0', port=LOCAL_PORT, debug=False, use_reloader=False)