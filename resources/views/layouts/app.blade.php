<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Polysense') }} - @yield('title')</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    @stack('styles')
</head>
<body class="bg-gray-100">
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex">
                    <div class="flex-shrink-0 flex items-center">
                        <a href="{{ url('/') }}" class="text-xl font-bold text-indigo-600 flex items-center gap-2">
                            <span>📡</span> Polysense
                        </a>
                    </div>

                    <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">

                        <a href="{{ route('modulo1') }}" 
                           class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors
                           {{ request()->routeIs('modulo1') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Módulo 1
                        </a>

                        <a href="{{ route('modulo2') }}" 
                           class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors
                           {{ request()->routeIs('modulo2') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Módulo 2
                        </a>

                        <a href="{{ route('modulo4') }}" 
                           class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors
                           {{ request()->routeIs('modulo4') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            Módulo 4
                        </a>
                    </div>
                </div>

                <div class="hidden sm:flex sm:items-center sm:ml-6">
                    @auth
                        <div class="ml-3 relative flex items-center gap-4">
                            <span class="text-sm text-gray-700 font-medium">{{ Auth::user()->name }}</span>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-semibold">
                                    Logout
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="space-x-4">
                            <a href="{{ route('login') }}" class="text-sm text-gray-700 hover:text-indigo-600 font-medium">Login</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="ml-4 text-sm bg-indigo-600 text-white px-3 py-2 rounded-md hover:bg-indigo-700">Register</a>
                            @endif
                        </div>
                    @endauth
                </div>

                <div class="flex items-center sm:hidden">
                    <button type="button" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100">
                        <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div id="mobile-menu" class="hidden sm:hidden bg-white border-t border-gray-200">
            <div class="pt-2 pb-3 space-y-1">
                <a href="{{ route('modulo1') }}" class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800">Módulo 1</a>
                <a href="{{ route('modulo2') }}" class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800">Módulo 2</a>
                <a href="{{ route('modulo4') }}" class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800">🎤 Módulo 4</a>
            </div>
        </div>
    </nav>

    <main class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif
            @yield('content')
        </div>
    </main>

    <footer class="bg-white mt-12 border-t border-gray-200">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
            <p class="text-center text-gray-500 text-sm">
                &copy; {{ date('Y') }} {{ config('app.name', 'Polysense') }}.
            </p>
        </div>
    </footer>

    <!-- Indicador de Voz Activa -->
    <div id="voiceIndicator" class="hidden fixed bottom-4 left-4 bg-blue-500 text-white px-4 py-2 rounded-full shadow-lg flex items-center space-x-2" style="z-index: 9999;">
        <svg class="w-5 h-5 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M7 4a3 3 0 016 0v4a3 3 0 11-6 0V4zm4 10.93A7.001 7.001 0 0017 8a1 1 0 10-2 0A5 5 0 015 8a1 1 0 00-2 0 7.001 7.001 0 006 6.93V17H6a1 1 0 100 2h8a1 1 0 100-2h-3v-2.07z" clip-rule="evenodd"/>
        </svg>
        <span class="status-text">Voz activa</span>
    </div>

    <!-- Texto Parcial de Voz -->
    <div id="voicePartial" class="hidden fixed bottom-16 left-4 bg-gray-800 text-white px-4 py-2 rounded-lg shadow-lg text-sm italic" style="z-index: 9999;"></div>

    <!-- Sistema de Comandos de Voz LOCAL (WebSocket) -->
    <script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
    <script src="{{ asset('js/voice-websocket.js') }}"></script>

    @stack('scripts')
</body>
</html>