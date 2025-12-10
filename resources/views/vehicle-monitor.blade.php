<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Monitor de Vehículos - ESP32-CAM</title>
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: #fff;
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        header {
            text-align: center;
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .status-indicator {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: bold;
            margin-top: 10px;
        }

        .status-online {
            background: #4caf50;
            animation: pulse 2s infinite;
        }

        .status-offline {
            background: #f44336;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .dashboard {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .video-section {
            background: rgba(0, 0, 0, 0.5);
            border-radius: 15px;
            padding: 20px;
            border: 2px solid rgba(255, 255, 255, 0.1);
        }

        .video-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .camera-selector {
            padding: 10px 15px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #fff;
            font-size: 1em;
            cursor: pointer;
        }

        .camera-selector option {
            background: #1e3c72;
            color: #fff;
        }

        #videoCanvas {
            width: 100%;
            height: auto;
            border-radius: 10px;
            background: #000;
            min-height: 400px;
        }

        .stats-panel {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            border: 2px solid rgba(255, 255, 255, 0.2);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .stat-title {
            font-size: 0.9em;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            color: #4caf50;
            text-shadow: 0 0 10px rgba(76, 175, 80, 0.5);
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 20px;
        }

        .detail-item {
            background: rgba(0, 0, 0, 0.3);
            padding: 12px;
            border-radius: 8px;
            text-align: center;
        }

        .detail-label {
            font-size: 0.85em;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 1.3em;
            font-weight: bold;
            color: #64b5f6;
        }

        .vehicle-list {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            border: 2px solid rgba(255, 255, 255, 0.2);
        }

        .list-title {
            font-size: 1.2em;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.2);
        }

        .vehicle-item {
            background: rgba(0, 0, 0, 0.3);
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .vehicle-type {
            font-weight: bold;
            text-transform: capitalize;
        }

        .vehicle-count {
            background: #4caf50;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: bold;
        }

        .color-badge {
            display: inline-block;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 8px;
            border: 2px solid rgba(255, 255, 255, 0.5);
        }

        .loading {
            text-align: center;
            padding: 40px;
            font-size: 1.2em;
            color: rgba(255, 255, 255, 0.7);
        }

        .error-message {
            background: rgba(244, 67, 54, 0.2);
            border: 2px solid #f44336;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
            text-align: center;
        }

        @media (max-width: 1024px) {
            .dashboard {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🚗 Monitor de Vehículos en Tiempo Real</h1>
            <div id="connectionStatus" class="status-indicator status-offline">
                Conectando...
            </div>
        </header>

        <div class="dashboard">
            <!-- Sección de Video -->
            <div class="video-section">
                <div class="video-header">
                    <h2>📹 Transmisión en Vivo</h2>
                    <select id="cameraSelector" class="camera-selector">
                        <option value="">Seleccionar cámara...</option>
                    </select>
                </div>
                <canvas id="videoCanvas" width="800" height="600"></canvas>
            </div>

            <!-- Panel de Estadísticas -->
            <div class="stats-panel">
                <div class="stat-card">
                    <div class="stat-title">Vehículos Actuales</div>
                    <div class="stat-value" id="currentVehicles">0</div>
                </div>

                <div class="stat-card">
                    <div class="stat-title">Total Detectados</div>
                    <div class="stat-value" id="totalVehicles">0</div>
                </div>

                <div class="stat-card">
                    <div class="stat-title">Detalles en Tiempo Real</div>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <div class="detail-label">FPS</div>
                            <div class="detail-value" id="fps">0</div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Promedio</div>
                            <div class="detail-value" id="avgVehicles">0</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Listas de Tipos y Colores -->
        <div class="dashboard">
            <div class="vehicle-list">
                <div class="list-title">🚙 Tipos de Vehículos</div>
                <div id="vehicleTypes">
                    <div class="loading">Esperando datos...</div>
                </div>
            </div>

            <div class="vehicle-list">
                <div class="list-title">🎨 Colores Detectados</div>
                <div id="vehicleColors">
                    <div class="loading">Esperando datos...</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const LOCAL_DETECTOR = 'http://localhost:8080';
        let selectedCamera = null;
        let websocket = null;
        let statsInterval = null;

        const canvas = document.getElementById('videoCanvas');
        const ctx = canvas.getContext('2d');
        const cameraSelector = document.getElementById('cameraSelector');
        const statusIndicator = document.getElementById('connectionStatus');

        // Colores para badges
        const colorMap = {
            'rojo': '#f44336',
            'azul': '#2196F3',
            'verde': '#4CAF50',
            'amarillo': '#FFEB3B',
            'naranja': '#FF9800',
            'negro': '#000000',
            'blanco': '#FFFFFF',
            'gris': '#9E9E9E',
            'morado': '#9C27B0'
        };

        // Inicializar
        async function init() {
            await loadCameras();
            if (selectedCamera) {
                connectWebSocket();
                startStatsPolling();
            }
        }

        // Cargar lista de cámaras
        async function loadCameras() {
            try {
                const response = await fetch('/api/vehicle-monitor/stats');
                const data = await response.json();

                // Ya no necesitamos selector de cámara, solo hay una
                selectedCamera = data.camera_id || 'camera_01';
            } catch (error) {
                console.error('Error loading cameras:', error);
                updateStatus(false, 'Error de conexión');
            }
        }

        // Cambio de cámara
        cameraSelector.addEventListener('change', (e) => {
            if (e.target.value) {
                selectedCamera = e.target.value;
                if (websocket) {
                    websocket.close();
                }
                connectWebSocket();
            }
        });

        // Conectar WebSocket para video
        function connectWebSocket() {
            if (!selectedCamera) return;

            const wsUrl = `ws://localhost:8080/ws/stream`;
            websocket = new WebSocket(wsUrl);
            websocket.binaryType = 'arraybuffer';

            websocket.onopen = () => {
                console.log('✓ WebSocket conectado');
                updateStatus(true);
            };

            websocket.onmessage = (event) => {
                const blob = new Blob([event.data], {type: 'image/jpeg'});
                const url = URL.createObjectURL(blob);
                const img = new Image();

                img.onload = () => {
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                    URL.revokeObjectURL(url);
                };

                img.src = url;
            };

            websocket.onerror = (error) => {
                console.error('✗ WebSocket error:', error);
                updateStatus(false, 'Error en transmisión');
            };

            websocket.onclose = () => {
                console.log('⚠ WebSocket cerrado, reconectando...');
                updateStatus(false, 'Reconectando...');
                setTimeout(connectWebSocket, 3000);
            };
        }

        // Polling de estadísticas
        function startStatsPolling() {
            if (statsInterval) {
                clearInterval(statsInterval);
            }

            statsInterval = setInterval(updateStats, 1000);
            updateStats(); // Primera actualización inmediata
        }

        // Actualizar estadísticas
        async function updateStats() {
            try {
                const response = await fetch('/api/vehicle-monitor/camera_01');
                const data = await response.json();

                // Actualizar valores principales
                document.getElementById('currentVehicles').textContent = data.current_vehicles || 0;
                document.getElementById('totalVehicles').textContent = data.total_detected || 0;
                document.getElementById('fps').textContent = (data.fps || 0).toFixed(1);
                
                const avgHistory = data.history || [];
                const avg = avgHistory.length > 0 
                    ? avgHistory.reduce((a, b) => a + b, 0) / avgHistory.length 
                    : 0;
                document.getElementById('avgVehicles').textContent = avg.toFixed(1);

                // Actualizar tipos de vehículos
                updateVehicleTypes(data.vehicle_types || {});

                // Actualizar colores
                updateVehicleColors(data.vehicle_colors || {});

            } catch (error) {
                console.error('Error updating stats:', error);
            }
        }

        // Actualizar lista de tipos
        function updateVehicleTypes(types) {
            const container = document.getElementById('vehicleTypes');
            
            if (Object.keys(types).length === 0) {
                container.innerHTML = '<div class="loading">Sin datos aún...</div>';
                return;
            }

            container.innerHTML = '';
            Object.entries(types).sort((a, b) => b[1] - a[1]).forEach(([type, count]) => {
                const item = document.createElement('div');
                item.className = 'vehicle-item';
                item.innerHTML = `
                    <span class="vehicle-type">${type}</span>
                    <span class="vehicle-count">${count}</span>
                `;
                container.appendChild(item);
            });
        }

        // Actualizar lista de colores
        function updateVehicleColors(colors) {
            const container = document.getElementById('vehicleColors');
            
            if (Object.keys(colors).length === 0) {
                container.innerHTML = '<div class="loading">Sin datos aún...</div>';
                return;
            }

            container.innerHTML = '';
            Object.entries(colors).sort((a, b) => b[1] - a[1]).forEach(([color, count]) => {
                const item = document.createElement('div');
                item.className = 'vehicle-item';
                const bgColor = colorMap[color.toLowerCase()] || '#666';
                item.innerHTML = `
                    <span>
                        <span class="color-badge" style="background: ${bgColor};"></span>
                        <span class="vehicle-type">${color}</span>
                    </span>
                    <span class="vehicle-count">${count}</span>
                `;
                container.appendChild(item);
            });
        }

        // Actualizar estado de conexión
        function updateStatus(isOnline, message = null) {
            if (isOnline) {
                statusIndicator.textContent = message || '🟢 En línea';
                statusIndicator.className = 'status-indicator status-online';
            } else {
                statusIndicator.textContent = message || '🔴 Desconectado';
                statusIndicator.className = 'status-indicator status-offline';
            }
        }

        // Iniciar aplicación
        init();
    </script>
</body>
</html>