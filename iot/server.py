#!/usr/bin/env python3
"""
Servidor UDP ultra-optimizado para ESP32-CAM
- Recepción UDP multithreaded
- WebSocket con compresión opcional
- Zero-copy donde sea posible
- Buffer optimizado
"""

import socket
import threading
import time
import struct
from collections import defaultdict, deque
from flask import Flask, Response, render_template_string
from flask_sock import Sock
import select

app = Flask(__name__)
sock = Sock(app)

# ===========================
# CONFIGURACIÓN OPTIMIZADA
# ===========================
UDP_IP = "0.0.0.0"
UDP_PORT = 5001  # UDP en puerto diferente
WEB_PORT = 5000  # Web en puerto 5000 (como antes)
MAX_PACKET_SIZE = 2048
SOCKET_BUFFER_SIZE = 2 * 1024 * 1024  # 2MB buffer (reducido para estabilidad)
NUM_RECEIVER_THREADS = 1  # Solo 1 thread para evitar problemas

# ===========================
# ALMACENAMIENTO
# ===========================
camera_frames = defaultdict(lambda: {
    'frame': None,
    'last_update': 0,
    'frame_count': 0,
    'total_bytes': 0,
    'fps': 0,
    'last_fps_time': time.time(),
    'fps_buffer': deque(maxlen=30)
})

# Buffers para frames fragmentados
frame_buffers = defaultdict(lambda: defaultdict(dict))
frame_lock = threading.Lock()

# WebSocket queues optimizadas
websocket_clients = defaultdict(list)

