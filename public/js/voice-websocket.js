/**
 * Cliente WebSocket para reconocimiento de voz local (Vosk)
 * Se conecta al servidor Python que procesa el audio
 */

class VoiceWebSocketClient {
    constructor(serverUrl = 'http://localhost:5001') {
        this.serverUrl = serverUrl;
        this.socket = null;
        this.isConnected = false;
        this.commands = [];
        this.onCommandCallback = null;
        this.onPartialCallback = null;
        this.onStatusCallback = null;
    }

    /**
     * Conectar al servidor de reconocimiento
     */
    async connect() {
        return new Promise((resolve, reject) => {
            try {
                // Cargar Socket.IO desde CDN si no está disponible
                if (typeof io === 'undefined') {
                    const script = document.createElement('script');
                    script.src = 'https://cdn.socket.io/4.5.4/socket.io.min.js';
                    script.onload = () => this.initializeSocket(resolve, reject);
                    script.onerror = () => reject(new Error('No se pudo cargar Socket.IO'));
                    document.head.appendChild(script);
                } else {
                    this.initializeSocket(resolve, reject);
                }
            } catch (error) {
                reject(error);
            }
        });
    }

    initializeSocket(resolve, reject) {
        try {
            this.socket = io(this.serverUrl, {
                reconnection: true,
                reconnectionAttempts: 5,
                reconnectionDelay: 1000
            });

            this.socket.on('connect', () => {
                this.isConnected = true;
                this.updateStatus('connected');
                resolve();
            });

            this.socket.on('disconnect', () => {
                this.isConnected = false;
                this.updateStatus('disconnected');
            });

            this.socket.on('connect_error', (error) => {
                this.updateStatus('error', error.message);
                reject(error);
            });

            this.socket.on('voice_command', (data) => {
                this.processCommand(data.text, data.confidence);
            });

            this.socket.on('voice_partial', (data) => {
                if (this.onPartialCallback) {
                    this.onPartialCallback(data.text);
                }
            });

        } catch (error) {
            reject(error);
        }
    }

    /**
     * Cargar comandos disponibles desde la API
     */
    async loadCommands() {
        try {
            const response = await fetch('/api/voice-commands');
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            this.commands = Array.isArray(data) ? data : (data.commands || []);
            
            return this.commands;
        } catch (error) {
            console.error('Error cargando comandos de voz:', error);
            return [];
        }
    }

    /**
     * Procesar comando reconocido
     */
    processCommand(text, confidence = 1.0) {
        const textLower = text.toLowerCase().trim();
        
        // Buscar coincidencia en comandos configurados
        for (const command of this.commands) {
            if (!command.enabled) continue;
            
            const triggers = command.trigger.split(',').map(t => t.trim().toLowerCase());
            
            if (triggers.some(trigger => textLower.includes(trigger))) {
                this.executeCommand(command);
                
                if (this.onCommandCallback) {
                    this.onCommandCallback(command, text, confidence);
                }
                return;
            }
        }
    }

    /**
     * Ejecutar acción del comando
     */
    executeCommand(command) {
        switch (command.action) {
            case 'navigate':
                window.location.href = command.target;
                break;
            case 'export':
                this.triggerExport();
                break;
            case 'toggle':
                this.toggleElement(command.target);
                break;
            case 'custom':
                this.executeCustomFunction(command.function_name);
                break;
        }
    }

    /**
     * Exportar datos a Excel
     */
    triggerExport() {
        const exportBtn = document.getElementById('exportBtn');
        if (exportBtn) {
            exportBtn.click();
        } else if (typeof window.exportToExcel === 'function') {
            window.exportToExcel();
        }
    }

    /**
     * Toggle de elemento
     */
    toggleElement(selector) {
        const element = document.querySelector(selector);
        if (element) {
            element.classList.toggle('hidden');
        }
    }

    /**
     * Ejecutar función personalizada
     */
    executeCustomFunction(functionName) {
        if (typeof window[functionName] === 'function') {
            window[functionName]();
        }
    }

    /**
     * Actualizar estado de la conexión
     */
    updateStatus(status, message = '') {
        if (this.onStatusCallback) {
            this.onStatusCallback(status, message);
        }
    }

    /**
     * Callbacks
     */
    onCommand(callback) {
        this.onCommandCallback = callback;
    }

    onPartial(callback) {
        this.onPartialCallback = callback;
    }

    onStatus(callback) {
        this.onStatusCallback = callback;
    }

    /**
     * Desconectar
     */
    disconnect() {
        if (this.socket) {
            this.socket.disconnect();
            this.isConnected = false;
        }
    }
}

// Inicialización global
let voiceClient = null;

document.addEventListener('DOMContentLoaded', async () => {
    voiceClient = new VoiceWebSocketClient('http://localhost:5001');
    
    try {
        await voiceClient.connect();
        await voiceClient.loadCommands();
        
        // Mostrar indicador visual
        const indicator = document.getElementById('voiceIndicator');
        if (indicator) {
            indicator.classList.remove('hidden');
        }
        
        // Callbacks opcionales
        voiceClient.onPartial((text) => {
            const partialDiv = document.getElementById('voicePartial');
            if (partialDiv) {
                partialDiv.textContent = text;
                partialDiv.classList.remove('hidden');
            }
        });
        
        voiceClient.onCommand((command, text, confidence) => {
            showVoiceNotification(command.name, text);
        });
        
    } catch (error) {
        console.warn('Los comandos de voz no están disponibles. Asegúrate de que voice_server.py esté corriendo.');
    }
});

/**
 * Mostrar notificación de comando reconocido
 */
function showVoiceNotification(commandName, recognizedText) {
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 animate-fade-in-out';
    notification.innerHTML = `
        <div class="flex items-center space-x-2">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
            </svg>
            <div>
                <div class="font-bold">${commandName}</div>
                <div class="text-xs opacity-90">"${recognizedText}"</div>
            </div>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}
