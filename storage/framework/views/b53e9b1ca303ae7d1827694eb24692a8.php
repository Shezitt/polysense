<?php $__env->startSection('title', 'Deteccion'); ?>

<?php $__env->startPush('styles'); ?>
<style>
    .status-online {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }

    .stat-card {
        transition: transform 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-2px);
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="bg-white rounded-lg shadow-lg p-6 mb-6">
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-3xl font-bold text-gray-800">Monitor de Vehículos en Tiempo Real</h1>
        <div id="connectionStatus" class="px-4 py-2 rounded-full text-white font-semibold bg-red-500">
            Conectando...
        </div>
    </div>
</div>

<!-- Camera Selector -->
<div class="bg-white rounded-lg shadow-lg p-6 mb-6">
    <label class="block text-sm font-medium text-gray-700 mb-2">Seleccionar Cámara</label>
    <select id="cameraSelect" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
        <option value="CAM_002">Skyline Cochabamba</option>
        <option value="CAM_001">Oracle Server</option>
    </select>
</div>

<div class="flex flex-col lg:flex-row gap-6 mb-6">
    <!-- Video Section -->
    <div class="bg-white rounded-lg shadow-lg p-6 lg:basis-[70%]">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Transmisión en Vivo</h2>
        <div class="bg-black rounded-lg overflow-hidden">
            <canvas id="videoCanvas" width="800" height="600" class="w-full h-auto"></canvas>
        </div>
    </div>

    <!-- Stats Panel -->
    <div class="space-y-4 lg:basis-[30%]">
        <div class="bg-white rounded-lg shadow-lg p-6 stat-card">
            <div class="text-sm text-gray-500 uppercase tracking-wide mb-2">Vehículos Actuales</div>
            <div class="text-4xl font-bold text-green-600" id="currentVehicles">0</div>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6 stat-card">
            <div class="text-sm text-gray-500 uppercase tracking-wide mb-2">Total Detectados</div>
            <div class="text-4xl font-bold text-blue-600" id="totalVehicles">0</div>
        </div>

        <div class="bg-white rounded-lg shadow-lg p-6 stat-card">
            <div class="text-sm text-gray-500 uppercase tracking-wide mb-4">Detalles en Tiempo Real</div>
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <div class="text-xs text-gray-500 mb-1">FPS</div>
                    <div class="text-2xl font-bold text-indigo-600" id="fps">0</div>
                </div>
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <div class="text-xs text-gray-500 mb-1">Promedio</div>
                    <div class="text-2xl font-bold text-indigo-600" id="avgVehicles">0</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vehicle Types and Colors -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-3 border-b">Tipos de Vehículos</h3>
        <div id="vehicleTypes">
            <div class="text-center text-gray-500 py-8">Esperando datos...</div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-3 border-b">Colores Detectados</h3>
        <div id="vehicleColors">
            <div class="text-center text-gray-500 py-8">Esperando datos...</div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    const LOCAL_DETECTOR = 'http://localhost:8080';
    let websocket = null;
    let statsInterval = null;
    let currentCamera = 'CAM_002'; // Default

    const canvas = document.getElementById('videoCanvas');
    const ctx = canvas.getContext('2d');
    const statusIndicator = document.getElementById('connectionStatus');
    const cameraSelect = document.getElementById('cameraSelect');

    // Colores para badges
    const colorMap = {
        'rojo': '#ef4444',
        'azul': '#3b82f6',
        'verde': '#10b981',
        'amarillo': '#eab308',
        'naranja': '#f97316',
        'negro': '#1f2937',
        'blanco': '#f3f4f6',
        'gris': '#6b7280',
        'morado': '#a855f7'
    };

    // Event listener para cambio de cámara
    cameraSelect.addEventListener('change', (e) => {
        currentCamera = e.target.value;
        reconnectWebSocket();
    });

    // Inicializar
    function init() {
        connectWebSocket();
        startStatsPolling();
    }

    // Desconectar WebSocket anterior
    function reconnectWebSocket() {
        console.log(`Reconectando a ${currentCamera}...`);
        if (websocket) {
            console.log(`Cerrando WebSocket anterior`);
            websocket.close();
            websocket = null;
        }
        // Pequeño delay para asegurar que se cierre antes de reconectar
        setTimeout(connectWebSocket, 500);
    }

    // Conectar WebSocket para video
    function connectWebSocket() {
        const wsUrl = `ws://localhost:8080/ws/stream?camera_id=${currentCamera}`;
        console.log(`Conectando a: ${wsUrl}`);
        console.log(`Camera actual: ${currentCamera}`);
        
        websocket = new WebSocket(wsUrl);
        websocket.binaryType = 'arraybuffer';
        
        // Agregar ID único para este WebSocket para debugging
        websocket._debug_camera = currentCamera;
        websocket._debug_id = Math.random().toString(36).substr(2, 9);
        console.log(`WebSocket ID: ${websocket._debug_id} para ${websocket._debug_camera}`);

        websocket.onopen = () => {
            console.log(`WebSocket abierto para ${websocket._debug_camera}`);
            updateStatus(true);
        };

        websocket.onmessage = (event) => {
            console.log(`Frame recibido de ${websocket._debug_camera} (${event.data.byteLength} bytes)`);
            
            const blob = new Blob([event.data], {type: 'image/jpeg'});
            const url = URL.createObjectURL(blob);
            const img = new Image();

            img.onload = () => {
                // Verificar que currentCamera sigue siendo el mismo
                if (websocket._debug_camera !== currentCamera) {
                    console.warn(`Cámara cambió! Era ${websocket._debug_camera}, ahora es ${currentCamera}`);
                }
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                URL.revokeObjectURL(url);
            };

            img.onerror = () => {
                console.error(`Error cargando frame de ${websocket._debug_camera}`);
            };

            img.src = url;
        };

        websocket.onerror = (error) => {
            console.error(`WebSocket error para ${websocket._debug_camera}:`, error);
            updateStatus(false, 'Error en transmisión');
        };

        websocket.onclose = () => {
            console.log(`WebSocket cerrado para ${websocket._debug_camera}, reconectando...`);
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
        updateStats();
    }

    // Actualizar estadísticas
    async function updateStats() {
        try {
            const response = await fetch(`/api/vehicle-monitor/${currentCamera}`);
            const data = await response.json();

            // Verificar status de la cámara
            if (data.status === 'offline') {
                document.getElementById('connectionStatus').textContent = 'OFFLINE';
                document.getElementById('connectionStatus').className = 'px-3 py-1 rounded text-sm font-semibold bg-red-100 text-red-800';
                
                // Mostrar mensaje en canvas
                const canvas = document.getElementById('videoCanvas');
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#1a1a1a';
                ctx.fillRect(0, 0, canvas.width, canvas.height);
                ctx.fillStyle = '#ff0000';
                ctx.font = 'bold 48px Arial';
                ctx.textAlign = 'center';
                ctx.fillText(`${data.camera_name || 'Cámara'}`, canvas.width/2, canvas.height/2 - 50);
                ctx.fillText('OFFLINE', canvas.width/2, canvas.height/2 + 50);
            } else {
                document.getElementById('connectionStatus').textContent = 'En línea';
                document.getElementById('connectionStatus').className = 'px-3 py-1 rounded text-sm font-semibold bg-green-100 text-green-800';
            }

            document.getElementById('currentVehicles').textContent = data.current_vehicles || 0;
            document.getElementById('totalVehicles').textContent = data.total_detected || 0;
            document.getElementById('fps').textContent = (data.fps || 0).toFixed(1);
            
            const avgHistory = data.history || [];
            const avg = avgHistory.length > 0 
                ? avgHistory.reduce((a, b) => a + b, 0) / avgHistory.length 
                : 0;
            document.getElementById('avgVehicles').textContent = avg.toFixed(1);

            updateVehicleTypes(data.vehicle_types || {});
            updateVehicleColors(data.vehicle_colors || {});

        } catch (error) {
            console.error('Error updating stats:', error);
            document.getElementById('connectionStatus').textContent = 'Error';
            document.getElementById('connectionStatus').className = 'px-3 py-1 rounded text-sm font-semibold bg-orange-100 text-orange-800';
        }
    }

    // Actualizar lista de tipos
    function updateVehicleTypes(types) {
        const container = document.getElementById('vehicleTypes');
        
        if (Object.keys(types).length === 0) {
            container.innerHTML = '<div class="text-center text-gray-500 py-8">Sin datos aún...</div>';
            return;
        }

        container.innerHTML = '';
        Object.entries(types).sort((a, b) => b[1] - a[1]).forEach(([type, count]) => {
            const item = document.createElement('div');
            item.className = 'flex justify-between items-center bg-gray-50 rounded-lg p-3 mb-2';
            item.innerHTML = `
                <span class="font-semibold text-gray-700 capitalize">${type}</span>
                <span class="bg-green-500 text-white px-3 py-1 rounded-full font-bold text-sm">${count}</span>
            `;
            container.appendChild(item);
        });
    }

    // Actualizar lista de colores
    function updateVehicleColors(colors) {
        const container = document.getElementById('vehicleColors');
        
        if (Object.keys(colors).length === 0) {
            container.innerHTML = '<div class="text-center text-gray-500 py-8">Sin datos aún...</div>';
            return;
        }

        container.innerHTML = '';
        Object.entries(colors).sort((a, b) => b[1] - a[1]).forEach(([color, count]) => {
            const item = document.createElement('div');
            item.className = 'flex justify-between items-center bg-gray-50 rounded-lg p-3 mb-2';
            const bgColor = colorMap[color.toLowerCase()] || '#6b7280';
            item.innerHTML = `
                <span class="flex items-center">
                    <span class="w-5 h-5 rounded-full mr-3 border-2 border-gray-300" style="background: ${bgColor};"></span>
                    <span class="font-semibold text-gray-700 capitalize">${color}</span>
                </span>
                <span class="bg-blue-500 text-white px-3 py-1 rounded-full font-bold text-sm">${count}</span>
            `;
            container.appendChild(item);
        });
    }

    // Actualizar estado de conexión
    function updateStatus(isOnline, message = null) {
        if (isOnline) {
            statusIndicator.textContent = message || 'En línea';
            statusIndicator.className = 'status-online px-4 py-2 rounded-full font-semibold bg-green-500';
        } else {
            statusIndicator.textContent = message || 'Desconectado';
            statusIndicator.className = 'px-4 py-2 rounded-full font-semibold bg-red-500';
        }
    }

    // Iniciar aplicación cuando el DOM esté listo
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\ProyectoFinal\polysense\resources\views/modulo1.blade.php ENDPATH**/ ?>