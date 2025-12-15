@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold">Notificaciones</h2>
                    <button onclick="markAllAsRead()" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Marcar todas como leídas
                    </button>
                </div>

                <!-- Filtros -->
                <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
                    <select id="filterType" class="border rounded px-3 py-2">
                        <option value="">Todos los tipos</option>
                        <option value="info">Info</option>
                        <option value="warning">Advertencia</option>
                        <option value="alert">Alerta</option>
                        <option value="success">Éxito</option>
                    </select>
                    <select id="filterRead" class="border rounded px-3 py-2">
                        <option value="">Todas</option>
                        <option value="false">No leídas</option>
                        <option value="true">Leídas</option>
                    </select>
                    <input type="date" id="filterDateFrom" class="border rounded px-3 py-2" placeholder="Desde">
                    <button onclick="applyFilters()" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        Aplicar Filtros
                    </button>
                </div>

                <!-- Lista de notificaciones -->
                <div id="notificationsList" class="space-y-3">
                    <!-- Se cargará con JavaScript -->
                </div>

                <!-- Paginación -->
                <div id="pagination" class="mt-6"></div>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;

function loadNotifications(page = 1) {
    const type = document.getElementById('filterType').value;
    const isRead = document.getElementById('filterRead').value;
    const dateFrom = document.getElementById('filterDateFrom').value;
    
    let url = `/api/notifications?page=${page}`;
    if (type) url += `&type=${type}`;
    if (isRead) url += `&is_read=${isRead}`;
    if (dateFrom) url += `&date_from=${dateFrom}`;
    
    fetch(url)
        .then(res => res.json())
        .then(data => {
            renderNotifications(data.data);
            renderPagination(data);
        });
}

function renderNotifications(notifications) {
    const container = document.getElementById('notificationsList');
    
    if (notifications.length === 0) {
        container.innerHTML = '<p class="text-gray-500">No hay notificaciones</p>';
        return;
    }
    
    container.innerHTML = notifications.map(n => `
        <div class="flex items-start p-4 bg-gray-50 rounded-lg border-l-4 ${getBorderColor(n.type)} ${n.is_read ? 'opacity-60' : ''}">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-1">
                    <span class="px-2 py-1 text-xs rounded ${getTypeBadge(n.type)}">${n.type.toUpperCase()}</span>
                    <span class="px-2 py-1 text-xs rounded ${getPriorityBadge(n.priority)}">${n.priority.toUpperCase()}</span>
                    ${!n.is_read ? '<span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800">NUEVA</span>' : ''}
                </div>
                <p class="font-semibold">${n.title}</p>
                <p class="text-sm text-gray-600">${n.message}</p>
                <p class="text-xs text-gray-400 mt-1">${new Date(n.created_at).toLocaleString()}</p>
            </div>
            <div class="flex gap-2 ml-4">
                ${!n.is_read ? `<button onclick="markAsRead(${n.id})" class="text-blue-600 hover:text-blue-800">✓</button>` : ''}
                <button onclick="deleteNotification(${n.id})" class="text-red-600 hover:text-red-800">✕</button>
            </div>
        </div>
    `).join('');
}

function getBorderColor(type) {
    const colors = {
        'alert': 'border-red-500',
        'warning': 'border-yellow-500',
        'success': 'border-green-500',
        'info': 'border-blue-500'
    };
    return colors[type] || 'border-gray-500';
}

function getTypeBadge(type) {
    const badges = {
        'alert': 'bg-red-100 text-red-800',
        'warning': 'bg-yellow-100 text-yellow-800',
        'success': 'bg-green-100 text-green-800',
        'info': 'bg-blue-100 text-blue-800'
    };
    return badges[type] || 'bg-gray-100 text-gray-800';
}

function getPriorityBadge(priority) {
    const badges = {
        'high': 'bg-red-100 text-red-800',
        'medium': 'bg-yellow-100 text-yellow-800',
        'low': 'bg-gray-100 text-gray-800'
    };
    return badges[priority] || 'bg-gray-100 text-gray-800';
}

function renderPagination(data) {
    const container = document.getElementById('pagination');
    const pages = [];
    
    for (let i = 1; i <= data.last_page; i++) {
        pages.push(`
            <button onclick="loadNotifications(${i})" 
                class="px-3 py-1 ${i === data.current_page ? 'bg-blue-500 text-white' : 'bg-gray-200'} rounded">
                ${i}
            </button>
        `);
    }
    
    container.innerHTML = `<div class="flex gap-2 justify-center">${pages.join('')}</div>`;
}

function markAsRead(id) {
    fetch(`/api/notifications/${id}/read`, { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
        .then(() => loadNotifications(currentPage));
}

function markAllAsRead() {
    fetch('/api/notifications/read-all', { method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
        .then(() => loadNotifications(currentPage));
}

function deleteNotification(id) {
    if (confirm('¿Eliminar esta notificación?')) {
        fetch(`/api/notifications/${id}`, { method: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' } })
            .then(() => loadNotifications(currentPage));
    }
}

function applyFilters() {
    loadNotifications(1);
}

// Cargar al inicio
loadNotifications();

// Actualizar cada 30 segundos
setInterval(() => loadNotifications(currentPage), 30000);
</script>
@endsection
