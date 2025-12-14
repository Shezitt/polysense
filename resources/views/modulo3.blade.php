@extends('layouts.app')

@section('title', 'Módulo 3: Automatización')

@section('content')
<div class="space-y-8">
    <!-- Header Styled as Card -->
    <div class="bg-white shadow-lg rounded-xl p-6 mb-8 border border-gray-100">
        <div>
            <h2 class="text-xl font-bold text-gray-900">
                Módulo 3: Automatización y Gestión
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Optimización y Reglas de Negocio
            </p>
        </div>
    </div>

    <!-- Grid Layout -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        
        <!-- Card 1: Limpieza de Datos -->
        <div class="bg-white shadow-lg rounded-xl overflow-hidden border border-gray-100">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-100 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">🗑️ Limpieza de Datos</h3>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    DB: {{ $dbSize }}
                </span>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-6">
                    Elimine registros antiguos para liberar espacio. Los datos eliminados <span class="font-bold text-red-500">no se pueden recuperar</span>.
                </p>
                <form action="{{ route('modulo3.clean') }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="camera" class="block text-sm font-medium text-gray-700">Cámara Objetivo</label>
                        <select id="camera" name="camera" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($cameras as $cam)
                                <option value="{{ $cam }}">{{ $cam }}</option>
                            @endforeach
                            <option value="all">Todas las Cámaras</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="period" class="block text-sm font-medium text-gray-700">Retener Datos De</label>
                            <select id="period" name="period" onchange="toggleCustomDate(this.value)" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="1week">Última Semana</option>
                                <option value="1month">Último Mes</option>
                                <option value="custom">Rango Personalizado</option>
                            </select>
                        </div>
                        <div>
                            <label for="execution_frequency" class="block text-sm font-medium text-gray-700">Ejecución</label>
                            <select id="execution_frequency" name="execution_frequency" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="now">Inmediata</option>
                                <option value="daily">Diaria (Auto)</option>
                                <option value="weekly">Semanal (Auto)</option>
                            </select>
                        </div>
                    </div>

                    <div id="custom_date_container" class="hidden">
                        <label for="custom_date" class="block text-sm font-medium text-gray-700">Fecha de Corte (Conservar posterior a):</label>
                        <input type="date" name="custom_date" id="custom_date" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                            Ejecutar Limpieza
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Card 2: Suscripción a Reportes -->
        <div class="bg-white shadow-lg rounded-xl overflow-hidden border border-gray-100">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800">📧 Distribución de Reportes</h3>
            </div>
            <div class="p-6">
                <!-- Add User Form -->
                <form action="{{ route('modulo3.saveUser') }}" method="POST" class="space-y-4 mb-8">
                    @csrf
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2 sm:col-span-1">
                            <label for="name" class="block text-sm font-medium text-gray-700">Nombre</label>
                            <input type="text" name="name" id="name" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div class="col-span-2 sm:col-span-1">
                            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" name="email" id="email" required class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="report_frequency" class="block text-sm font-medium text-gray-700">Frecuencia</label>
                            <select name="report_frequency" id="report_frequency" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="daily">Diario</option>
                                <option value="weekly">Semanal</option>
                                <option value="monthly">Mensual</option>
                            </select>
                        </div>
                        <div>
                            <label for="report_format" class="block text-sm font-medium text-gray-700">Formato</label>
                            <select name="report_format" id="report_format" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="pdf">PDF</option>
                                <option value="latex">LaTeX</option>
                                <option value="excel">Excel</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        Registrar Destinatario
                    </button>
                </form>

                <!-- Users List -->
                <div class="border-t border-gray-100 pt-4">
                    <div class="flex justify-between items-center mb-4">
                        <h4 class="text-sm font-medium text-gray-700">Suscripciones Activas</h4>
                        <form action="{{ route('modulo3.sendReport') }}" method="POST">
                            @csrf
                            <button type="submit" class="text-xs bg-indigo-50 text-indigo-700 hover:bg-indigo-100 px-3 py-1 rounded-full font-medium transition-colors">
                                📤 Simular Envío
                            </button>
                        </form>
                    </div>
                    <div class="max-h-40 overflow-y-auto space-y-2">
                        @if($config->users && count($config->users->children()) > 0)
                            @foreach($config->users->user as $user)
                                <div class="bg-gray-50 rounded-lg p-3 flex justify-between items-center text-sm">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $user->name }}</p>
                                        <p class="text-gray-500 text-xs">{{ $user->email }}</p>
                                    </div>
                                    @php
                                        $uReport = null;
                                        foreach($config->reports->report as $rep) {
                                            if((string)$rep->user_email == (string)$user->email) {
                                                $uReport = $rep;
                                                break;
                                            }
                                        }
                                    @endphp
                                    @if($uReport)
                                        <div class="text-right">
                                            <span class="block text-xs font-semibold text-indigo-600">{{ ucfirst($uReport->frequency) }}</span>
                                            <span class="block text-xs text-gray-400">{{ strtoupper($uReport->format) }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        @else
                            <p class="text-sm text-gray-500 text-center italic py-2">No hay destinatarios registrados.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 3: Reglas de Notificación -->
        <div class="bg-white shadow-lg rounded-xl overflow-hidden border border-gray-100 md:col-span-2 lg:col-span-1">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800">🔔 Reglas de Alerta</h3>
            </div>
            <div class="p-6">
                <form action="{{ route('modulo3.notify') }}" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label for="notify_user" class="block text-sm font-medium text-gray-700">Usuario Destino</label>
                            <select name="user_email" id="notify_user" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @if($config->users && count($config->users->children()) > 0)
                                    @foreach($config->users->user as $user)
                                        <option value="{{ $user->email }}">{{ $user->name }}</option>
                                    @endforeach
                                @else
                                    <option disabled>Registre usuarios en la sección de Reportes</option>
                                @endif
                            </select>
                        </div>
                        <div>
                            <label for="min_threshold" class="block text-sm font-medium text-gray-700">Umbral Mín.</label>
                            <input type="number" name="min_threshold" id="min_threshold" value="1" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label for="max_threshold" class="block text-sm font-medium text-gray-700">Umbral Máx.</label>
                            <input type="number" name="max_threshold" id="max_threshold" value="10" class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                    
                    <div class="relative flex items-start py-2">
                        <div class="flex items-center h-5">
                            <input id="notify_black_screen" name="notify_black_screen" type="checkbox" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                        </div>
                        <div class="ml-3 text-sm">
                            <label for="notify_black_screen" class="font-medium text-gray-700">Alerta de Inactividad</label>
                            <p class="text-gray-500 text-xs">Notificar si la cámara deja de enviar datos.</p>
                        </div>
                    </div>

                    <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors">
                        Crear Regla
                    </button>
                </form>

                <div class="mt-6">
                    <h4 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Reglas Activas</h4>
                    <div class="space-y-2">
                        @if($config->notifications && count($config->notifications->children()) > 0)
                            @foreach($config->notifications->notification as $rule)
                                <div class="bg-indigo-50 rounded-lg p-3 text-sm border border-indigo-100">
                                    <div class="flex justify-between">
                                        <span class="font-bold text-indigo-900">{{ $rule->user_email }}</span>
                                        <span class="text-xs text-indigo-500 bg-white px-2 py-0.5 rounded-full border border-indigo-200">{{ $rule->camera }}</span>
                                    </div>
                                    <div class="mt-1 text-indigo-700 text-xs flex gap-2">
                                        <span>📉 &lt; {{ $rule->min_threshold }}</span>
                                        <span>📈 &gt; {{ $rule->max_threshold }}</span>
                                        @if($rule->notify_black_screen == 'true')
                                            <span>⚠️ Inactividad</span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p class="text-sm text-gray-500 italic">Sin reglas definidas.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Card 4: Estado de Dispositivos (Resumen) -->
        <div class="bg-white shadow-lg rounded-xl overflow-hidden border border-gray-100 md:col-span-2 lg:col-span-1">
            <div class="bg-gray-50 px-6 py-4 border-b border-gray-100">
                <h3 class="text-lg font-semibold text-gray-800">📡 Estado de Dispositivos</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    @foreach($cameras as $cam)
                        @php
                            $statusInfo = $cameraStatus[$cam] ?? ['status' => 'UNKNOWN', 'last_seen' => 'N/A'];
                            $isOnline = $statusInfo['status'] === 'ONLINE';
                            $statusColor = $isOnline ? 'bg-green-100 text-green-800 border-green-200' : 'bg-red-100 text-red-800 border-red-200';
                            $iconColor = $isOnline ? 'text-green-500' : 'text-red-500';
                        @endphp
                        <div class="flex items-center p-4 bg-white border rounded-lg shadow-sm hover:shadow-md transition-shadow">
                            <div class="flex-shrink-0 p-3 rounded-full {{ $isOnline ? 'bg-green-50' : 'bg-red-50' }}">
                                <svg class="h-6 w-6 {{ $iconColor }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div class="ml-4 flex-1">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-lg font-bold text-gray-900">{{ $cam }}</h4>
                                    <span class="px-2 py-1 text-xs font-bold rounded-full border {{ $statusColor }}">
                                        {{ $statusInfo['status'] }}
                                    </span>
                                </div>
                                <p class="mt-1 text-sm text-gray-500">
                                    Última actividad: {{ $statusInfo['last_seen'] }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-6 bg-blue-50 border border-blue-100 rounded-lg p-4">
                     <h4 class="text-sm font-semibold text-blue-900 mb-1">Información del Sistema</h4>
                     <p class="text-xs text-blue-700">
                         El sistema monitorea la base de datos XML localmente. El estado ONLINE indica actividad en los últimos 5 minutos.
                     </p>
                </div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
    function toggleCustomDate(val) {
        const el = document.getElementById('custom_date_container');
        if (val === 'custom') {
            el.classList.remove('hidden');
        } else {
            el.classList.add('hidden');
        }
    }
</script>
@endpush
@endsection
