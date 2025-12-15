@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h2 class="text-2xl font-bold mb-6">Reportes</h2>

                <!-- Formulario para generar reporte -->
                <div class="mb-8 p-6 bg-gray-50 rounded-lg">
                    <h3 class="text-lg font-semibold mb-4">Generar Nuevo Reporte</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <select id="reportType" class="border rounded px-3 py-2">
                            <option value="daily">Diario</option>
                            <option value="weekly">Semanal</option>
                            <option value="monthly">Mensual</option>
                            <option value="custom">Personalizado</option>
                        </select>
                        <input type="date" id="reportDateFrom" class="border rounded px-3 py-2" placeholder="Desde">
                        <input type="date" id="reportDateTo" class="border rounded px-3 py-2" placeholder="Hasta">
                        <button onclick="generateReport()" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                            Generar Reporte
                        </button>
                    </div>
                </div>

                <!-- Lista de reportes -->
                <div id="reportsList" class="space-y-4">
                    <!-- Se cargará con JavaScript -->
                </div>

                <!-- Paginación -->
                <div id="pagination" class="mt-6"></div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para ver reporte -->
<div id="reportModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold" id="modalTitle"></h3>
            <button onclick="closeModal()" class="text-gray-600 hover:text-gray-900 text-2xl">&times;</button>
        </div>
        <div id="modalContent" class="mt-4"></div>
    </div>
</div>

<script>
let currentPage = 1;

function loadReports(page = 1) {
    fetch(`/api/reports?page=${page}`)
        .then(res => res.json())
        .then(data => {
            renderReports(data.data);
            renderPagination(data);
        });
}

function renderReports(reports) {
    const container = document.getElementById('reportsList');
    
    if (reports.length === 0) {
        container.innerHTML = '<p class="text-gray-500">No hay reportes generados</p>';
        return;
    }
    
    container.innerHTML = reports.map(r => `
        <div class="p-4 bg-gray-50 rounded-lg border">
            <div class="flex justify-between items-start">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <h4 class="font-semibold">${r.title}</h4>
                        <span class="px-2 py-1 text-xs rounded ${getStatusBadge(r.status)}">${r.status.toUpperCase()}</span>
                        <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800">${r.type.toUpperCase()}</span>
                    </div>
                    <p class="text-sm text-gray-600">${r.description || ''}</p>
                    <p class="text-xs text-gray-400 mt-1">Generado: ${new Date(r.generated_at).toLocaleString()}</p>
                    <p class="text-xs text-gray-500 mt-1">Total detecciones: ${r.data.total_detections || 0}</p>
                </div>
                <div class="flex gap-2 ml-4">
                    <button onclick="viewReport(${r.id})" class="bg-blue-500 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                        Ver
                    </button>
                    <button onclick="deleteReport(${r.id})" class="bg-red-500 hover:bg-red-700 text-white px-3 py-1 rounded text-sm">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

function getStatusBadge(status) {
    const badges = {
        'pending': 'bg-yellow-100 text-yellow-800',
        'generated': 'bg-green-100 text-green-800',
        'viewed': 'bg-gray-100 text-gray-800'
    };
    return badges[status] || 'bg-gray-100 text-gray-800';
}

function renderPagination(data) {
    const container = document.getElementById('pagination');
    const pages = [];
    
    for (let i = 1; i <= data.last_page; i++) {
        pages.push(`
            <button onclick="loadReports(${i})" 
                class="px-3 py-1 ${i === data.current_page ? 'bg-blue-500 text-white' : 'bg-gray-200'} rounded">
                ${i}
            </button>
        `);
    }
    
    container.innerHTML = `<div class="flex gap-2 justify-center">${pages.join('')}</div>`;
}

function generateReport() {
    const type = document.getElementById('reportType').value;
    const dateFrom = document.getElementById('reportDateFrom').value;
    const dateTo = document.getElementById('reportDateTo').value;
    
    const filters = {};
    if (dateFrom) filters.date_from = dateFrom;
    if (dateTo) filters.date_to = dateTo;
    
    fetch('/api/reports/generate', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ type, filters })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Reporte generado exitosamente');
            loadReports(1);
        }
    });
}

function viewReport(id) {
    fetch(`/api/reports/${id}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showReportModal(data.report);
            }
        });
}

function showReportModal(report) {
    document.getElementById('modalTitle').textContent = report.title;
    
    const stats = report.data;
    let content = `
        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 bg-blue-50 rounded">
                    <p class="text-sm text-gray-600">Total Detecciones</p>
                    <p class="text-2xl font-bold">${stats.total_detections || 0}</p>
                </div>
                <div class="p-4 bg-green-50 rounded">
                    <p class="text-sm text-gray-600">Confianza Promedio</p>
                    <p class="text-2xl font-bold">${stats.average_confidence || 0}%</p>
                </div>
            </div>
            
            <div>
                <h4 class="font-semibold mb-2">Detecciones por Tipo</h4>
                <div class="space-y-1">
                    ${Object.entries(stats.by_type || {}).map(([type, count]) => `
                        <div class="flex justify-between p-2 bg-gray-50 rounded">
                            <span>${type}</span>
                            <span class="font-semibold">${count}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
            
            ${stats.peak_hour ? `<p class="text-sm"><strong>Hora pico:</strong> ${stats.peak_hour}:00</p>` : ''}
            ${stats.busiest_day ? `<p class="text-sm"><strong>Día más ocupado:</strong> ${stats.busiest_day}</p>` : ''}
        </div>
    `;
    
    document.getElementById('modalContent').innerHTML = content;
    document.getElementById('reportModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('reportModal').classList.add('hidden');
}

function deleteReport(id) {
    if (confirm('¿Eliminar este reporte?')) {
        fetch(`/api/reports/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        })
        .then(() => loadReports(currentPage));
    }
}

// Cargar al inicio
loadReports();
</script>
@endsection