# ===========================
# HTML OPTIMIZADO
# ===========================
HTML_TEMPLATE = """
<!DOCTYPE html>
<html>
<head>
    <title>ESP32-CAM Ultra-Fast Stream</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: linear-gradient(135deg, #000000 0%, #1a1a1a 100%);
            color: #00ff00;
            padding: 10px;
        }
        h1 {
            text-align: center;
            color: #00ff00;
            margin: 15px 0;
            text-shadow: 0 0 20px #00ff00;
            font-size: 1.8em;
            animation: glow 2s ease-in-out infinite;
        }
        @keyframes glow {
            0%, 100% { text-shadow: 0 0 20px #00ff00; }
            50% { text-shadow: 0 0 30px #00ff00, 0 0 40px #00ff00; }
        }
        .stats-bar {
            background: rgba(0, 255, 0, 0.1);
            border: 2px solid #00ff00;
            padding: 10px;
            margin-bottom: 15px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            box-shadow: 0 0 30px rgba(0, 255, 0, 0.3);
            border-radius: 5px;
        }
        .stat {
            text-align: center;
            padding: 8px;
            background: rgba(0, 255, 0, 0.05);
            border-radius: 3px;
        }
        .stat-label {
            color: #00aa00;
            font-size: 0.75em;
            text-transform: uppercase;
        }
        .stat-value {
            color: #00ff00;
            font-size: 1.3em;
            font-weight: bold;
            margin-top: 3px;
        }
        .camera-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 15px;
        }
        .camera-card {
            background: rgba(0, 50, 0, 0.3);
            border: 2px solid #00ff00;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.2);
            transition: all 0.3s;
        }
        .camera-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 30px rgba(0, 255, 0, 0.4);
        }
        .camera-header {
            background: rgba(0, 255, 0, 0.2);
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #00ff00;
        }
        .camera-name {
            font-weight: bold;
            font-size: 1.1em;
        }
        .camera-fps {
            font-size: 1.2em;
            font-weight: bold;
            padding: 3px 10px;
            border-radius: 3px;
            background: rgba(0, 0, 0, 0.3);
        }
        .fps-excellent { color: #00ff00; }
        .fps-good { color: #ffff00; }
        .fps-poor { color: #ff8800; }
        .fps-bad { color: #ff0000; }
        .camera-stream {
            width: 100%;
            height: auto;
            display: block;
            background: #000;
            min-height: 240px;
            image-rendering: crisp-edges;
        }
        .camera-info {
            padding: 10px;
            background: rgba(0, 0, 0, 0.5);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            font-size: 0.85em;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 3px 5px;
            background: rgba(0, 255, 0, 0.05);
            border-radius: 3px;
        }
        .info-label { color: #00aa00; }
        .info-value { color: #00ff00; font-weight: bold; }
        .pulse { animation: pulse 1.5s ease-in-out infinite; }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        @media (max-width: 768px) {
            .camera-grid { grid-template-columns: 1fr; }
            .stats-bar { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>
    <h1>⚡ ESP32-CAM ULTRA-FAST STREAMING ⚡</h1>
    
    <div class="stats-bar">
        <div class="stat">
            <div class="stat-label">Cámaras</div>
            <div class="stat-value" id="total-cameras">0</div>
        </div>
        <div class="stat pulse">
            <div class="stat-label">Activas</div>
            <div class="stat-value" id="active-cameras">0</div>
        </div>
        <div class="stat">
            <div class="stat-label">Frames</div>
            <div class="stat-value" id="total-frames">0</div>
        </div>
        <div class="stat">
            <div class="stat-label">FPS Prom</div>
            <div class="stat-value" id="avg-fps">0</div>
        </div>
        <div class="stat">
            <div class="stat-label">Datos (MB)</div>
            <div class="stat-value" id="total-mb">0</div>
        </div>
    </div>

    <div class="camera-grid" id="camera-grid">
        {% for cam_id in camera_ids %}
        <div class="camera-card">
            <div class="camera-header">
                <span class="camera-name">📹 {{ cam_id }}</span>
                <span id="fps-{{ cam_id }}" class="camera-fps fps-excellent">0 FPS</span>
            </div>
            <canvas class="camera-stream" id="stream-{{ cam_id }}" width="480" height="320"></canvas>
            <div class="camera-info">
                <div class="info-item">
                    <span class="info-label">Frame #</span>
                    <span class="info-value" id="frame-{{ cam_id }}">0</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Latencia</span>
                    <span class="info-value" id="latency-{{ cam_id }}">0 ms</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Tamaño</span>
                    <span class="info-value" id="size-{{ cam_id }}">0 KB</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Protocolo</span>
                    <span class="info-value">UDP/WS</span>
                </div>
            </div>
        </div>
        {% endfor %}
    </div>

    <script>
        const cameras = {{ camera_ids | tojson }};
        const wsConnections = {};
        const canvasContexts = {};
        
        // Inicializar canvas y WebSocket para cada cámara
        cameras.forEach(camId => {
            const canvas = document.getElementById(`stream-${camId}`);
            const ctx = canvas.getContext('2d', { alpha: false });
            canvasContexts[camId] = ctx;
            
            // Conectar WebSocket
            const ws = new WebSocket(`ws://${window.location.hostname}:${window.location.port}/ws/${camId}`);
            ws.binaryType = 'arraybuffer';
            
            let frameCount = 0;
            let lastFrameTime = performance.now();
            
            ws.onopen = () => console.log(`✓ WebSocket ${camId} conectado`);
            
            ws.onmessage = (event) => {
                const blob = new Blob([event.data], {type: 'image/jpeg'});
                const url = URL.createObjectURL(blob);
                const img = new Image();
                
                img.onload = () => {
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                    URL.revokeObjectURL(url);
                    
                    // Calcular FPS local
                    frameCount++;
                    const now = performance.now();
                    const delta = now - lastFrameTime;
                    lastFrameTime = now;
                    
                    const sizeKB = (event.data.byteLength / 1024).toFixed(1);
                    const sizeElem = document.getElementById(`size-${camId}`);
                    if(sizeElem) sizeElem.textContent = `${sizeKB} KB`;
                };
                
                img.src = url;
            };
            
            ws.onerror = (error) => console.error(`✗ WebSocket ${camId} error:`, error);
            ws.onclose = () => {
                console.log(`⚠ WebSocket ${camId} cerrado, intentando reconectar...`);
                // Reconectar sin recargar página
                setTimeout(() => {
                    const newWs = new WebSocket(`ws://${window.location.hostname}:${window.location.port}/ws/${camId}`);
                    newWs.binaryType = 'arraybuffer';
                    newWs.onopen = () => console.log(`✓ WebSocket ${camId} reconectado`);
                    newWs.onmessage = ws.onmessage;
                    newWs.onerror = ws.onerror;
                    newWs.onclose = ws.onclose;
                    wsConnections[camId] = newWs;
                }, 2000);
            };
            
            wsConnections[camId] = ws;
        });
        
        // Actualizar estadísticas cada segundo
        function updateStats() {
            fetch('/stats')
                .then(r => r.json())
                .then(data => {
                    document.getElementById('total-cameras').textContent = data.total_cameras;
                    document.getElementById('active-cameras').textContent = data.active_cameras;
                    document.getElementById('total-frames').textContent = data.total_frames;
                    document.getElementById('total-mb').textContent = data.total_mb.toFixed(2);
                    document.getElementById('avg-fps').textContent = data.avg_fps.toFixed(1);
                    
                    // Actualizar info de cada cámara
                    Object.keys(data.cameras).forEach(camId => {
                        const cam = data.cameras[camId];
                        const fpsElem = document.getElementById(`fps-${camId}`);
                        const frameElem = document.getElementById(`frame-${camId}`);
                        const latencyElem = document.getElementById(`latency-${camId}`);
                        
                        if(fpsElem) {
                            const fps = cam.fps;
                            fpsElem.textContent = `${fps.toFixed(1)} FPS`;
                            fpsElem.className = 'camera-fps ' + 
                                (fps >= 25 ? 'fps-excellent' : 
                                 fps >= 20 ? 'fps-good' : 
                                 fps >= 10 ? 'fps-poor' : 'fps-bad');
                        }
                        if(frameElem) frameElem.textContent = cam.frames;
                        if(latencyElem) latencyElem.textContent = `${cam.last_seen.toFixed(0)} ms`;
                    });
                })
                .catch(e => console.error('Error stats:', e));
        }
        
        setInterval(updateStats, 1000);
        updateStats();
    </script>
</body>
</html>
"""

