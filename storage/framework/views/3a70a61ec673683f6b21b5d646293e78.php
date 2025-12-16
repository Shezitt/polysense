<?php $__env->startSection('content'); ?>
<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Automatizaciones: Automatización y Gestión</h1>

    <?php if(session('success')): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>

    <!-- Estadísticas -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-blue-100 p-4 rounded-lg">
            <h3 class="text-lg font-semibold">Usuarios Activos</h3>
            <p class="text-3xl font-bold"><?php echo e($activeUsers); ?></p>
        </div>
        <div class="bg-green-100 p-4 rounded-lg">
            <h3 class="text-lg font-semibold">Total Notificaciones</h3>
            <p class="text-3xl font-bold"><?php echo e($totalNotifications); ?></p>
        </div>
        <div class="bg-purple-100 p-4 rounded-lg">
            <h3 class="text-lg font-semibold">Total Reportes</h3>
            <p class="text-3xl font-bold"><?php echo e($totalReports); ?></p>
        </div>
    </div>

    <!-- Accesos rápidos -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <a href="<?php echo e(route('modulo3.all-notifications')); ?>" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
            <h3 class="font-bold text-lg mb-2">Ver Todas las Notificaciones</h3>
            <p class="text-gray-600">Gestionar notificaciones de todos los usuarios</p>
        </a>
        <a href="<?php echo e(route('modulo3.all-reports')); ?>" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
            <h3 class="font-bold text-lg mb-2">Ver Todos los Reportes</h3>
            <p class="text-gray-600">Gestionar reportes de todos los usuarios</p>
        </a>
        <a href="<?php echo e(route('modulo3.xml-cleanup')); ?>" class="bg-white p-6 rounded-lg shadow hover:shadow-lg transition">
            <h3 class="font-bold text-lg mb-2">Configurar Limpieza XML</h3>
            <p class="text-gray-600">Configuración global de limpieza automática</p>
        </a>
    </div>

    <!-- Lista de usuarios -->
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold mb-4">Configuración por Usuario</h2>
        <table class="w-full">
            <thead>
                <tr class="border-b">
                    <th class="text-left py-2">Usuario</th>
                    <th class="text-left py-2">Email</th>
                    <th class="text-left py-2">Rol</th>
                    <th class="text-left py-2">Configurado</th>
                    <th class="text-left py-2">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $user): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="py-3"><?php echo e($user->name); ?></td>
                        <td class="py-3"><?php echo e($user->email); ?></td>
                        <td class="py-3">
                            <span class="px-2 py-1 rounded text-xs <?php echo e($user->role === 'admin' ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'); ?>">
                                <?php echo e(ucfirst($user->role)); ?>

                            </span>
                        </td>
                        <td class="py-3">
                            <?php if($user->automationConfig): ?>
                                <span class="text-green-600">✓ Sí</span>
                            <?php else: ?>
                                <span class="text-gray-400">No</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3">
                            <a href="<?php echo e(route('modulo3.configure', $user->id)); ?>" class="bg-blue-500 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm">
                                Configurar
                            </a>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\ProyectoFinal\polysense\resources\views/modulo3/index.blade.php ENDPATH**/ ?>