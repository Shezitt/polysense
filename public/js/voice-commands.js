/**
 * Sistema de Comandos de Voz para PolySense - VERSIÓN SIMPLIFICADA
 * Utiliza Web Speech API para reconocimiento de voz
 */

class VoiceCommandSystem {
    constructor() {
        this.recognition = null;
        this.isListening = false;
        this.commands = [];
        this.currentModule = this.getCurrentModule();
        
        this.init();
    }

    init() {
        console.log('🎤 Inicializando sistema de voz...');
        
        // Verificar soporte del navegador
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        
        if (!SpeechRecognition) {
            console.error('❌ Web Speech API no soportada');
            this.showNotification('Tu navegador no soporta reconocimiento de voz. Usa Chrome o Edge.', 'error');
            return;
        }

        // Crear reconocimiento
        this.recognition = new SpeechRecognition();
        
        // Configuración SIMPLE
        this.recognition.lang = 'es-ES';
        this.recognition.continuous = false;
        this.recognition.interimResults = false;
        
        console.log('✅ Reconocimiento creado');

        // Event listeners SIMPLES
        this.recognition.onstart = () => {
            console.log('🎤 Micrófono activado - Habla ahora...');
            this.isListening = true;
            this.showListeningIndicator();
        };

        this.recognition.onend = () => {
            console.log('🎤 Micrófono desactivado');
            this.isListening = false;
            this.hideListeningIndicator();
        };

        this.recognition.onerror = (event) => {
            console.error('❌ ERROR:', event.error);
            this.hideListeningIndicator();
            
            const errorMessages = {
                'network': '🌐 Error de red. Prueba en modo incógnito (Ctrl+Shift+N) o desactiva extensiones.',
                'not-allowed': '🔒 Permiso denegado. Click en el candado junto a la URL y permite el micrófono.',
                'no-speech': '🔇 No se detectó voz. Habla más cerca del micrófono.',
                'audio-capture': '🎤 No se encontró micrófono.',
                'aborted': 'Cancelado'
            };
            
            const message = errorMessages[event.error] || `Error: ${event.error}`;
            this.showNotification(message, 'error');
        };

        this.recognition.onresult = (event) => {
            const transcript = event.results[0][0].transcript;
            const confidence = event.results[0][0].confidence;
            
            console.log('✅ ESCUCHÉ:', transcript);
            console.log('📊 Confianza:', (confidence * 100).toFixed(1) + '%');
            
            // Mostrar lo que se escuchó
            this.showNotification(`Escuché: "${transcript}"`, 'success');
            
            // Procesar comando
            this.processCommand(transcript.toLowerCase().trim());
        };

        // Cargar comandos desde el servidor
        this.loadCommands();

        console.log('✅ Sistema de comandos de voz inicializado');
    }

    getCurrentModule() {
        const path = window.location.pathname;
        if (path.includes('modulo1')) return 'modulo1';
        if (path.includes('modulo2')) return 'modulo2';
        if (path.includes('modulo3')) return 'modulo3';
        return 'all';
    }

    async loadCommands() {
        try {
            const response = await fetch('/api/voice-commands');
            this.commands = await response.json();
            
            // Filtrar comandos activos para el módulo actual
            this.commands = this.commands.filter(cmd => 
                cmd.enabled && (cmd.modules === 'all' || cmd.modules.includes(this.currentModule))
            );
            
            console.log(`✅ Cargados ${this.commands.length} comandos para ${this.currentModule}`);
        } catch (error) {
            console.error('❌ Error cargando comandos:', error);
        }
    }

    startListening(callback = null) {
        if (!this.recognition) {
            console.error('❌ Sistema no inicializado');
            this.showNotification('Sistema de voz no disponible', 'error');
            return;
        }

        if (this.isListening) {
            console.log('⏹️ Deteniendo...');
            this.recognition.stop();
            return;
        }

        try {
            console.log('🎤 Iniciando micrófono...');
            this.recognition.start();
        } catch (error) {
            console.error('❌ Error:', error.message);
            this.showNotification('Error: ' + error.message, 'error');
        }
    }

    stopListening() {
        if (this.recognition && this.isListening) {
            this.recognition.stop();
        }
    }

    processCommand(transcript) {
        console.log('🔍 Procesando:', transcript);

        // Buscar comando
        const matchedCommand = this.findMatchingCommand(transcript);

        if (matchedCommand) {
            console.log('✅ EJECUTANDO:', matchedCommand.name);
            this.executeCommand(matchedCommand);
        } else {
            console.log('⚠️ Sin comando para:', transcript);
            
            // Sugerencias simples
            if (transcript.includes('uno') || transcript.includes('1')) {
                console.log('💡 ¿Querías decir "módulo uno"?');
            } else if (transcript.includes('dos') || transcript.includes('2')) {
                console.log('💡 ¿Querías decir "módulo dos"?');
            }
        }
    }

    findMatchingCommand(transcript) {
        const normalizedTranscript = this.normalizeText(transcript);

        for (const command of this.commands) {
            const triggers = command.trigger.split(',').map(t => this.normalizeText(t.trim()));
            
            for (const trigger of triggers) {
                if (this.matchesTrigger(normalizedTranscript, trigger)) {
                    return command;
                }
            }
        }

        return null;
    }

    matchesTrigger(transcript, trigger) {
        // Coincidencia exacta
        if (transcript === trigger) return true;

        // Coincidencia si contiene el trigger
        if (transcript.includes(trigger)) return true;

        // Coincidencia con similitud (permite pequeñas variaciones)
        const similarity = this.calculateSimilarity(transcript, trigger);
        return similarity > 0.8; // 80% de similitud
    }

