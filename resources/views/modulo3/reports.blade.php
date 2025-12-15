@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-4">Todos los Reportes (Todos los Usuarios)</h1>

    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="block text-sm font-semibold mb-1">Usuario</label>
                <select name="user_id" class="border rounded px-3 py-2 w-full">
                    <option value="">Todos los usuarios</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }} ({{ $user->email }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Tipo</label>
                <select name="type" class="border rounded px-3 py-2 w-full">
                    <option value="">Todos los tipos</option>
                    <option value="daily" {{ request('type') == 'daily' ? 'selected' : '' }}>Diario</option>
                    <option value="weekly" {{ request('type') == 'weekly' ? 'selected' : '' }}>Semanal</option>
                    <option value="monthly" {{ request('type') == 'monthly' ? 'selected' : '' }}>Mensual</option>
                    <option value="custom" {{ request('type') == 'custom' ? 'selected' : '' }}>Personalizado</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white px-4 py-2 rounded w-full">
                    Filtrar
                </button>
            </div>
        </form>
    </div>

    <!-- Tabla de reportes -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Usuario</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Título</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tipo</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Estado</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Generado</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Detecciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($reports as $report)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="font-semibold">{{ $report->user->name }}</div>
                            <div class="text-xs text-gray-500">{{ $report->user->email }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $report->title }}</div>
                            @if($report->description)
                                <div class="text-sm text-gray-600">{{ Str::limit($report->description, 40) }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800">
                                {{ strtoupper($report->type) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs rounded 
                                @if($report->status === 'generated') bg-green-100 text-green-800
                                @elseif($report->status === 'viewed') bg-gray-100 text-gray-800
                                @else bg-yellow-100 text-yellow-800 @endif">
                                {{ strtoupper($report->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            {{ $report->generated_at ? $report->generated_at->format('d/m/Y H:i') : 'Pendiente' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ isset($report->data['total_detections']) ? $report->data['total_detections'] : 'N/A' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            No hay reportes
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div class="mt-4">
        {{ $reports->links() }}
    </div>

    <div class="mt-6">
        <a href="{{ route('modulo3') }}" class="bg-gray-500 hover:bg-gray-700 text-white px-4 py-2 rounded">
            Volver a Automatizaciones
        </a>
    </div>
</div>
@endsection
