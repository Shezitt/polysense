@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-4">Mis Notificaciones</h1>

    @if($notifications->count() > 0)
        <div class="space-y-3">
            @foreach($notifications as $notification)
                <div class="flex items-start p-4 bg-gray-50 rounded-lg border-l-4 
                    @if($notification->type === 'alert') border-red-500
                    @elseif($notification->type === 'warning') border-yellow-500
                    @elseif($notification->type === 'success') border-green-500
                    @else border-blue-500 @endif
                    {{ $notification->is_read ? 'opacity-60' : '' }}">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-1 text-xs rounded 
                                @if($notification->type === 'alert') bg-red-100 text-red-800
                                @elseif($notification->type === 'warning') bg-yellow-100 text-yellow-800
                                @elseif($notification->type === 'success') bg-green-100 text-green-800
                                @else bg-blue-100 text-blue-800 @endif">
                                {{ strtoupper($notification->type) }}
                            </span>
                            @if(!$notification->is_read)
                                <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800">NUEVA</span>
                            @endif
                        </div>
                        <p class="font-semibold">{{ $notification->title }}</p>
                        <p class="text-sm text-gray-600">{{ $notification->message }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ $notification->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $notifications->links() }}
        </div>
    @else
        <p class="text-gray-500">No tienes notificaciones</p>
    @endif

    <div class="mt-6">
        <a href="{{ route('modulo2') }}" class="bg-gray-500 hover:bg-gray-700 text-white px-4 py-2 rounded">
            Volver a Reportes
        </a>
    </div>
</div>
@endsection