    normalizeText(text) {
        return text.toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '') // Remover acentos
            .replace(/[^\w\s]/g, '') // Remover puntuación
            .trim();
    }

    calculateSimilarity(str1, str2) {
        const longer = str1.length > str2.length ? str1 : str2;
        const shorter = str1.length > str2.length ? str2 : str1;
        
        if (longer.length === 0) return 1.0;
        
        const editDistance = this.levenshteinDistance(longer, shorter);
        return (longer.length - editDistance) / longer.length;
    }

    levenshteinDistance(str1, str2) {
        const matrix = [];

        for (let i = 0; i <= str2.length; i++) {
            matrix[i] = [i];
        }

        for (let j = 0; j <= str1.length; j++) {
            matrix[0][j] = j;
        }

        for (let i = 1; i <= str2.length; i++) {
            for (let j = 1; j <= str1.length; j++) {
                if (str2.charAt(i - 1) === str1.charAt(j - 1)) {
                    matrix[i][j] = matrix[i - 1][j - 1];
                } else {
                    matrix[i][j] = Math.min(
                        matrix[i - 1][j - 1] + 1,
                        matrix[i][j - 1] + 1,
                        matrix[i - 1][j] + 1
                    );
                }
            }
        }

        return matrix[str2.length][str1.length];
    }

    executeCommand(command) {
        this.showNotification(`✅ Ejecutando: ${command.name}`, 'success');

        switch (command.action) {
            case 'navigate':
                this.navigate(command.target);
                break;

            case 'export':
                this.exportData();
                break;

            case 'toggle':
                this.toggleFeature(command.target);
                break;

            case 'custom':
                this.executeCustomFunction(command.function_name);
                break;

            default:
                console.warn('⚠️ Acción no implementada:', command.action);
        }
    }

    navigate(url) {
        console.log('🔀 Navegando a:', url);
        window.location.href = url;
    }

    exportData() {
        console.log('📥 Exportando datos...');
        
        // Si existe la función global de exportar en el módulo actual
        if (typeof window.exportToExcel === 'function') {
            window.exportToExcel();
        } else if (typeof window.exportData === 'function') {
            window.exportData();
        } else {
            console.warn('⚠️ Función de exportar no encontrada');
            this.showNotification('Función de exportar no disponible', 'warning');
        }
    }

    toggleFeature(feature) {
        console.log('🔄 Alternando:', feature);
        
        // Implementar toggles específicos según el feature
        if (typeof window.toggleFeature === 'function') {
            window.toggleFeature(feature);
        } else {
            console.warn('⚠️ Toggle no implementado para:', feature);
        }
    }

    executeCustomFunction(functionName) {
        console.log('⚡ Ejecutando función personalizada:', functionName);
        
        try {
            // Buscar función en el scope global
            if (typeof window[functionName] === 'function') {
                window[functionName]();
            } else {
                // Intentar evaluar (cuidado con esto en producción)
                eval(functionName);
            }
        } catch (error) {
            console.error('❌ Error ejecutando función:', error);
            this.showNotification('Error ejecutando comando', 'error');
        }
    }

    showListeningIndicator() {
        // Crear o mostrar indicador visual
        let indicator = document.getElementById('voiceListeningIndicator');
        
        if (!indicator) {
            indicator = document.createElement('div');
            indicator.id = 'voiceListeningIndicator';
            indicator.className = 'fixed top-4 left-1/2 transform -translate-x-1/2 z-50';
            indicator.innerHTML = `
                <div class="bg-gradient-to-r from-purple-500 to-indigo-600 text-white px-6 py-3 rounded-full shadow-lg flex items-center gap-3">
                    <svg class="w-5 h-5 animate-pulse" fill="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="8"></circle>
                    </svg>
                    <span class="font-semibold">Escuchando...</span>
                </div>
            `;
            document.body.appendChild(indicator);
        } else {
            indicator.classList.remove('hidden');
        }
    }

    hideListeningIndicator() {
        const indicator = document.getElementById('voiceListeningIndicator');
        if (indicator) {
            indicator.classList.add('hidden');
        }
    }

    showNotification(message, type = 'info') {
        const colors = {
            success: 'from-green-500 to-green-600',
            error: 'from-red-500 to-red-600',
            warning: 'from-yellow-500 to-yellow-600',
            info: 'from-blue-500 to-blue-600'
        };

        const notification = document.createElement('div');
        notification.className = 'fixed top-20 right-4 z-50 animate-slide-in';
        notification.innerHTML = `
            <div class="bg-gradient-to-r ${colors[type]} text-white px-6 py-4 rounded-lg shadow-lg max-w-md">
                <p class="font-semibold">${message}</p>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
}

// Inicializar sistema global
window.voiceCommandSystem = new VoiceCommandSystem();

// Agregar atajo de teclado para activar voz (Ctrl + Shift + V)
document.addEventListener('keydown', (e) => {
    if (e.ctrlKey && e.shiftKey && e.key === 'V') {
        e.preventDefault();
        window.voiceCommandSystem.startListening();
    }
});

// Monitorear estado de conexión
window.addEventListener('online', () => {
    console.log('✅ Conexión a internet restaurada');
});

window.addEventListener('offline', () => {
    console.warn('⚠️ Sin conexión a internet. Los comandos de voz no funcionarán.');
});

// Mostrar estado al cargar
if (navigator.onLine) {
    console.log('🎤 Sistema de comandos de voz cargado. Usa Ctrl+Shift+V para activar.');
} else {
    console.warn('⚠️ Sistema de comandos de voz cargado pero sin conexión a internet.');
}
