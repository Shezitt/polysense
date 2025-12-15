@extends('layouts.app')

@section('title', 'Módulo 2: Analítica y Reportes')

@section('content')
<div class="bg-white rounded-lg shadow-lg p-6 mb-6">
    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Módulo 2: Analítica y Reportes Viales</h1>
            <p class="text-gray-500 mt-1">Visualización de datos históricos y estadísticas de detección.</p>
        </div>
        
        <div class="flex items-center gap-3 bg-gray-50 p-3 rounded-lg border border-gray-200 flex-wrap">
            <div class="flex flex-col flex-1 min-w-[250px]">
                <label class="text-xs text-gray-500 font-semibold ml-1 mb-1">🎥 Cámara</label>
                <input 
                    type="text" 
                    id="cameraSearchM2" 
                    placeholder="🔍 Buscar..." 
                    class="w-full border-gray-300 rounded text-sm px-3 py-1.5 focus:ring-blue-500 focus:border-blue-500 mb-1"
                />
                <select id="cameraSelectM2" size="5" class="border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Cargando cámaras...</option>
                </select>
                <div class="mt-1 text-xs text-gray-500" id="cameraStatsM2">0 cámaras</div>
            </div>

            <div class="h-8 w-px bg-gray-300 mx-2"></div>

            <div class="flex flex-col">
                <label class="text-xs text-gray-500 font-semibold ml-1">Rango de Fechas</label>
                <div class="flex items-center gap-2">
                    <input type="date" id="fechaInicio" class="border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500">
                    <span class="text-gray-400">-</span>
                    <input type="date" id="fechaFin" class="border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div class="h-8 w-px bg-gray-300 mx-2"></div>

            <div class="flex flex-col">
                <label class="text-xs text-gray-500 font-semibold ml-1">Tipo de Vehículo</label>
                <select id="tipoVehiculo" class="border-gray-300 rounded text-sm min-w-[150px] focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Todos</option>
                    <option value="Auto">Autos</option>
                    <option value="Moto">Motos</option>
                    <option value="Bus">Buses</option>
                </select>
            </div>

            <button onclick="aplicarFiltros()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-md text-sm font-medium transition-colors">
                Filtrar
            </button>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="lg:col-span-2 bg-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-lg font-semibold text-gray-800">Vehículos Detectados por Día</h2>
            <button class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-ellipsis-v"></i>
            </button>
        </div>
        <div class="relative h-[300px] w-full">
            <canvas id="dailyChart"></canvas>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-lg font-semibold text-gray-800">Distribución</h2>
            <button class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-ellipsis-v"></i>
            </button>
        </div>
        <div class="relative h-[300px] w-full flex justify-center">
            <canvas id="typeDistributionChart"></canvas>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-lg overflow-hidden">
    <div class="p-6 border-b border-gray-100 flex justify-between items-center">
        <h2 class="text-lg font-semibold text-gray-800">Registro de Detecciones</h2>
        <div class="flex gap-2">
            <button onclick="exportarExcel()" class="px-3 py-1 text-sm text-green-600 bg-green-50 rounded border border-green-200 hover:bg-green-100 flex items-center gap-2">
                Exportar Excel
            </button>
            <button onclick="window.print()" class="px-3 py-1 text-sm text-red-600 bg-red-50 rounded border border-red-200 hover:bg-red-100 flex items-center gap-2">
                PDF / Imprimir
            </button>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                    <th class="px-6 py-4 font-medium">Fecha/Hora</th>
                    <th class="px-6 py-4 font-medium">Tipo de Vehículo</th>
                    <th class="px-6 py-4 font-medium">Color</th>
                    <th class="px-6 py-4 font-medium text-center">Confianza IA</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm text-gray-700" id="reportsTableBody">
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                        Cargando registros...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-between items-center text-sm text-gray-500">
        <span>Total: <span id="totalRegistros">0</span> registros</span>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    const LOCAL_DETECTOR = 'http://localhost:8080';
    let chartBar = null;
    let chartPie = null;
    let currentCameraM2 = null;
    let allRegistros = [];

    const cameraSelectM2 = document.getElementById('cameraSelectM2');
    const fechaInicio = document.getElementById('fechaInicio');
    const fechaFin = document.getElementById('fechaFin');
    const tipoVehiculo = document.getElementById('tipoVehiculo');

    // Establecer fechas por defecto
    function setDefaultDates() {
        const hoy = new Date();
        const primerDia = new Date(hoy.getFullYear(), hoy.getMonth(), 1);
        
        fechaInicio.valueAsDate = primerDia;
        fechaFin.valueAsDate = hoy;
    }

    let allCamerasM2 = [];
    let filteredCamerasM2 = [];

    // Event listener para búsqueda
    const searchInputM2 = document.getElementById('cameraSearchM2');
    searchInputM2.addEventListener('input', (e) => {
        filterCamerasM2(e.target.value);
    });

    // Cargar cámaras desde API
    async function loadCamerasM2() {
        try {
            const response = await fetch(`${LOCAL_DETECTOR}/api/cameras`);
            
            if (!response.ok) {
                throw new Error('Detector no disponible');
            }
            
            const data = await response.json();
            
            allCamerasM2 = data.cameras;
            filteredCamerasM2 = [...allCamerasM2];
            
            renderCamerasM2();
            updateCameraStatsM2();
            
            // Seleccionar la primera online por defecto
            if (!currentCameraM2 && filteredCamerasM2.length > 0) {
                const firstOnline = filteredCamerasM2.find(cam => cam.status === 'online');
                currentCameraM2 = firstOnline ? firstOnline.id : filteredCamerasM2[0].id;
                cameraSelectM2.value = currentCameraM2;
                cargarRegistros();
            }
        } catch (error) {
            console.warn('⚠️ Detector no disponible, usando lista de cámaras por defecto:', error);
            
            // Fallback: Lista de cámaras hardcoded
            allCamerasM2 = [
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
            
            filteredCamerasM2 = [...allCamerasM2];
            renderCamerasM2();
            updateCameraStatsM2();
            
            // Seleccionar CAM_002 por defecto
            if (!currentCameraM2) {
                currentCameraM2 = 'CAM_002';
                cameraSelectM2.value = currentCameraM2;
                cargarRegistros();
            }
            
            // Mostrar advertencia
            document.getElementById('cameraStatsM2').innerHTML = '⚠️ Detector offline';
        }
    }

    // Renderizar cámaras
    function renderCamerasM2() {
        const currentValue = cameraSelectM2.value;
        cameraSelectM2.innerHTML = '';
        
        if (filteredCamerasM2.length === 0) {
            cameraSelectM2.innerHTML = '<option value="">No se encontraron cámaras</option>';
            return;
        }
        
        // Agrupar por país
        const grouped = groupCamerasByCountryM2(filteredCamerasM2);
        
        Object.entries(grouped).forEach(([country, cameras]) => {
            if (Object.keys(grouped).length > 1) {
                const optgroup = document.createElement('optgroup');
                optgroup.label = country;
                
                cameras.forEach(cam => {
                    const option = createCameraOptionM2(cam);
                    optgroup.appendChild(option);
                });
                
                cameraSelectM2.appendChild(optgroup);
            } else {
                cameras.forEach(cam => {
                    const option = createCameraOptionM2(cam);
                    cameraSelectM2.appendChild(option);
                });
            }
        });
        
        // Restaurar selección
        if (currentValue && Array.from(cameraSelectM2.options).some(opt => opt.value === currentValue)) {
            cameraSelectM2.value = currentValue;
        }
    }

    // Crear opción de cámara
    function createCameraOptionM2(cam) {
        const option = document.createElement('option');
        option.value = cam.id;
        const statusIcon = cam.status === 'online' ? '🟢' : '🔴';
        option.textContent = `${statusIcon} ${cam.id} - ${cam.name}`;
        option.title = `${cam.name} (${cam.status})`;
        return option;
    }

    // Agrupar por país
    function groupCamerasByCountryM2(cameras) {
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
            if (!grouped[country]) grouped[country] = [];
            grouped[country].push(cam);
        });
        return grouped;
    }

    // Filtrar cámaras
    function filterCamerasM2(searchTerm) {
        const term = searchTerm.toLowerCase().trim();
        
        if (!term) {
            filteredCamerasM2 = [...allCamerasM2];
        } else {
            filteredCamerasM2 = allCamerasM2.filter(cam => {
                return cam.name.toLowerCase().includes(term) ||
                       cam.id.toLowerCase().includes(term);
            });
        }
        
        renderCamerasM2();
        updateCameraStatsM2();
    }

    // Actualizar stats
    function updateCameraStatsM2() {
        const total = filteredCamerasM2.length;
        const online = filteredCamerasM2.filter(cam => cam.status === 'online').length;
        document.getElementById('cameraStatsM2').textContent = `${total} cámara${total !== 1 ? 's' : ''} (🟢 ${online} online)`;
    }

    // Event listener para cambio de cámara
    cameraSelectM2.addEventListener('change', (e) => {
        currentCameraM2 = e.target.value;
        cargarRegistros();
    });

    // Cargar registros desde XML del detector
    async function cargarRegistros() {
        if (!currentCameraM2) return;

        try {
            const response = await fetch(`/api/reports?camera_id=${currentCameraM2}`);
            const data = await response.json();
            
            allRegistros = data.registros || [];
            aplicarFiltros();
        } catch (error) {
            console.error('Error cargando registros:', error);
            document.getElementById('reportsTableBody').innerHTML = `
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-red-500">
                        Error cargando registros
                    </td>
                </tr>
            `;
        }
    }

    // Aplicar filtros y actualizar tabla
    function aplicarFiltros() {
        const fechaInicioVal = fechaInicio.value;
        const fechaFinVal = fechaFin.value;
        const tipoVal = tipoVehiculo.value;

        let filtrados = allRegistros;

        if (fechaInicioVal) {
            filtrados = filtrados.filter(r => {
                const fecha = r.fecha.split(' ')[0];
                return fecha >= fechaInicioVal;
            });
        }

        if (fechaFinVal) {
            filtrados = filtrados.filter(r => {
                const fecha = r.fecha.split(' ')[0];
                return fecha <= fechaFinVal;
            });
        }

        if (tipoVal) {
            filtrados = filtrados.filter(r => r.tipo === tipoVal);
        }

        actualizarTabla(filtrados);
        actualizarGraficos(filtrados);
    }

    // Actualizar tabla de registros
    function actualizarTabla(registros) {
        const tbody = document.getElementById('reportsTableBody');
        const totalSpan = document.getElementById('totalRegistros');

        if (registros.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                        No se encontraron registros con los filtros seleccionados
                    </td>
                </tr>
            `;
            totalSpan.textContent = '0';
            return;
        }

        const vehicleColorMap = {
            'rojo': 'bg-red-500',
            'azul': 'bg-blue-500',
            'verde': 'bg-green-500',
            'amarillo': 'bg-yellow-400',
            'naranja': 'bg-orange-500',
            'negro': 'bg-gray-800',
            'blanco': 'bg-gray-100 border border-gray-300',
            'gris': 'bg-gray-400',
            'morado': 'bg-purple-500',
        };

        const colorTypeMap = {
            'auto': 'bg-blue-500',
            'moto': 'bg-red-500',
            'bus': 'bg-green-500',
            'camion': 'bg-orange-500',
        };

        tbody.innerHTML = registros.map(r => {
            const colorTypeClass = colorTypeMap[r.tipo.toLowerCase()] || 'bg-gray-500';
            const colorBadgeClass = vehicleColorMap[r.color.toLowerCase()] || 'bg-gray-300';
            const conf = parseFloat(r.confianza || 0);
            const confColor = conf > 90 ? 'bg-green-100 text-green-800' : (conf > 75 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');

            return `
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-4">${r.fecha}</td>
                    <td class="px-6 py-4">
                        <span class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full ${colorTypeClass}"></span>
                            ${r.tipo}
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded-full ${colorBadgeClass}"></span>
                            <span class="capitalize">${r.color || 'desconocido'}</span>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="${confColor} px-3 py-1 rounded-full text-xs font-bold">${r.confianza}%</span>
                    </td>
                </tr>
            `;
        }).join('');

        totalSpan.textContent = registros.length;
    }

    // Actualizar gráficos
    function actualizarGraficos(registros) {
        const dailyCounts = {};
        const typesCount = { 'Auto': 0, 'Moto': 0, 'Bus': 0, 'Otro': 0 };

        registros.forEach(item => {
            const date = item.fecha.split(' ')[0];
            const type = item.tipo;

            if (!dailyCounts[date]) {
                dailyCounts[date] = { 'Auto': 0, 'Moto': 0, 'Bus': 0, 'Otro': 0 };
            }

            if (['Auto', 'Moto', 'Bus'].includes(type)) {
                dailyCounts[date][type]++;
                typesCount[type]++;
            } else {
                dailyCounts[date]['Otro']++;
                typesCount['Otro']++;
            }
        });

        const sortedDates = Object.keys(dailyCounts).sort();
        const labels = sortedDates;
        const dataAuto = sortedDates.map(date => dailyCounts[date]['Auto']);
        const dataMoto = sortedDates.map(date => dailyCounts[date]['Moto']);
        const dataBus = sortedDates.map(date => dailyCounts[date]['Bus']);

        // Destruir gráficos anteriores
        if (chartBar) chartBar.destroy();
        if (chartPie) chartPie.destroy();

        // Gráfico de barras
        const ctxBar = document.getElementById('dailyChart').getContext('2d');
        chartBar = new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Autos',
                        data: dataAuto,
                        backgroundColor: '#3b82f6',
                        borderRadius: 4,
                    },
                    {
                        label: 'Motos',
                        data: dataMoto,
                        backgroundColor: '#ef4444',
                        borderRadius: 4,
                    },
                    {
                        label: 'Buses',
                        data: dataBus,
                        backgroundColor: '#10b981',
                        borderRadius: 4,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8 } } },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [2, 2], drawBorder: false }, ticks: { stepSize: 1 } },
                    x: { grid: { display: false } }
                }
            }
        });

        // Gráfico de torta
        const ctxPie = document.getElementById('typeDistributionChart').getContext('2d');
        chartPie = new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: ['Autos', 'Motos', 'Buses', 'Otros'],
                datasets: [{
                    data: [typesCount['Auto'], typesCount['Moto'], typesCount['Bus'], typesCount['Otro']],
                    backgroundColor: ['#3b82f6', '#ef4444', '#10b981', '#9ca3af'],
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, padding: 20 } } }
            }
        });
    }

    // Exportar a Excel
    function exportarExcel() {
        const params = new URLSearchParams({
            camera_id: currentCameraM2,
            fecha_inicio: fechaInicio.value,
            fecha_fin: fechaFin.value,
            tipo: tipoVehiculo.value
        });
        window.location.href = `{{ route("exportar.excel") }}?${params.toString()}`;
    }

    // Inicializar
    document.addEventListener('DOMContentLoaded', function() {
        setDefaultDates();
        loadCamerasM2();
    });
</script>
@endpush