def udp_receiver_thread(thread_id):
    """Thread optimizado para recibir paquetes UDP"""
    # Crear socket individual por thread
    udp_socket = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
    udp_socket.setsockopt(socket.SOL_SOCKET, socket.SO_RCVBUF, SOCKET_BUFFER_SIZE)
    udp_socket.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
    udp_socket.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEPORT, 1)  # Crítico para múltiples threads
    udp_socket.bind((UDP_IP, UDP_PORT))
    
    print(f"✓ Thread {thread_id}: UDP Listener en {UDP_IP}:{UDP_PORT}")
    
    header_size = struct.calcsize('IHHI12s')
    
    while True:
        try:
            data, addr = udp_socket.recvfrom(MAX_PACKET_SIZE)
            
            if len(data) < header_size:
                continue
            
            # Parsear header (optimizado con struct)
            frame_id, packet_num, total_packets, frame_size, node_id_bytes = \
                struct.unpack('IHHI12s', data[:header_size])
            
            node_id = node_id_bytes.decode('utf-8', errors='ignore').rstrip('\x00')
            packet_data = data[header_size:]
            
            # Log de debug cada 100 paquetes
            if frame_id % 100 == 0 and packet_num == 0:
                print(f"[Thread {thread_id}] Recibido frame {frame_id} de {node_id} ({total_packets} paquetes)")
            
            # Almacenar paquete
            frame_buffers[node_id][frame_id][packet_num] = packet_data
            
            # Verificar frame completo
            if len(frame_buffers[node_id][frame_id]) == total_packets:
                # Reensamblar (zero-copy cuando sea posible)
                sorted_packets = sorted(frame_buffers[node_id][frame_id].items())
                frame_data = b''.join([pkt for _, pkt in sorted_packets])
                
                current_time = time.time()
                
                # Actualizar datos de cámara
                with frame_lock:
                    cam_data = camera_frames[node_id]
                    
                    # FPS suavizado con buffer
                    time_delta = current_time - cam_data['last_fps_time']
                    if time_delta > 0:
                        instant_fps = 1.0 / time_delta
                        cam_data['fps_buffer'].append(instant_fps)
                        cam_data['fps'] = sum(cam_data['fps_buffer']) / len(cam_data['fps_buffer'])
                    
                    cam_data['frame'] = frame_data
                    cam_data['last_update'] = current_time
                    cam_data['last_fps_time'] = current_time
                    cam_data['frame_count'] += 1
                    cam_data['total_bytes'] += len(frame_data)
                    
                    # Broadcast a WebSocket clients
                    if node_id in websocket_clients:
                        dead_clients = []
                        for ws in websocket_clients[node_id]:
                            try:
                                ws.send(frame_data)
                            except:
                                dead_clients.append(ws)
                        
                        # Limpiar clientes muertos
                        for ws in dead_clients:
                            websocket_clients[node_id].remove(ws)
                
                # Limpiar buffer
                del frame_buffers[node_id][frame_id]
                
                # Limitar tamaño de buffer (mantener solo últimos 3 frames)
                if len(frame_buffers[node_id]) > 3:
                    oldest = min(frame_buffers[node_id].keys())
                    del frame_buffers[node_id][oldest]
                
        except Exception as e:
            print(f"✗ Error Thread {thread_id}: {e}")
            import traceback
            traceback.print_exc()
            time.sleep(0.1)

