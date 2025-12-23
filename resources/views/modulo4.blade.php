@extends('layouts.app')

@section('title', 'Accesibilidad')

@section('content')
<div class="bg-gray-50 py-8">
    <div class="container mx-auto px-4 max-w-6xl">
        
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Comandos de Voz</h1>
            <p class="text-gray-600">Activa, desactiva y edita las palabras clave de tus comandos</p>
            
            <!-- Stats -->
            <div class="flex gap-4 mt-4">
                <div class="bg-white rounded px-4 py-3 border border-gray-300">
                    <div class="text-2xl font-bold text-gray-900" id="total-commands">0</div>
                    <div class="text-sm text-gray-600">Total</div>
                </div>
                <div class="bg-white rounded px-4 py-3 border border-gray-300">
                    <div class="text-2xl font-bold text-green-600" id="active-commands">0</div>
                    <div class="text-sm text-gray-600">Activos</div>
                </div>
            </div>
        </div>

        <!-- Comandos Grid -->
        <div id="commands-container">
            <!-- Loading State -->
            <div class="text-center py-12">
                <div class="inline-block animate-spin rounded-full h-10 w-10 border-4 border-gray-300 border-t-blue-600 mb-3"></div>
                <p class="text-gray-600">Cargando comandos...</p>
            </div>
        </div>

    </div>
</div>

<!-- Modal para Editar Palabras Clave -->
<div id="edit-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded shadow-lg max-w-2xl w-full">
        <!-- Modal Header -->
        <div class="bg-gray-100 px-6 py-4 border-b border-gray-300 flex items-center justify-between">
            <h3 class="text-xl font-bold text-gray-800">Editar Palabras Clave</h3>
            <button onclick="closeModal()" class="text-gray-600 hover:text-gray-800">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Modal Body -->
        <form id="edit-form" class="p-6">
            <input type="hidden" id="edit-command-id">
            
            <!-- Comando Info -->
            <div class="bg-gray-100 rounded p-4 border border-gray-300 mb-4">
                <h4 id="edit-name" class="text-lg font-bold text-gray-900 mb-2"></h4>
                <div class="flex items-center gap-2">
                    <span id="edit-action-badge" class="px-2 py-1 text-xs font-semibold rounded"></span>
                    <span id="edit-module-badge" class="px-2 py-1 text-xs bg-gray-300 text-gray-800 rounded"></span>
                </div>
            </div>

            <!-- Palabras Clave Input -->
            <div class="mb-4">
                <label class="block text-sm font-bold text-gray-700 mb-2">
                    Palabras Clave <span class="text-red-600">*</span>
                </label>
                <textarea id="edit-trigger" required rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:border-blue-500"
                    placeholder="inicio, home, página principal"></textarea>
                <p class="text-sm text-gray-600 mt-2">Separa las palabras con comas. Usa sinónimos para mejorar el reconocimiento.</p>
            </div>

            <!-- Información de Destino (si aplica) -->
            <div id="edit-target-info" class="hidden bg-gray-100 border border-gray-300 rounded p-3 mb-4">
                <p class="text-sm text-gray-700 font-semibold mb-2">Destino del comando:</p>
                <code id="edit-target" class="block bg-white px-3 py-2 rounded text-sm text-gray-900 border border-gray-300"></code>
            </div>

            <!-- Botones -->
            <div class="flex gap-3">
                <button type="button" onclick="closeModal()"
                    class="flex-1 px-4 py-2 bg-gray-200 border border-gray-300 rounded hover:bg-gray-300 text-gray-800">
                    Cancelar
                </button>
                <button type="submit"
                    class="flex-1 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded">
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
            <div class="text-center py-12">
                <p class="text-lg text-gray-600">No hay comandos configurados</p>
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
        <div class="bg-white rounded border border-gray-300 ${!isActive ? 'opacity-60' : ''}">
            <!-- Card Header -->
            <div class="bg-gray-50 px-4 py-3 border-b border-gray-300 flex items-center justify-between">
                <div class="flex-1">
                    <h3 class="text-lg font-bold text-gray-900">${escapeHtml(cmd.name)}</h3>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="px-2 py-0.5 ${actionConfig.badgeClass} text-xs font-semibold rounded">
                            ${actionConfig.label}
                        </span>
                        ${cmd.modules ? `
                            <span class="px-2 py-0.5 bg-gray-200 text-gray-700 text-xs rounded">
                                ${cmd.modules}
                            </span>
                        ` : ''}
                    </div>
                </div>
                
                <!-- Toggle Switch -->
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" ${isActive ? 'checked' : ''} 
                        data-command-id="${cmd.id}"
                        class="toggle-checkbox sr-only peer">
                    <div class="w-11 h-6 bg-gray-300 rounded-full peer peer-checked:bg-blue-600 peer-focus:ring-2 peer-focus:ring-blue-300 after:content-[''] after:absolute after:top-0.5 after:left-[2px] after:bg-white after:border after:border-gray-300 after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full peer-checked:after:border-white"></div>
                </label>
            </div>
            
            <!-- Card Body -->
            <div class="p-4">
                <!-- Palabras Clave -->
                <div class="mb-3">
                    <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Palabras Clave</label>
                    <div class="bg-gray-50 rounded p-2 border border-gray-200">
                        <p class="text-gray-900">"${escapeHtml(cmd.trigger)}"</p>
                    </div>
                </div>
                
                <!-- Destino (si aplica) -->
                ${cmd.target ? `
                    <div class="mb-3">
                        <label class="block text-xs font-bold text-gray-600 uppercase mb-1">Destino</label>
                        <code class="block bg-gray-50 rounded p-2 text-sm text-gray-800 border border-gray-200 break-all">${escapeHtml(cmd.target)}</code>
                    </div>
                ` : ''}
                
                <!-- Footer -->
                <div class="flex items-center justify-between pt-3 border-t border-gray-200">
                    <span class="text-sm ${isActive ? 'text-green-600' : 'text-gray-500'} font-semibold">
                        ${isActive ? 'Activo' : 'Inactivo'}
                    </span>
                    
                    <button onclick="openEditModal(${cmd.id})" 
                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded text-sm">
                        Editar
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
            label: 'Navegación',
            badgeClass: 'bg-blue-100 text-blue-800'
        },
        'alert': {
            label: 'Alerta',
            badgeClass: 'bg-green-100 text-green-800'
        },
        'function': {
            label: 'Función',
            badgeClass: 'bg-purple-100 text-purple-800'
        }
    };
    return configs[action] || {
        label: action,
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
            showNotification(enabled ? 'Comando activado' : 'Comando desactivado', 'success');
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

function closeModal() {
    document.getElementById('edit-modal').classList.add('hidden');
}


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
    notification.className = `fixed bottom-4 right-4 ${colors[type]} text-white px-4 py-3 rounded shadow-lg z-50`;
    notification.innerHTML = `<span>${message}</span>`;
    
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
            <div class="bg-red-50 rounded p-6 border border-red-300 inline-block">
                <p class="text-lg text-red-800 font-semibold mb-3">${message}</p>
                <button onclick="loadCommands()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded">
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
