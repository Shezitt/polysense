<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <title><?php echo e(config('app.name', 'Polysense')); ?> - <?php echo $__env->yieldContent('title'); ?></title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>

    <?php echo $__env->yieldPushContent('styles'); ?>
    
    <style>
        /* Header positioning styles generic */
        .layout-container {
            min-height: 100vh;
            display: flex;
        }
        
        /* Top position (default) */
        .layout-container.header-top {
            flex-direction: column;
        }
        .layout-container.header-top nav { width: 100%; }
        
        /* Bottom position */
        .layout-container.header-bottom {
            flex-direction: column-reverse;
        }
        .layout-container.header-bottom nav { width: 100%; }
        
        /* Left/Right Sidebar Common base */
        .layout-container.header-left,
        .layout-container.header-right {
           flex-direction: row; 
        }
        .layout-container.header-right {
            flex-direction: row-reverse;
        }

        /* --- Sidebar Specific Styles (Verge Style) --- */
        .layout-container.header-left nav,
        .layout-container.header-right nav {
            width: 280px;
            height: 100vh;
            position: sticky;
            top: 0;
            flex-shrink: 0;
            background-color: white;
            border-right: 1px solid #e5e7eb;
            overflow-y: auto;
            z-index: 40;
        }
        
        .layout-container.header-right nav {
            border-right: none;
            border-left: 1px solid #e5e7eb;
        }

        /* Reset container constraints */
        .layout-container.header-left nav .max-w-7xl,
        .layout-container.header-right nav .max-w-7xl {
            max-width: none;
            padding: 0;
            height: 100%;
        }

        /* Flex container reset to column */
        .layout-container.header-left nav .flex.justify-between,
        .layout-container.header-right nav .flex.justify-between {
            flex-direction: column;
            height: 100%; /* Override h-16 */
            justify-content: flex-start;
            padding: 1.5rem;
        }

        /* Logo Area */
        .layout-container.header-left nav .flex-shrink-0,
        .layout-container.header-right nav .flex-shrink-0 {
            width: 100%;
            margin-bottom: 2rem;
            padding-left: 0.5rem;
        }
        .layout-container.header-left nav .flex-shrink-0 a,
        .layout-container.header-right nav .flex-shrink-0 a {
            font-size: 1.5rem; /* Larger logo */
        }

        /* Nav Links Container */
        .layout-container.header-left nav .hidden.space-x-8,
        .layout-container.header-right nav .hidden.space-x-8 {
            display: flex !important; /* Force show */
            flex-direction: column;
            margin-left: 0;
            margin-top: 0;
            width: 100%;
            /* Remove space-x-8 margin effects */
            --tw-space-x-reverse: 0; 
            margin-right: 0; 
        }
        
        /* Remove space-x-* margins manually for children */
        .layout-container.header-left nav .hidden.space-x-8 > :not([hidden]) ~ :not([hidden]),
        .layout-container.header-right nav .hidden.space-x-8 > :not([hidden]) ~ :not([hidden]) {
            margin-left: 0;
            margin-top: 0.5rem; 
        }

        /* Link Styling */
        .layout-container.header-left nav a.inline-flex,
        .layout-container.header-right nav a.inline-flex {
            display: flex;
            width: 100%;
            padding: 0.75rem 1rem;
            border-bottom: 0 !important;
            border-left: 4px solid transparent;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 500;
            color: #4b5563; /* text-gray-600 */
        }
        
        .layout-container.header-left nav a.inline-flex:hover,
        .layout-container.header-right nav a.inline-flex:hover {
            background-color: #f3f4f6;
            color: #1f2937;
        }

        /* Active Link */
        .layout-container.header-left nav a.border-indigo-500,
        .layout-container.header-right nav a.border-indigo-500 {
            background-color: #eef2ff; /* indigo-50 */
            color: #4f46e5 !important; /* indigo-600 */
            border-left-color: #4f46e5;
        }

        /* Wrapper for Logo + Links to take available space */
        .layout-container.header-left nav .flex,
        .layout-container.header-right nav .flex {
            /* This targets the first child of justify-between, which holds logo + links */
            flex-direction: column;
            width: 100%;
        }

        /* User Menu Section (Bottom of Sidebar) */
        .layout-container.header-left nav .hidden.sm\:flex.sm\:items-center.sm\:ml-6,
        .layout-container.header-right nav .hidden.sm\:flex.sm\:items-center.sm\:ml-6 {
            margin-top: auto; /* Push to bottom */
            margin-left: 0;
            padding-top: 1.5rem;
            border-top: 1px solid #f3f4f6;
            width: 100%;
            display: flex !important;
            flex-direction: row-reverse; /* Put profile pic/name first if row, but let's see */
            justify-content: space-between;
        }

        /* Main Content Wrapper */
        .main-wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
            min-width: 0; /* Prevent flex overflow */
        }
        
        /* Mobile menu toggle button hidden on larger screens by reset */
    </style>