@app.route('/')
def index():
    with frame_lock:
        camera_ids = list(camera_frames.keys()) if camera_frames else ['Esperando cámaras...']
    return render_template_string(HTML_TEMPLATE, camera_ids=camera_ids)

@sock.route('/ws/<node_id>')
def websocket_stream(ws, node_id):
    """WebSocket optimizado para streaming"""
    if node_id not in websocket_clients:
        websocket_clients[node_id] = []
    
    websocket_clients[node_id].append(ws)
    
    try:
        # Mantener conexión abierta
        while True:
            # Recibir keepalive del cliente
            data = ws.receive(timeout=30)
            if data is None:
                break
    except Exception as e:
        pass
    finally:
        if ws in websocket_clients[node_id]:
            websocket_clients[node_id].remove(ws)

@app.route('/stats')
def stats():
    """Estadísticas del sistema"""
    with frame_lock:
        current_time = time.time()
        active_cameras = sum(1 for cam in camera_frames.values() 
                           if current_time - cam['last_update'] < 3)
        total_frames = sum(cam['frame_count'] for cam in camera_frames.values())
        total_mb = sum(cam['total_bytes'] for cam in camera_frames.values()) / 1024 / 1024
        avg_fps = sum(cam['fps'] for cam in camera_frames.values()) / len(camera_frames) if camera_frames else 0
        
        return {
            'total_cameras': len(camera_frames),
            'active_cameras': active_cameras,
            'total_frames': total_frames,
            'total_mb': total_mb,
            'avg_fps': avg_fps,
            'cameras': {
                cam_id: {
                    'frames': cam['frame_count'],
                    'fps': cam['fps'],
                    'last_seen': (current_time - cam['last_update']) * 1000
                }
                for cam_id, cam in camera_frames.items()
            }
        }

@app.route('/health')
def health():
    """Health check"""
    return {'status': 'healthy', 'timestamp': time.time(), 'cameras': len(camera_frames)}

if __name__ == '__main__':
    print("\n" + "="*70)
    print("⚡ SERVIDOR UDP ULTRA-OPTIMIZADO ESP32-CAM")
    print("="*70)
    print(f"UDP Recepción: {UDP_IP}:{UDP_PORT} (buffer: {SOCKET_BUFFER_SIZE/1024/1024:.0f}MB)")
    print(f"Web Interface: http://144.22.56.85:{WEB_PORT}/")
    print(f"WebSocket: ws://144.22.56.85:{WEB_PORT}/ws/<cam_id>")
    print(f"Threads: {NUM_RECEIVER_THREADS} receptores UDP")
    print("="*70)
    print("\n📦 Dependencias: pip3 install flask flask-sock simple-websocket")
    print("🔥 Firewall UDP: sudo iptables -I INPUT 6 -p udp --dport 5001 -j ACCEPT")
    print("🔥 Firewall TCP: sudo iptables -I INPUT 6 -p tcp --dport 5000 -j ACCEPT")
    print(f"\n✅ Acceso Web: http://144.22.56.85:{WEB_PORT}/\n")
    
    # Iniciar threads UDP receptores
    for i in range(NUM_RECEIVER_THREADS):
        thread = threading.Thread(target=udp_receiver_thread, args=(i,), daemon=True)
        thread.start()
    
    # Iniciar servidor Flask
    app.run(host='0.0.0.0', port=WEB_PORT, threaded=True, debug=False)