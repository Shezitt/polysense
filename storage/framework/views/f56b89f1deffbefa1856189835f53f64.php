<?php if(auth()->guard()->check()): ?>
<div class="relative">
    <button 
        onclick="toggleHeaderPositionMenu()" 
        class="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:text-indigo-600 transition-colors"
        title="Cambiar posición del header"
        data-current-position="<?php echo e(Auth::user()->header_position ?? 'top'); ?>"
    >
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
        </svg>
        <span class="hidden md:inline">Posición</span>
    </button>
    
    <?php
        $headerPosition = Auth::user()->header_position ?? 'top';
    ?>
    
    <div 
        id="headerPositionMenu" 
        class="hidden fixed bg-white rounded-lg shadow-xl border border-gray-200 z-50"
        style="width: 320px;"
    >
        <div class="p-5">
            <h3 class="text-sm font-semibold text-gray-900 mb-4">Posición del Header</h3>
            <div class="grid grid-cols-2 gap-4">
                <!-- Top -->
                <button 
                    onclick="changeHeaderPosition('top')" 
                    class="flex flex-col items-center justify-center p-5 rounded-lg border-2 transition-all cursor-pointer <?php echo e($headerPosition === 'top' ? 'border-indigo-600 bg-indigo-100 text-indigo-700' : 'border-gray-200 hover:border-indigo-400 hover:bg-indigo-50'); ?>"
                    style="min-height: 110px;"
                >
                    <svg class="w-10 h-10 mb-3 <?php echo e($headerPosition === 'top' ? 'text-indigo-600' : 'text-gray-600'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <rect x="3" y="3" width="18" height="4" stroke-width="2" rx="1"></rect>
                        <rect x="3" y="9" width="18" height="12" stroke-width="1.5" rx="1" opacity="0.3"></rect>
                    </svg>
                    <span class="text-sm font-semibold">Arriba</span>
                </button>
                
                <!-- Bottom -->
                <button 
                    onclick="changeHeaderPosition('bottom')" 
                    class="flex flex-col items-center justify-center p-5 rounded-lg border-2 transition-all cursor-pointer <?php echo e($headerPosition === 'bottom' ? 'border-indigo-600 bg-indigo-100 text-indigo-700' : 'border-gray-200 hover:border-indigo-400 hover:bg-indigo-50'); ?>"
                    style="min-height: 110px;"
                >
                    <svg class="w-10 h-10 mb-3 <?php echo e($headerPosition === 'bottom' ? 'text-indigo-600' : 'text-gray-600'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <rect x="3" y="3" width="18" height="12" stroke-width="1.5" rx="1" opacity="0.3"></rect>
                        <rect x="3" y="17" width="18" height="4" stroke-width="2" rx="1"></rect>
                    </svg>
                    <span class="text-sm font-semibold">Abajo</span>
                </button>
                
                <!-- Left -->
                <button 
                    onclick="changeHeaderPosition('left')" 
                    class="flex flex-col items-center justify-center p-5 rounded-lg border-2 transition-all cursor-pointer <?php echo e($headerPosition === 'left' ? 'border-indigo-600 bg-indigo-100 text-indigo-700' : 'border-gray-200 hover:border-indigo-400 hover:bg-indigo-50'); ?>"
                    style="min-height: 110px;"
                >
                    <svg class="w-10 h-10 mb-3 <?php echo e($headerPosition === 'left' ? 'text-indigo-600' : 'text-gray-600'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <rect x="3" y="3" width="4" height="18" stroke-width="2" rx="1"></rect>
                        <rect x="9" y="3" width="12" height="18" stroke-width="1.5" rx="1" opacity="0.3"></rect>
                    </svg>
                    <span class="text-sm font-semibold">Izquierda</span>
                </button>
                
                <!-- Right -->
                <button 
                    onclick="changeHeaderPosition('right')" 
                    class="flex flex-col items-center justify-center p-5 rounded-lg border-2 transition-all cursor-pointer <?php echo e($headerPosition === 'right' ? 'border-indigo-600 bg-indigo-100 text-indigo-700' : 'border-gray-200 hover:border-indigo-400 hover:bg-indigo-50'); ?>"
                    style="min-height: 110px;"
                >
                    <svg class="w-10 h-10 mb-3 <?php echo e($headerPosition === 'right' ? 'text-indigo-600' : 'text-gray-600'); ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <rect x="3" y="3" width="12" height="18" stroke-width="1.5" rx="1" opacity="0.3"></rect>
                        <rect x="17" y="3" width="4" height="18" stroke-width="2" rx="1"></rect>
                    </svg>
                    <span class="text-sm font-semibold">Derecha</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function toggleHeaderPositionMenu() {
    const menu = document.getElementById('headerPositionMenu');
    const btn = document.querySelector('button[onclick="toggleHeaderPositionMenu()"]');
    const position = btn.dataset.currentPosition || 'top';
    const menuWidth = 320;
    
    if (menu.classList.contains('hidden')) {
        menu.classList.remove('hidden');
        
        // Reset positioning styles
        menu.style.top = ''; 
        menu.style.bottom = ''; 
        menu.style.left = ''; 
        menu.style.right = '';
        
        const rect = btn.getBoundingClientRect();
        
        if (position === 'left') {
            // Position to the right of the sidebar button, aligning bottom
            menu.style.left = (rect.right + 10) + 'px';
            menu.style.bottom = (window.innerHeight - rect.bottom) + 'px';
        } else if (position === 'right') {
            // Position to the left of the sidebar button, aligning bottom
            menu.style.left = (rect.left - menuWidth - 10) + 'px';
            menu.style.bottom = (window.innerHeight - rect.bottom) + 'px';
        } else if (position === 'bottom') {
            // Position above the button, aligned right to match trigger area
            menu.style.bottom = (window.innerHeight - rect.top + 10) + 'px';
            menu.style.left = (rect.right - menuWidth) + 'px';
        } else { // top position (default)
            // Position below the button, aligned right
            menu.style.top = (rect.bottom + 10) + 'px';
            menu.style.left = (rect.right - menuWidth) + 'px';
        }
    } else {
        menu.classList.add('hidden');
    }
}

// Close menu when clicking outside
document.addEventListener('click', function(event) {
    const menu = document.getElementById('headerPositionMenu');
    const button = event.target.closest('button[onclick="toggleHeaderPositionMenu()"]');
    
    if (!button && !menu?.contains(event.target)) {
        menu?.classList.add('hidden');
    }
});

// Update position on resize if open (optional but nice)
window.addEventListener('resize', function() {
    const menu = document.getElementById('headerPositionMenu');
    if (!menu.classList.contains('hidden')) {
        // Toggle off to reset or re-calculate. Simplest is close it.
        menu.classList.add('hidden');
    }
});

async function changeHeaderPosition(position) {
    try {
        const response = await fetch('<?php echo e(route("preferences.header-position")); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ position })
        });

        const data = await response.json();

        if (data.success) {
            window.location.reload();
        } else {
            console.error('Failed to change position');
        }
    } catch (error) {
        console.error('Error:', error);
    }
}
</script>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\ProyectoFinal\polysense\resources\views/components/header-position-selector.blade.php ENDPATH**/ ?>