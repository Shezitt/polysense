<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Polysense</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen">
    <div class="bg-white rounded-lg shadow-xl p-8 w-full max-w-md">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Polysense</h1>
            <p class="text-gray-600 mt-2">Iniciar Sesión</p>
        </div>

        <?php if(session('success')): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo e(session('success')); ?>

        </div>
        <?php endif; ?>

        <?php if($errors->any()): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc list-inside">
                <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li><?php echo e($error); ?></li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </div>
        <?php endif; ?>

        <form action="<?php echo e(route('login.post')); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <div class="mb-4">
                <label class="block text-gray-700 font-semibold mb-2" for="email">
                    Email
                </label>
                <input 
                    type="email" 
                    name="email" 
                    id="email"
                    value="<?php echo e(old('email')); ?>"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="admin@polysense.com"
                >
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 font-semibold mb-2" for="password">
                    Contraseña
                </label>
                <input 
                    type="password" 
                    name="password" 
                    id="password"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500"
                    placeholder="********"
                >
            </div>

            <div class="mb-6 flex items-center">
                <input 
                    type="checkbox" 
                    name="remember" 
                    id="remember"
                    class="mr-2"
                >
                <label for="remember" class="text-gray-700">
                    Recordarme
                </label>
            </div>

            <button 
                type="submit"
                class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-3 rounded-lg transition duration-200"
            >
                Iniciar Sesión
            </button>
        </form>

        <div class="mt-6 p-4 bg-gray-100 rounded-lg">
            <p class="text-sm text-gray-700 font-semibold mb-2">Usuario Admin por defecto:</p>
            <p class="text-xs text-gray-600">Email: <code class="bg-white px-2 py-1 rounded">admin@polysense.com</code></p>
            <p class="text-xs text-gray-600">Contraseña: <code class="bg-white px-2 py-1 rounded">admin123</code></p>
        </div>
    </div>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\ProyectoFinal\polysense\resources\views/login.blade.php ENDPATH**/ ?>