</head>
<body class="bg-gray-100">
    <?php
        $headerPosition = Auth::check() ? (Auth::user()->header_position ?? 'top') : 'top';
    ?>
    
    <div class="layout-container header-<?php echo e($headerPosition); ?>">
        <nav class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <a href="<?php echo e(url('/')); ?>" class="text-xl font-bold text-indigo-600 flex items-center gap-2">
                                Polysense
                            </a>
                        </div>

                        <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">

                            <a href="<?php echo e(route('modulo1')); ?>" 
                               class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors
                               <?php echo e(request()->routeIs('modulo1') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'); ?>">
                                Deteccion
                            </a>

                            <a href="<?php echo e(route('modulo2')); ?>" 
                               class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors
                               <?php echo e(request()->routeIs('modulo2') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'); ?>">
                                Reportes
                            </a>

                            <a href="<?php echo e(route('modulo3')); ?>" 
                               class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors
                               <?php echo e(request()->routeIs('modulo3') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'); ?>">
                                Automatizaciones
                            </a>

                            <a href="<?php echo e(route('modulo4')); ?>" 
                               class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors
                               <?php echo e(request()->routeIs('modulo4') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'); ?>">
                                Accesibilidad
                            </a>

                            <?php if(auth()->guard()->check()): ?>
                                <?php if(Auth::user()->role === 'admin'): ?>
                                    <a href="<?php echo e(route('modulo5')); ?>" 
                                       class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition-colors
                                       <?php echo e(request()->routeIs('modulo5') ? 'border-indigo-500 text-gray-900' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'); ?>">
                                        Gestion
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="hidden sm:flex sm:items-center sm:ml-6">
                        <?php if(auth()->guard()->check()): ?>
                            <div class="ml-3 relative flex items-center gap-4">
                                <?php echo $__env->make('components.header-position-selector', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                <span class="text-sm text-gray-700 font-medium"><?php echo e(Auth::user()->name); ?></span>
                                <form method="POST" action="<?php echo e(route('logout')); ?>" class="inline">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-semibold">
                                        Cerrar Sesión
                                    </button>
                                </form>
                            </div>
                        <?php else: ?>
                            <div class="space-x-4">
                                <a href="<?php echo e(route('login')); ?>" class="text-sm text-gray-700 hover:text-indigo-600 font-medium">Iniciar Sesión</a>
                            </div>
                        <?php endif; ?>
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
                    <a href="<?php echo e(route('modulo1')); ?>" class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800">Deteccion</a>
                    <a href="<?php echo e(route('modulo2')); ?>" class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800">Reportes</a>
                    <a href="<?php echo e(route('modulo3')); ?>" class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800">Automatizaciones</a>
                    <a href="<?php echo e(route('modulo4')); ?>" class="block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-gray-600 hover:bg-gray-50 hover:border-gray-300 hover:text-gray-800">Accesibilidad</a>
                </div>
            </div>
        </nav>

        <div class="main-wrapper">
            <main class="py-8 flex-1">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <?php if(session('success')): ?>
                        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                            <?php echo e(session('success')); ?>

                        </div>
                    <?php endif; ?>
                    <?php echo $__env->yieldContent('content'); ?>
                </div>
            </main>

            <footer class="bg-white mt-12 border-t border-gray-200">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <p class="text-center text-gray-500 text-sm">
                        &copy; <?php echo e(date('Y')); ?> <?php echo e(config('app.name', 'Polysense')); ?>.
                    </p>
                </div>
            </footer>
        </div>
    </div>

    <script src="https://cdn.socket.io/4.5.4/socket.io.min.js"></script>
    <script src="<?php echo e(asset('js/voice-websocket.js')); ?>"></script>

    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html><?php /**PATH C:\xampp\htdocs\ProyectoFinal\polysense\resources\views/layouts/app.blade.php ENDPATH**/ ?>