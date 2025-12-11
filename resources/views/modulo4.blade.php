@extends('layouts.app')

@section('title', 'Módulo 4 - Comandos de Voz')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4 max-w-7xl">
        
        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div>
                    <div class="flex items-center gap-4 mb-2">
                        <div class="w-14 h-14 bg-blue-600 rounded-xl flex items-center justify-center shadow-md">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                            </svg>
                        </div>
                        <h1 class="text-4xl font-bold text-gray-900">Comandos de Voz</h1>
                    </div>
                    <p class="text-gray-600 ml-18">Activa, desactiva y edita las palabras clave de tus comandos</p>
                </div>
                
                <!-- Stats -->
                <div class="flex gap-4">
                    <div class="bg-white rounded-lg px-6 py-4 shadow border border-gray-200">
                        <div class="text-3xl font-bold text-gray-900" id="total-commands">0</div>
                        <div class="text-sm text-gray-500 font-medium">Total</div>
                    </div>
                    <div class="bg-white rounded-lg px-6 py-4 shadow border border-gray-200">
                        <div class="text-3xl font-bold text-green-600" id="active-commands">0</div>
                        <div class="text-sm text-gray-500 font-medium">Activos</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comandos Grid -->
        <div id="commands-container">
            <!-- Loading State -->
            <div class="flex items-center justify-center py-20">
                <div class="text-center">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-4 border-gray-300 border-t-blue-600 mb-4"></div>
                    <p class="text-gray-600 text-lg font-medium">Cargando comandos...</p>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Modal para Editar Palabras Clave -->
<div id="edit-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full">
        <!-- Modal Header -->
        <div class="bg-blue-600 px-6 py-4 rounded-t-lg">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-white">Editar Palabras Clave</h3>
                    <p class="text-blue-100 text-sm mt-1">Solo puedes modificar las palabras que activan el comando</p>
                </div>
                <button onclick="closeModal()" class="text-white hover:bg-blue-700 rounded-lg p-2 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Modal Body -->
        <form id="edit-form" class="p-6 space-y-5">
            <input type="hidden" id="edit-command-id">
            
            <!-- Comando Info -->
            <div class="bg-gray-100 rounded-lg p-4 border border-gray-300">
                <div class="flex items-center gap-3">
                    <span id="edit-icon" class="text-3xl"></span>
                    <div class="flex-1">
                        <h4 id="edit-name" class="text-lg font-bold text-gray-900"></h4>
                        <div class="flex items-center gap-2 mt-1">
                            <span id="edit-action-badge" class="px-2 py-1 text-xs font-semibold rounded"></span>
                            <span id="edit-module-badge" class="px-2 py-1 text-xs font-medium bg-gray-300 text-gray-800 rounded"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Palabras Clave Input -->
            <div>
                <label class="block text-sm font-bold text-gray-800 mb-2">
                    Palabras Clave <span class="text-red-600">*</span>
                </label>
                <textarea id="edit-trigger" required rows="3"
                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all resize-none text-base"
                    placeholder="inicio, home, página principal"></textarea>
                <div class="mt-2 bg-blue-50 border border-blue-200 rounded-lg p-3">
                    <p class="text-sm text-gray-800 font-medium mb-1">💡 Consejos:</p>
                    <ul class="text-sm text-gray-700 space-y-1 ml-4 list-disc">
                        <li>Separa las palabras con comas</li>
                        <li>Usa sinónimos para mejorar el reconocimiento</li>
                        <li>Evita palabras muy cortas</li>
                    </ul>
                </div>
            </div>

            <!-- Información de Destino (si aplica) -->
            <div id="edit-target-info" class="hidden bg-gray-100 border border-gray-300 rounded-lg p-3">
                <p class="text-sm text-gray-700 font-semibold mb-2">Destino del comando:</p>
                <code id="edit-target" class="block bg-white px-3 py-2 rounded text-sm text-gray-900 font-mono border border-gray-300"></code>
            </div>

            <!-- Botones -->
            <div class="flex gap-3 pt-3">
                <button type="button" onclick="closeModal()"
                    class="flex-1 px-5 py-3 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 font-semibold text-gray-800 transition-colors">
                    Cancelar
                </button>
                <button type="submit"
                    class="flex-1 px-5 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-semibold transition-colors shadow">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let allCommands = [];

// Cargar comandos
async function loadCommands() {
    try {
        const response = await fetch('/api/voice-commands');
        const data = await response.json();
        allCommands = Array.isArray(data) ? data : (data.commands || []);
        renderCommands();
        updateStats();
    } catch (error) {
        showError('No se pudieron cargar los comandos');
    }
}

