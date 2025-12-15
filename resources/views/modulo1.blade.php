@extends('layouts.app')

@section('title', 'Monitor de Vehículos')

@push('styles')
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
@endpush

@section('content')
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
    <div class="flex items-center justify-between mb-4">
        <label class="text-lg font-semibold text-gray-800">🎥 Seleccionar Cámara</label>
        <span id="activeCameraName" class="text-sm text-gray-500 italic">Cargando...</span>
    </div>
    
    <!-- Search Box -->
    <div class="mb-3">
        <div class="relative">
            <input 
                type="text" 
                id="cameraSearch" 
                placeholder="🔍 Buscar cámara por nombre o país..." 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm"
            />
        </div>
    </div>
    
    <!-- Camera Selector -->
    <select id="cameraSelect" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm" size="8">
        <option value="" disabled>Cargando cámaras...</option>
    </select>
    
    <!-- Stats -->
    <div class="mt-3 flex items-center justify-between text-xs text-gray-500">
        <span id="cameraStats">0 cámaras disponibles</span>
        <button id="refreshCameras" class="text-blue-600 hover:text-blue-800 font-medium">🔄 Actualizar</button>
    </div>
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
        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-3 border-b">🚙 Tipos de Vehículos</h3>
        <div id="vehicleTypes">
            <div class="text-center text-gray-500 py-8">Esperando datos...</div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4 pb-3 border-b">🎨 Colores Detectados</h3>
        <div id="vehicleColors">
            <div class="text-center text-gray-500 py-8">Esperando datos...</div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
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

    // Estado de cámaras
    let allCameras = [];
    let filteredCameras = [];

    // Event listener para cambio de cámara
    cameraSelect.addEventListener('change', (e) => {
        currentCamera = e.target.value;
        updateActiveCameraName();
        reconnectWebSocket();
    });

    // Event listener para búsqueda
    const searchInput = document.getElementById('cameraSearch');
    searchInput.addEventListener('input', (e) => {
        filterCameras(e.target.value);
    });

    // Event listener para refresh
    document.getElementById('refreshCameras').addEventListener('click', () => {
        loadCameras();
    });

    // Inicializar
    function init() {
        loadCameras();
        connectWebSocket();
        startStatsPolling();
        
        // Auto-refresh de cámaras cada 30 segundos
        setInterval(loadCameras, 30000);
    }

    // Cargar lista de cámaras desde API
    async function loadCameras() {
        try {
            const response = await fetch('http://localhost:8080/api/cameras');
            
            if (!response.ok) {
                throw new Error('Detector no disponible');
            }
            
            const data = await response.json();
            
            allCameras = data.cameras;
            filteredCameras = [...allCameras];
            
            renderCameras();
            updateCameraStats();
            
            // Seleccionar la primera online por defecto si no hay selección
            if (!currentCamera && filteredCameras.length > 0) {
                const firstOnline = filteredCameras.find(cam => cam.status === 'online');
                currentCamera = firstOnline ? firstOnline.id : filteredCameras[0].id;
                document.getElementById('cameraSelect').value = currentCamera;
                updateActiveCameraName();
            }
        } catch (error) {
            console.warn('⚠️ Detector no disponible, usando lista de cámaras por defecto:', error);
            
            // Fallback: Lista de cámaras hardcoded
            allCameras = [
                { id: 'CAM_001', name: 'Oracle Server', type: 'websocket', status: 'offline' },
                { id: 'CAM_002', name: 'Skyline Cochabamba', type: 'skyline', status: 'offline' },
                { id: 'CAM_003', name: 'Maceió Pájucara', type: 'skyline', status: 'offline' },
                { id: 'CAM_004', name: 'Balneario Camboriú Avenida Brasil', type: 'skyline', status: 'offline' },
                { id: 'CAM_005', name: 'São Paulo Avenida Paulista', type: 'skyline', status: 'offline' },
                { id: 'CAM_006', name: 'Balneario Camboriú Avenue dos Estados', type: 'skyline', status: 'offline' },
                { id: 'CAM_007', name: 'Canmore Alberta', type: 'skyline', status: 'offline' },
                { id: 'CAM_008', name: 'Calgary Streets', type: 'skyline', status: 'offline' },
                { id: 'CAM_009', name: 'Collingwood Hurontario Street', type: 'skyline', status: 'offline' },
                { id: 'CAM_010', name: 'Surrey Border', type: 'skyline', status: 'offline' },
                { id: 'CAM_011', name: 'Cusco Plaza Mayor', type: 'skyline', status: 'offline' },
                { id: 'CAM_012', name: 'Chachapoyas Plaza Mayor', type: 'skyline', status: 'offline' },
                { id: 'CAM_013', name: 'Oxapampa Plaza de Armas', type: 'skyline', status: 'offline' },
                { id: 'CAM_014', name: 'Punta Arenas Magallanes', type: 'skyline', status: 'offline' },
                { id: 'CAM_015', name: 'San Antonio Avenida Llolleo', type: 'skyline', status: 'offline' },
                { id: 'CAM_016', name: 'Las Vegas Nevada', type: 'skyline', status: 'offline' },
                { id: 'CAM_017', name: 'Virginia City Nevada', type: 'skyline', status: 'offline' },
                { id: 'CAM_018', name: 'Idyllwild Pine Cove', type: 'skyline', status: 'offline' },
                { id: 'CAM_019', name: 'Tehachapi Railroad', type: 'skyline', status: 'offline' },
                { id: 'CAM_020', name: 'Lee Vining California', type: 'skyline', status: 'offline' }
            ];
            
            filteredCameras = [...allCameras];
            renderCameras();
            updateCameraStats();
            
            // Seleccionar CAM_002 por defecto
            if (!currentCamera) {
                currentCamera = 'CAM_002';
                document.getElementById('cameraSelect').value = currentCamera;
                updateActiveCameraName();
            }
            
            // Mostrar advertencia
            document.getElementById('cameraStats').innerHTML = '⚠️ Detector offline - Lista estática';
        }
    }

    // Renderizar cámaras en el selector
    function renderCameras() {
        const select = document.getElementById('cameraSelect');
        const currentValue = select.value;
        
        select.innerHTML = '';
        
        if (filteredCameras.length === 0) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'No se encontraron cámaras';
            option.disabled = true;
            select.appendChild(option);
            return;
        }
        
        // Agrupar por país
        const grouped = groupCamerasByCountry(filteredCameras);
        
        Object.entries(grouped).forEach(([country, cameras]) => {
            // Agregar optgroup si hay múltiples países
            if (Object.keys(grouped).length > 1) {
                const optgroup = document.createElement('optgroup');
                optgroup.label = country;
                
                cameras.forEach(cam => {
                    const option = createCameraOption(cam);
                    optgroup.appendChild(option);
                });
                
                select.appendChild(optgroup);
            } else {
                // Si solo hay un país, no usar optgroup
                cameras.forEach(cam => {
                    const option = createCameraOption(cam);
                    select.appendChild(option);
                });
            }
        });
        
        // Restaurar selección si existe
        if (currentValue && Array.from(select.options).some(opt => opt.value === currentValue)) {
            select.value = currentValue;
        }
    }

    // Crear opción de cámara con indicador de estado
    function createCameraOption(cam) {
        const option = document.createElement('option');
        option.value = cam.id;
        
        const statusIcon = cam.status === 'online' ? '🟢' : '🔴';
        const statusText = cam.status === 'online' ? 'Online' : 'Offline';
        
        option.textContent = `${statusIcon} ${cam.id} - ${cam.name}`;
        option.title = `${cam.name} (${statusText})`;
        
        // Deshabilitar cámaras offline (opcional)
        // option.disabled = cam.status === 'offline';
        
        return option;
    }

    // Agrupar cámaras por país
    function groupCamerasByCountry(cameras) {
        const countryMap = {
            'Oracle': ['CAM_001'],
            'Bolivia': ['CAM_002'],
            'Brasil': ['CAM_003', 'CAM_004', 'CAM_005', 'CAM_006'],
            'Canadá': ['CAM_007', 'CAM_008', 'CAM_009', 'CAM_010'],
            'Perú': ['CAM_011', 'CAM_012', 'CAM_013'],
            'Chile': ['CAM_014', 'CAM_015'],
            'USA': ['CAM_016', 'CAM_017', 'CAM_018', 'CAM_019', 'CAM_020']
        };
        
        const grouped = {};
        
        cameras.forEach(cam => {
            let country = 'Otros';
            for (const [c, ids] of Object.entries(countryMap)) {
                if (ids.includes(cam.id)) {
                    country = c;
                    break;
                }
            }
            
            if (!grouped[country]) {
                grouped[country] = [];
            }
            grouped[country].push(cam);
        });
        
        return grouped;
    }

    // Filtrar cámaras por búsqueda
    function filterCameras(searchTerm) {
        const term = searchTerm.toLowerCase().trim();
        
        if (!term) {
            filteredCameras = [...allCameras];
        } else {
            filteredCameras = allCameras.filter(cam => {
                return cam.name.toLowerCase().includes(term) ||
                       cam.id.toLowerCase().includes(term);
            });
        }
        
        renderCameras();
        updateCameraStats();
    }

    // Actualizar estadísticas de cámaras
    function updateCameraStats() {
        const total = filteredCameras.length;
        const online = filteredCameras.filter(cam => cam.status === 'online').length;
        const offline = total - online;
        
        const statsText = `${total} cámara${total !== 1 ? 's' : ''} (🟢 ${online} online, 🔴 ${offline} offline)`;
        document.getElementById('cameraStats').textContent = statsText;
    }

    // Actualizar nombre de cámara activa
    function updateActiveCameraName() {
        const cam = allCameras.find(c => c.id === currentCamera);
        if (cam) {
            document.getElementById('activeCameraName').textContent = `📡 ${cam.name}`;
        }
    }

    // Desconectar WebSocket anterior
    function reconnectWebSocket() {
        console.log(`🔄 Reconectando a ${currentCamera}...`);
        if (websocket) {
            console.log(`❌ Cerrando WebSocket anterior`);
            websocket.close();
            websocket = null;
        }
        // Pequeño delay para asegurar que se cierre antes de reconectar
        setTimeout(connectWebSocket, 500);
    }

    // Conectar WebSocket para video
    function connectWebSocket() {
        const wsUrl = `ws://localhost:8080/ws/stream?camera_id=${currentCamera}`;
        console.log(`🔌 Conectando a: ${wsUrl}`);
        console.log(`📸 Camera actual: ${currentCamera}`);
        
        websocket = new WebSocket(wsUrl);
        websocket.binaryType = 'arraybuffer';
        
        // Agregar ID único para este WebSocket para debugging
        websocket._debug_camera = currentCamera;
        websocket._debug_id = Math.random().toString(36).substr(2, 9);
        console.log(`🆔 WebSocket ID: ${websocket._debug_id} para ${websocket._debug_camera}`);

        websocket.onopen = () => {
            console.log(`✅ WebSocket abierto para ${websocket._debug_camera}`);
            updateStatus(true);
        };

        websocket.onmessage = (event) => {
            console.log(`📦 Frame recibido de ${websocket._debug_camera} (${event.data.byteLength} bytes)`);
            
            const blob = new Blob([event.data], {type: 'image/jpeg'});
            const url = URL.createObjectURL(blob);
            const img = new Image();

            img.onload = () => {
                // Verificar que currentCamera sigue siendo el mismo
                if (websocket._debug_camera !== currentCamera) {
                    console.warn(`⚠️ Cámara cambió! Era ${websocket._debug_camera}, ahora es ${currentCamera}`);
                }
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                URL.revokeObjectURL(url);
            };

            img.onerror = () => {
                console.error(`❌ Error cargando frame de ${websocket._debug_camera}`);
            };

            img.src = url;
        };

        websocket.onerror = (error) => {
            console.error(`❌ WebSocket error para ${websocket._debug_camera}:`, error);
            updateStatus(false, 'Error en transmisión');
        };

        websocket.onclose = () => {
            console.log(`👋 WebSocket cerrado para ${websocket._debug_camera}, reconectando...`);
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
@endpush