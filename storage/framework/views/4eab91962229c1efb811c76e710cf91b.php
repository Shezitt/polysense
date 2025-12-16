<?php $__env->startSection('title', 'Reportes'); ?>

<?php $__env->startSection('content'); ?>
<div class="bg-white rounded-lg shadow-lg p-6 mb-6">
    <!-- Título y botones en la misma línea -->
    <div class="flex justify-between items-start mb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Reportes: Analítica y Reportes Viales</h1>
            <p class="text-gray-500 text-sm mt-1">Visualización de datos históricos y estadísticas</p>
        </div>
        <div class="flex gap-2">
            <a href="<?php echo e(route('modulo2.my-notifications')); ?>" class="text-xs bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded transition whitespace-nowrap">
                Notificaciones
            </a>
            <a href="<?php echo e(route('modulo2.my-reports')); ?>" class="text-xs bg-green-50 hover:bg-green-100 px-3 py-1.5 rounded transition whitespace-nowrap">
                Reportes
            </a>
        </div>
    </div>
    
    <!-- Filtros compactos -->
    <form action="<?php echo e(route('modulo2')); ?>" method="GET" class="flex flex-wrap items-end gap-3 bg-gray-50 p-3 rounded-lg border border-gray-200">
        <div class="flex flex-col min-w-[160px]">
            <label class="text-xs text-gray-600 font-semibold mb-1">Cámara</label>
            <select name="camera_id" class="border-gray-300 rounded text-sm px-2 py-1.5 focus:ring-blue-500 focus:border-blue-500">
                <option value="CAM_002" <?php echo e(request('camera_id') == 'CAM_002' ? 'selected' : ''); ?>>Skyline Cochabamba</option>
                <option value="CAM_001" <?php echo e(request('camera_id') == 'CAM_001' ? 'selected' : ''); ?>>Oracle Server</option>
            </select>
        </div>

        <div class="flex flex-col">
            <label class="text-xs text-gray-600 font-semibold mb-1">Desde</label>
            <input type="date" name="fecha_inicio" class="border-gray-300 rounded text-sm px-2 py-1.5 focus:ring-blue-500 focus:border-blue-500" value="<?php echo e(request('fecha_inicio', date('Y-m-01'))); ?>">
        </div>
        
        <div class="flex flex-col">
            <label class="text-xs text-gray-600 font-semibold mb-1">Hasta</label>
            <input type="date" name="fecha_fin" class="border-gray-300 rounded text-sm px-2 py-1.5 focus:ring-blue-500 focus:border-blue-500" value="<?php echo e(request('fecha_fin', date('Y-m-d'))); ?>">
        </div>

        <div class="flex flex-col min-w-[120px]">
            <label class="text-xs text-gray-600 font-semibold mb-1">Tipo</label>
            <select name="tipo" class="border-gray-300 rounded text-sm px-2 py-1.5 focus:ring-blue-500 focus:border-blue-500">
                <option value="">Todos</option>
                <option value="Auto" <?php echo e(request('tipo') == 'Auto' ? 'selected' : ''); ?>>Autos</option>
                <option value="Moto" <?php echo e(request('tipo') == 'Moto' ? 'selected' : ''); ?>>Motos</option>
                <option value="Bus"  <?php echo e(request('tipo') == 'Bus' ? 'selected' : ''); ?>>Buses</option>
            </select>
        </div>

        <div class="flex flex-col min-w-[120px]">
            <label class="text-xs text-gray-600 font-semibold mb-1">Agrupación</label>
            <select name="view_mode" class="border-gray-300 rounded text-sm px-2 py-1.5 focus:ring-blue-500 focus:border-blue-500">
                <option value="auto">Automático</option>
                <option value="hourly" <?php echo e(request('view_mode') == 'hourly' ? 'selected' : ''); ?>>Por Hora (0-23h)</option>
                <option value="daily" <?php echo e(request('view_mode') == 'daily' ? 'selected' : ''); ?>>Por Día</option>
            </select>
        </div>

        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded text-sm font-medium transition-colors">
            Filtrar
        </button>
    </form>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    <div class="lg:col-span-2 bg-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-lg font-semibold text-gray-800">Vehículos Detectados por Hora</h2>
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
            <a href="<?php echo e(route('exportar.excel', request()->all())); ?>" class="px-3 py-1 text-sm text-green-600 bg-green-50 rounded border border-green-200 hover:bg-green-100 flex items-center gap-2">
                Exportar Excel
            </a>
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
                <?php $__empty_1 = true; $__currentLoopData = $registros ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $registro): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $colorClass = match(strtolower($registro['tipo'])) {
                            'auto' => 'bg-blue-500',
                            'moto' => 'bg-red-500',
                            'bus' => 'bg-green-500',
                            'camion' => 'bg-orange-500',
                            default => 'bg-gray-500'
                        };
                        $conf = floatval($registro['confianza']);
                        $confColor = $conf > 90 ? 'bg-green-100 text-green-800' : ($conf > 75 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                        
                        // Mapear colores de vehículo a colores de badge
                        $vehicleColorMap = [
                            'rojo' => 'bg-red-500',
                            'azul' => 'bg-blue-500',
                            'verde' => 'bg-green-500',
                            'amarillo' => 'bg-yellow-400',
                            'naranja' => 'bg-orange-500',
                            'negro' => 'bg-gray-800',
                            'blanco' => 'bg-gray-100 border border-gray-300',
                            'gris' => 'bg-gray-400',
                            'morado' => 'bg-purple-500',
                        ];
                        $colorBadge = $vehicleColorMap[strtolower($registro['color'] ?? 'desconocido')] ?? 'bg-gray-300';
                    ?>

                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4"><?php echo e($registro['fecha']); ?></td>
                        <td class="px-6 py-4">
                            <span class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full <?php echo e($colorClass); ?>"></span>
                                <?php echo e($registro['tipo']); ?>

                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="flex items-center gap-2">
                                <span class="w-4 h-4 rounded-full <?php echo e($colorBadge); ?>"></span>
                                <span class="capitalize"><?php echo e($registro['color'] ?? 'desconocido'); ?></span>
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="flex items-center gap-2">
                                <span class="w-4 h-4 rounded-full <?php echo e($colorBadge); ?>"></span>
                                <span class="capitalize"><?php echo e($registro['confianza'] ?? 'desconocido'); ?></span>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            No se encontraron registros en el archivo XML o base de datos.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-between items-center text-sm text-gray-500">
        <span>Total: <?php echo e(count($registros ?? [])); ?> registros</span>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Variables globales para gráficos
    let chartBar = null;
    let chartPie = null;
    const getSelectedCamera = () => {
        const params = new URLSearchParams(window.location.search);
        return params.get('camera_id') || 'CAM_002';
    };

    document.addEventListener('DOMContentLoaded', function() {
        // Obtenemos los datos pasados desde PHP (XML)
        const rawData = <?php echo json_encode($registros ?? [], 15, 512) ?>;
        processAndInitCharts(rawData);

        // Escuchar cambios en el formulario de filtros
        const filterForm = document.querySelector('form');
        if (filterForm) {
            filterForm.addEventListener('submit', function(e) {
                // El formulario se envía normalmente, recargando la página con nuevos filtros
            });
        }
    });

    function processAndInitCharts(data) {
        // --- PROCESAMIENTO DE DATOS ---
        const urlParams = new URLSearchParams(window.location.search);
        const startDate = urlParams.get('fecha_inicio');
        const endDate = urlParams.get('fecha_fin');
        const viewMode = urlParams.get('view_mode') || 'auto';
        
        // Determinar modo: 'daily' (por fecha) o 'hourly' (por horas del día)
        let isMultiDay = false;
        
        if (viewMode === 'daily') {
            isMultiDay = true;
        } else if (viewMode === 'hourly') {
            isMultiDay = false;
        } else {
            // Modo Automático
            if (startDate && endDate && startDate !== endDate) {
                isMultiDay = true;
            } else if (!startDate && !endDate && data.length > 0) {
                const uniqueDates = new Set(data.map(item => item.fecha.split(' ')[0]));
                if (uniqueDates.size > 1) isMultiDay = true;
            }
        }

        const stats = {};
        const typesCount = { 'Auto': 0, 'Moto': 0, 'Bus': 0, 'Otro': 0 };

        data.forEach(item => {
            const dateTimeParts = item.fecha.split(' ');
            if (dateTimeParts.length >= 2) {
                const datePart = dateTimeParts[0];
                const timePart = dateTimeParts[1];
                const hour = timePart.split(':')[0];
                const type = item.tipo;

                // Clave de agrupación según el modo
                const key = isMultiDay ? datePart : `${hour}:00`;

                // Inicializar objeto
                if (!stats[key]) {
                    stats[key] = { 'Auto': 0, 'Moto': 0, 'Bus': 0, 'Otro': 0 };
                }

                // Contar
                if (['Auto', 'Moto', 'Bus'].includes(type)) {
                    stats[key][type]++;
                    typesCount[type]++;
                } else {
                    stats[key]['Otro']++;
                    typesCount['Otro']++;
                }
            }
        });

        // Ordenar claves
        let sortedKeys = Object.keys(stats);
        if (isMultiDay) {
            // Ordenar fechas cronológicamente
            sortedKeys.sort((a, b) => new Date(a) - new Date(b));
        } else {
            // Ordenar horas
            sortedKeys.sort((a, b) => parseInt(a) - parseInt(b));
        }

        // Preparar arrays
        const labels = sortedKeys;
        const dataAuto = sortedKeys.map(k => stats[k]['Auto']);
        const dataMoto = sortedKeys.map(k => stats[k]['Moto']);
        const dataBus = sortedKeys.map(k => stats[k]['Bus']);

        // Actualizar título del gráfico
        const chartTitle = isMultiDay ? 'Vehículos Detectados por Día' : 'Vehículos Detectados por Hora';
        const canvasContainer = document.getElementById('dailyChart').parentElement;
        if (canvasContainer && canvasContainer.previousElementSibling) {
            canvasContainer.previousElementSibling.querySelector('h2').textContent = chartTitle;
        }

        // --- 2. CONFIGURACIÓN GRÁFICOS ---

        if (chartBar) chartBar.destroy();
        if (chartPie) chartPie.destroy();

        // A. Gráfico de Barras
        const ctxBar = document.getElementById('dailyChart').getContext('2d');
        chartBar = new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    { label: 'Autos', data: dataAuto, backgroundColor: '#3b82f6', borderRadius: 4 },
                    { label: 'Motos', data: dataMoto, backgroundColor: '#ef4444', borderRadius: 4 },
                    { label: 'Buses', data: dataBus, backgroundColor: '#10b981', borderRadius: 4 }
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

        // B. Gráfico de Torta
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

    // Función global para exportar (puede ser llamada por comandos de voz)
    window.exportToExcel = function() {
        const params = new URLSearchParams(window.location.search);
        window.location.href = '<?php echo e(route("exportar.excel")); ?>?' + params.toString();
    };
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\ProyectoFinal\polysense\resources\views/modulo2.blade.php ENDPATH**/ ?>