// Renderizar comandos
function renderCommands() {
    const container = document.getElementById('commands-container');
    
    if (allCommands.length === 0) {
        container.innerHTML = `
            <div class="text-center py-20">
                <svg class="w-20 h-20 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z"/>
                </svg>
                <p class="text-lg text-gray-600 font-medium">No hay comandos configurados</p>
            </div>
        `;
        return;
    }

    const html = `
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            ${allCommands.map(cmd => createCommandCard(cmd)).join('')}
        </div>
    `;
    container.innerHTML = html;
    
    // Attachear event listeners después de renderizar
    attachToggleListeners();
}

// Crear tarjeta de comando
function createCommandCard(cmd) {
    const actionConfig = getActionConfig(cmd.action);
    const isActive = cmd.enabled;
    
    return `
        <div class="bg-white rounded-lg shadow hover:shadow-lg transition-all border ${isActive ? 'border-blue-200' : 'border-gray-300'} ${!isActive ? 'opacity-75' : ''}">
            <!-- Card Header -->
            <div class="${actionConfig.bgClass} px-5 py-4 border-b ${actionConfig.borderClass}">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3 flex-1">
                        <span class="text-3xl">${actionConfig.icon}</span>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-900">${escapeHtml(cmd.name)}</h3>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="px-2 py-0.5 ${actionConfig.badgeClass} text-xs font-semibold rounded">
                                    ${actionConfig.label}
                                </span>
                                ${cmd.modules ? `
                                    <span class="px-2 py-0.5 bg-gray-200 text-gray-700 text-xs font-medium rounded">
                                        ${cmd.modules}
                                    </span>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                    
                    <!-- Toggle Switch -->
                    <div class="flex-shrink-0">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" ${isActive ? 'checked' : ''} 
                                data-command-id="${cmd.id}"
                                class="toggle-checkbox sr-only peer">
                            <div class="w-14 h-7 bg-gray-300 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-blue-400 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Card Body -->
            <div class="p-5 space-y-4">
                <!-- Palabras Clave -->
                <div>
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Palabras Clave</label>
                    <div class="bg-gray-50 rounded p-3 border border-gray-200">
                        <p class="text-gray-900 font-medium">"${escapeHtml(cmd.trigger)}"</p>
                    </div>
                </div>
                
                <!-- Destino (si aplica) -->
                ${cmd.target ? `
                    <div>
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-2">Destino</label>
                        <code class="block bg-gray-50 rounded p-3 text-sm text-gray-800 font-mono border border-gray-200 break-all">${escapeHtml(cmd.target)}</code>
                    </div>
                ` : ''}
                
                <!-- Footer -->
                <div class="flex items-center justify-between pt-3 border-t border-gray-200">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full ${isActive ? 'bg-green-500' : 'bg-gray-400'}"></span>
                        <span class="text-sm font-semibold ${isActive ? 'text-green-700' : 'text-gray-500'}">
                            ${isActive ? 'Activo' : 'Inactivo'}
                        </span>
                    </div>
                    
                    <button onclick="openEditModal(${cmd.id})" 
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-semibold text-sm">
                        Editar Palabras
                    </button>
                </div>
            </div>
        </div>
    `;
}

// Configuración de acciones
function getActionConfig(action) {
    const configs = {
        'navigate': {
            icon: '🔗',
            label: 'Navegación',
            bgClass: 'bg-blue-50',
            borderClass: 'border-blue-200',
            badgeClass: 'bg-blue-100 text-blue-800'
        },
        'alert': {
            icon: '💬',
            label: 'Alerta',
            bgClass: 'bg-green-50',
            borderClass: 'border-green-200',
            badgeClass: 'bg-green-100 text-green-800'
        },
        'function': {
            icon: '⚙️',
            label: 'Función',
            bgClass: 'bg-purple-50',
            borderClass: 'border-purple-200',
            badgeClass: 'bg-purple-100 text-purple-800'
        }
    };
    return configs[action] || {
        icon: '📌',
        label: action,
        bgClass: 'bg-gray-50',
        borderClass: 'border-gray-200',
        badgeClass: 'bg-gray-100 text-gray-800'
    };
}

// Toggle comando
async function toggleCommand(id, enabled) {
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        const response = await fetch(`/api/voice-commands/${id}/toggle`, {
            method: 'POST',
            headers: { 
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ enabled })
        });
        
        if (response.ok) {
            const cmdIndex = allCommands.findIndex(c => c.id === id);
            if (cmdIndex !== -1) {
                allCommands[cmdIndex].enabled = enabled;
            }
            renderCommands();
            updateStats();
            showNotification(enabled ? '✓ Comando activado' : '✓ Comando desactivado', 'success');
        } else {
            throw new Error('Error al cambiar estado');
        }
    } catch (error) {
        await loadCommands();
        showNotification('Error al cambiar el estado', 'error');
    }
}

// Abrir modal de edición
function openEditModal(id) {
    const cmd = allCommands.find(c => c.id === id);
    if (!cmd) return;
    
    const actionConfig = getActionConfig(cmd.action);
    
    document.getElementById('edit-command-id').value = cmd.id;
    document.getElementById('edit-name').textContent = cmd.name;
    document.getElementById('edit-icon').textContent = actionConfig.icon;
    document.getElementById('edit-trigger').value = cmd.trigger;
    
    const actionBadge = document.getElementById('edit-action-badge');
    actionBadge.textContent = actionConfig.label;
    actionBadge.className = `px-2 py-1 text-xs font-semibold rounded ${actionConfig.badgeClass}`;
    
    const moduleBadge = document.getElementById('edit-module-badge');
    if (cmd.modules) {
        moduleBadge.textContent = cmd.modules;
        moduleBadge.classList.remove('hidden');
    } else {
        moduleBadge.classList.add('hidden');
    }
    
    const targetInfo = document.getElementById('edit-target-info');
    if (cmd.target) {
        document.getElementById('edit-target').textContent = cmd.target;
        targetInfo.classList.remove('hidden');
    } else {
        targetInfo.classList.add('hidden');
    }
    
    document.getElementById('edit-modal').classList.remove('hidden');
    setTimeout(() => {
        document.getElementById('edit-trigger').focus();
    }, 100);
}

// Cerrar modal
function closeModal() {
    document.getElementById('edit-modal').classList.add('hidden');
}

// Guardar cambios
document.getElementById('edit-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const id = document.getElementById('edit-command-id').value;
    const cmd = allCommands.find(c => c.id === parseInt(id));
    
    if (!cmd) {
        showNotification('Error: Comando no encontrado', 'error');
        return;
    }
    
    const newTrigger = document.getElementById('edit-trigger').value.trim();
    
    if (!newTrigger) {
        showNotification('Las palabras clave no pueden estar vacías', 'error');
        return;
    }
    
    const data = {
        name: cmd.name,
        trigger: newTrigger,
        action: cmd.action,
        target: cmd.target,
        function_name: cmd.function_name,
        modules: cmd.modules,
        enabled: cmd.enabled
    };
    
    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        const response = await fetch(`/api/voice-commands/${id}`, {
            method: 'PUT',
            headers: { 
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken || '',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });
        
        if (response.ok) {
            closeModal();
            await loadCommands();
            showNotification('Palabras clave actualizadas correctamente', 'success');
        } else {
            throw new Error('Error al guardar');
        }
    } catch (error) {
        showNotification('Error al guardar los cambios', 'error');
    }
});

// Actualizar estadísticas
function updateStats() {
    document.getElementById('total-commands').textContent = allCommands.length;
    document.getElementById('active-commands').textContent = allCommands.filter(c => c.enabled).length;
}

// Mostrar notificación
function showNotification(message, type = 'success') {
    const colors = {
        success: 'bg-green-600',
        error: 'bg-red-600',
        info: 'bg-blue-600'
    };
    
    const notification = document.createElement('div');
    notification.className = `fixed bottom-6 right-6 ${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg z-50 flex items-center gap-3`;
    notification.innerHTML = `
        <span class="font-semibold">${message}</span>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Mostrar error
function showError(message) {
    const container = document.getElementById('commands-container');
    container.innerHTML = `
        <div class="text-center py-20">
            <div class="inline-flex flex-col items-center bg-red-50 rounded-lg p-8 border border-red-300">
                <svg class="w-16 h-16 text-red-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-lg text-red-800 font-semibold mb-3">${message}</p>
                <button onclick="loadCommands()" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-semibold transition-colors">
                    Reintentar
                </button>
            </div>
        </div>
    `;
}

// Escapar HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Attachear event listeners a los toggles
function attachToggleListeners() {
    document.querySelectorAll('.toggle-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', function(e) {
            const commandId = parseInt(this.getAttribute('data-command-id'));
            const isChecked = this.checked;
            toggleCommand(commandId, isChecked);
        });
    });
}

// Cerrar modal con ESC
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeModal();
    }
});

// Inicializar
loadCommands();
</script>
@endsection
