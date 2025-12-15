@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-4">Todas las Notificaciones (Todos los Usuarios)</h1>

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
                    <option value="info" {{ request('type') == 'info' ? 'selected' : '' }}>Info</option>
                    <option value="warning" {{ request('type') == 'warning' ? 'selected' : '' }}>Warning</option>
                    <option value="alert" {{ request('type') == 'alert' ? 'selected' : '' }}>Alert</option>
                    <option value="success" {{ request('type') == 'success' ? 'selected' : '' }}>Success</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white px-4 py-2 rounded w-full">
                    Filtrar
                </button>
            </div>
        </form>
    </div>

    <!-- Tabla de notificaciones -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Usuario</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Título</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Tipo</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Estado</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Fecha</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($notifications as $notification)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="font-semibold">{{ $notification->user->name }}</div>
                            <div class="text-xs text-gray-500">{{ $notification->user->email }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $notification->title }}</div>
                            <div class="text-sm text-gray-600">{{ Str::limit($notification->message, 50) }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 text-xs rounded 
                                @if($notification->type === 'alert') bg-red-100 text-red-800
                                @elseif($notification->type === 'warning') bg-yellow-100 text-yellow-800
                                @elseif($notification->type === 'success') bg-green-100 text-green-800
                                @else bg-blue-100 text-blue-800 @endif">
                                {{ strtoupper($notification->type) }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if($notification->is_read)
                                <span class="text-gray-500 text-sm">Leída</span>
                            @else
                                <span class="text-blue-600 font-semibold text-sm">Nueva</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-500">
                            {{ $notification->created_at->format('d/m/Y H:i') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                            No hay notificaciones
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div class="mt-4">
        {{ $notifications->links() }}
    </div>

    <div class="mt-6">
        <a href="{{ route('modulo3') }}" class="bg-gray-500 hover:bg-gray-700 text-white px-4 py-2 rounded">
            Volver al Módulo 3
        </a>
    </div>
</div>
@endsection
