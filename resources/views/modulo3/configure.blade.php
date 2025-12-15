@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Configurar Usuario: {{ $user->name }}</h1>

    <form action="{{ route('modulo3.save-config', $user->id) }}" method="POST" class="bg-white rounded-lg shadow p-6">
        @csrf
        
        <!-- Configuración de Notificaciones -->
        <div class="mb-8">
            <h2 class="text-xl font-bold mb-4">Notificaciones</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-2">Umbral de Tráfico (vehículos)</label>
                    <input type="number" name="traffic_threshold" value="{{ $config->traffic_threshold }}" class="border rounded px-3 py-2 w-full" min="1" max="1000" required>
                </div>
                <div>
                    <label class="block mb-2">Minutos para Umbral</label>
                    <input type="number" name="traffic_minutes" value="{{ $config->traffic_minutes }}" class="border rounded px-3 py-2 w-full" min="1" max="60" required>
                </div>
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="notify_high_traffic" value="1" {{ $config->notify_high_traffic ? 'checked' : '' }} class="mr-2">
                        Notificar Alto Tráfico
                    </label>
                </div>
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="notify_camera_offline" value="1" {{ $config->notify_camera_offline ? 'checked' : '' }} class="mr-2">
                        Notificar Cámara Offline
                    </label>
                </div>
                <div>
                    <label class="block mb-2">Minutos para Cámara Offline</label>
                    <input type="number" name="camera_offline_minutes" value="{{ $config->camera_offline_minutes }}" class="border rounded px-3 py-2 w-full" min="1" max="120" required>
                </div>
            </div>
        </div>

        <!-- Configuración de Reportes -->
        <div class="mb-8">
            <h2 class="text-xl font-bold mb-4">Reportes Automáticos</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block mb-2">Frecuencia de Generación</label>
                    <select name="report_frequency" class="border rounded px-3 py-2 w-full" required>
                        <option value="disabled" {{ $config->report_frequency === 'disabled' ? 'selected' : '' }}>Deshabilitado</option>
                        <option value="daily" {{ $config->report_frequency === 'daily' ? 'selected' : '' }}>Diario</option>
                        <option value="weekly" {{ $config->report_frequency === 'weekly' ? 'selected' : '' }}>Semanal</option>
                        <option value="monthly" {{ $config->report_frequency === 'monthly' ? 'selected' : '' }}>Mensual</option>
                    </select>
                </div>
                <div>
                    <label class="block mb-2">Hora de Generación</label>
                    <input type="time" name="report_generation_time" value="{{ $config->report_generation_time ? $config->report_generation_time->format('H:i') : '08:00' }}" class="border rounded px-3 py-2 w-full" required>
                </div>
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="auto_generate_reports" value="1" {{ $config->auto_generate_reports ? 'checked' : '' }} class="mr-2">
                        Generar Reportes Automáticamente
                    </label>
                </div>
            </div>
        </div>

        <div class="flex gap-4">
            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white px-6 py-2 rounded">
                Guardar Configuración
            </button>
            <a href="{{ route('modulo3') }}" class="bg-gray-500 hover:bg-gray-700 text-white px-6 py-2 rounded">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection
