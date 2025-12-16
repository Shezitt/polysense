<?php $__env->startSection('content'); ?>
<div class="container mx-auto px-4 py-6">
    <h1 class="text-2xl font-bold mb-4">Mis Reportes</h1>

    <?php if(session('success')): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <?php echo e(session('success')); ?>

        </div>
    <?php endif; ?>

    <!-- Formulario para generar reporte -->
    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
        <h3 class="font-semibold mb-3">Generar Nuevo Reporte</h3>
        <form action="<?php echo e(route('modulo2.generate-report')); ?>" method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <?php echo csrf_field(); ?>
            <select name="type" class="border rounded px-3 py-2" required>
                <option value="daily">Diario</option>
                <option value="weekly">Semanal</option>
                <option value="monthly">Mensual</option>
                <option value="custom">Personalizado</option>
            </select>
            <input type="date" name="date_from" class="border rounded px-3 py-2" placeholder="Desde">
            <input type="date" name="date_to" class="border rounded px-3 py-2" placeholder="Hasta">
            <button type="submit" class="bg-green-500 hover:bg-green-700 text-white px-4 py-2 rounded">
                Generar
            </button>
        </form>
    </div>

    <?php if($reports->count() > 0): ?>
        <div class="space-y-3">
            <?php $__currentLoopData = $reports; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $report): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="p-4 bg-white rounded-lg border shadow-sm">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <h4 class="font-semibold"><?php echo e($report->title); ?></h4>
                                <span class="px-2 py-1 text-xs rounded 
                                    <?php if($report->status === 'generated'): ?> bg-green-100 text-green-800
                                    <?php elseif($report->status === 'viewed'): ?> bg-gray-100 text-gray-800
                                    <?php else: ?> bg-yellow-100 text-yellow-800 <?php endif; ?>">
                                    <?php echo e(strtoupper($report->status)); ?>

                                </span>
                                <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800">
                                    <?php echo e(strtoupper($report->type)); ?>

                                </span>
                            </div>
                            <?php if($report->description): ?>
                                <p class="text-sm text-gray-600"><?php echo e($report->description); ?></p>
                            <?php endif; ?>
                            <p class="text-xs text-gray-400 mt-1">
                                Generado: <?php echo e($report->generated_at ? $report->generated_at->format('d/m/Y H:i') : 'Pendiente'); ?>

                            </p>
                            <?php if(isset($report->data['total_detections'])): ?>
                                <p class="text-xs text-gray-500 mt-1">
                                    Total detecciones: <?php echo e($report->data['total_detections']); ?>

                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <div class="mt-6">
            <?php echo e($reports->links()); ?>

        </div>
    <?php else: ?>
        <p class="text-gray-500">No tienes reportes generados</p>
    <?php endif; ?>

    <div class="mt-6">
        <a href="<?php echo e(route('modulo2')); ?>" class="bg-gray-500 hover:bg-gray-700 text-white px-4 py-2 rounded">
            Volver a Reportes
        </a>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\ProyectoFinal\polysense\resources\views/modulo2/my-reports.blade.php ENDPATH**/ ?>