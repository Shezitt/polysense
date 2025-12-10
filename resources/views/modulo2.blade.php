@extends('layouts.app')

@section('title', 'Módulo 2: Analítica y Reportes')

@section('content')
<div class="bg-white rounded-lg shadow-lg p-6 mb-6">
    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Módulo 2: Analítica y Reportes Viales</h1>
            <p class="text-gray-500 mt-1">Visualización de datos históricos y estadísticas de detección.</p>
        </div>
        
        <form action="{{ route('modulo2') }}" method="GET" class="flex items-center gap-3 bg-gray-50 p-2 rounded-lg border border-gray-200">
            
            <div class="flex flex-col">
                <label class="text-xs text-gray-500 font-semibold ml-1">Rango de Fechas</label>
                <div class="flex items-center gap-2">
                    <input type="date" name="fecha_inicio" class="border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500" value="{{ request('fecha_inicio', date('Y-m-01')) }}">
                    <span class="text-gray-400">-</span>
                    <input type="date" name="fecha_fin" class="border-gray-300 rounded text-sm focus:ring-blue-500 focus:border-blue-500" value="{{ request('fecha_fin', date('Y-m-d')) }}">
                </div>
            </div>

            <div class="h-8 w-px bg-gray-300 mx-2"></div>

            <div class="flex flex-col">
                <label class="text-xs text-gray-500 font-semibold ml-1">Tipo de Vehículo</label>
                <select name="tipo" class="border-gray-300 rounded text-sm min-w-[150px] focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Todos</option>
                    <option value="Auto" {{ request('tipo') == 'Auto' ? 'selected' : '' }}>Autos</option>
                    <option value="Moto" {{ request('tipo') == 'Moto' ? 'selected' : '' }}>Motos</option>
                    <option value="Bus"  {{ request('tipo') == 'Bus' ? 'selected' : '' }}>Buses</option>
                </select>
            </div>

            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-1.5 rounded-md text-sm font-medium transition-colors">
                Filtrar
            </button>
        </form>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
    
    <div class="lg:col-span-2 bg-white rounded-lg shadow-lg p-6">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-lg font-semibold text-gray-800">📊 Vehículos Detectados por Día (Última Semana)</h2>
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
            <h2 class="text-lg font-semibold text-gray-800">🥧 Distribución</h2>
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
        <h2 class="text-lg font-semibold text-gray-800">📋 Registro Reciente de Detecciones</h2>
        <div class="flex gap-2">
            <a href="{{ route('exportar.excel') }}" class="px-3 py-1 text-sm text-green-600 bg-green-50 rounded border border-green-200 hover:bg-green-100 flex items-center gap-2">
                <span>📄</span> Exportar Excel
            </a>
            <button onclick="window.print()" class="px-3 py-1 text-sm text-red-600 bg-red-50 rounded border border-red-200 hover:bg-red-100 flex items-center gap-2">
                <span>🖨️</span> PDF / Imprimir
            </button>
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-50 text-gray-600 text-sm uppercase tracking-wider">
                    <th class="px-6 py-4 font-medium">Fecha/Hora</th>
                    <th class="px-6 py-4 font-medium">Tipo de Vehículo</th>
                    <th class="px-6 py-4 font-medium">Cámara</th>
                    <th class="px-6 py-4 font-medium text-center">Confianza IA</th>
                    <th class="px-6 py-4 font-medium text-right">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-sm text-gray-700" id="reportsTableBody">
                {{-- BUCLE DINÁMICO: Itera sobre la variable $registros que enviará el controlador --}}
                @forelse($registros ?? [] as $registro)
                    @php
                        // Lógica simple para color del punto según tipo
                        $colorClass = match(strtolower($registro['tipo'])) {
                            'auto' => 'bg-blue-500',
                            'moto' => 'bg-red-500',
                            'bus' => 'bg-green-500',
                            default => 'bg-gray-500'
                        };
                        
                        // Lógica para color de confianza
                        $conf = floatval($registro['confianza']);
                        $confColor = $conf > 90 ? 'bg-green-100 text-green-800' : ($conf > 75 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
                    @endphp

                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">{{ $registro['fecha'] }}</td>
                        <td class="px-6 py-4">
                            <span class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full {{ $colorClass }}"></span>
                                {{ $registro['tipo'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4">{{ $registro['camara'] }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="{{ $confColor }} px-2 py-1 rounded text-xs font-bold">
                                {{ number_format($conf, 2) }}%
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right text-gray-400">Archivado</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            No se encontraron registros en el archivo XML o base de datos.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-between items-center text-sm text-gray-500">
        <span>Mostrando resultados recientes</span>
        <div class="flex gap-1">
            <button class="px-3 py-1 border rounded hover:bg-white disabled:opacity-50" disabled>Ant.</button>
            <button class="px-3 py-1 border rounded hover:bg-white bg-blue-50 text-blue-600 border-blue-200">1</button>
            <button class="px-3 py-1 border rounded hover:bg-white">Sig.</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        initCharts();
    });

    function initCharts() {
        // NOTA: Estos datos son estáticos por ahora para cumplir con la visualización.
        // Para hacerlos dinámicos, necesitarías pasar los arrays desde el Controller a JavaScript

        // --- 1. Gráfico de Barras ---
        const ctxBar = document.getElementById('dailyChart').getContext('2d');
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'],
                datasets: [
                    {
                        label: 'Autos',
                        data: [150, 210, 180, 240, 200, 150, 190],
                        backgroundColor: '#3b82f6',
                        borderRadius: 4,
                    },
                    {
                        label: 'Motos',
                        data: [80, 95, 70, 85, 60, 100, 50],
                        backgroundColor: '#ef4444',
                        borderRadius: 4,
                    },
                    {
                        label: 'Buses',
                        data: [40, 30, 45, 20, 35, 25, 15],
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
                    y: { beginAtZero: true, grid: { borderDash: [2, 2], drawBorder: false } },
                    x: { grid: { display: false } }
                }
            }
        });

        // --- 2. Gráfico de Torta ---
        const ctxPie = document.getElementById('typeDistributionChart').getContext('2d');
        new Chart(ctxPie, {
            type: 'pie',
            data: {
                labels: ['Autos', 'Motos', 'Buses', 'Otros'],
                datasets: [{
                    data: [36, 23.5, 7, 33.5],
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
</script>
@endpush