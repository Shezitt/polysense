@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-4">Configuración de Limpieza XML</h1>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-lg shadow p-6">
        <form action="{{ route('modulo3.save-xml-cleanup') }}" method="POST">
            @csrf
            
            <div class="mb-6">
                <h2 class="text-xl font-bold mb-4">Limpieza Automática del XML</h2>
                <p class="text-gray-600 mb-4">Configura la frecuencia y retención de datos en el archivo vehiculos_db.xml</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block mb-2 font-semibold">Frecuencia de Limpieza</label>
                        <select name="xml_cleanup_frequency" class="border rounded px-3 py-2 w-full" required>
                            <option value="never" {{ $config && $config->xml_cleanup_frequency === 'never' ? 'selected' : '' }}>Nunca</option>
                            <option value="daily" {{ $config && $config->xml_cleanup_frequency === 'daily' ? 'selected' : '' }}>Diario</option>
                            <option value="weekly" {{ $config && $config->xml_cleanup_frequency === 'weekly' ? 'selected' : '' }}>Semanal</option>
                            <option value="monthly" {{ $config && $config->xml_cleanup_frequency === 'monthly' ? 'selected' : '' }}>Mensual</option>
                        </select>
                        <p class="text-xs text-gray-500 mt-1">¿Con qué frecuencia se debe limpiar el XML?</p>
                    </div>
                    
                    <div>
                        <label class="block mb-2 font-semibold">Días de Retención</label>
                        <input type="number" name="xml_retention_days" 
                               value="{{ $config ? $config->xml_retention_days : 30 }}" 
                               class="border rounded px-3 py-2 w-full" 
                               min="1" max="365" required>
                        <p class="text-xs text-gray-500 mt-1">Cuántos días mantener los datos antes de eliminarlos</p>
                    </div>
                </div>
            </div>

            <div class="bg-yellow-50 border border-yellow-200 rounded p-4 mb-6">
                <p class="text-sm text-yellow-800">
                    <strong>Advertencia:</strong> La limpieza eliminará permanentemente los registros antiguos del XML. 
                    Asegúrate de tener respaldos si es necesario.
                </p>
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
</div>
@endsection
