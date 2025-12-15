@extends('layouts.app')

@section('content')
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <h2 class="text-2xl font-bold mb-6">Módulo 3: Notificaciones y Reportes</h2>

                <!-- Tarjetas de resumen -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <!-- Notificaciones no leídas -->
                    <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg shadow-lg p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-blue-100 text-sm">Notificaciones No Leídas</p>
                                <p class="text-3xl font-bold">{{ $unreadCount }}</p>
                            </div>
                            <svg class="w-12 h-12 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                        </div>
                        <a href="{{ route('modulo3.notifications') }}" class="mt-4 inline-block text-sm text-blue-100 hover:text-white">
                            Ver todas →
                        </a>
                    </div>

                    <!-- Reportes generados -->
                    <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg shadow-lg p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-green-100 text-sm">Reportes Generados</p>
                                <p class="text-3xl font-bold">{{ $recentReports->count() }}</p>
                            </div>
                            <svg class="w-12 h-12 text-green-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <a href="{{ route('modulo3.reports') }}" class="mt-4 inline-block text-sm text-green-100 hover:text-white">
                            Ver todos →
                        </a>
                    </div>

                    <!-- Detecciones totales (última semana) -->
                    <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-purple-100 text-sm">Detecciones (7 días)</p>
                                <p class="text-3xl font-bold">{{ $quickStats['total_detections'] ?? 0 }}</p>
                            </div>
                            <svg class="w-12 h-12 text-purple-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                            </svg>
                        </div>
                        <p class="mt-4 text-sm text-purple-100">
                            Confianza promedio: {{ $quickStats['average_confidence'] ?? 0 }}%
                        </p>
                    </div>
                </div>

                <!-- Notificaciones recientes -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold mb-4">Notificaciones Recientes</h3>
                    @if($recentNotifications->count() > 0)
                        <div class="space-y-3">
                            @foreach($recentNotifications as $notification)
                                <div class="flex items-start p-4 bg-gray-50 rounded-lg border-l-4 
                                    @if($notification->type === 'alert') border-red-500
                                    @elseif($notification->type === 'warning') border-yellow-500
                                    @elseif($notification->type === 'success') border-green-500
                                    @else border-blue-500 @endif">
                                    <div class="flex-1">
                                        <p class="font-semibold">{{ $notification->title }}</p>
                                        <p class="text-sm text-gray-600">{{ $notification->message }}</p>
                                        <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500">No hay notificaciones recientes</p>
                    @endif
                </div>

                <!-- Accesos rápidos -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="{{ route('modulo3.notifications') }}" class="block p-6 bg-blue-50 hover:bg-blue-100 rounded-lg transition">
                        <h4 class="font-semibold text-blue-900 mb-2">Gestionar Notificaciones</h4>
                        <p class="text-sm text-blue-700">Ver, filtrar y gestionar todas las notificaciones del sistema</p>
                    </a>
                    <a href="{{ route('modulo3.reports') }}" class="block p-6 bg-green-50 hover:bg-green-100 rounded-lg transition">
                        <h4 class="font-semibold text-green-900 mb-2">Generar Reportes</h4>
                        <p class="text-sm text-green-700">Crear y visualizar reportes de detecciones vehiculares</p>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
