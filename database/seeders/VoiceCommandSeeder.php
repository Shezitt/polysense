<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VoiceCommandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $commands = [
            [
                'name' => 'Ir al Módulo 1',
                'trigger' => 'módulo uno,ir al monitor,monitoreo,módulo 1',
                'action' => 'navigate',
                'target' => '/modulo1',
                'function_name' => null,
                'modules' => 'all',
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ir al Módulo 2',
                'trigger' => 'módulo dos,ir al historial,estadísticas,módulo 2',
                'action' => 'navigate',
                'target' => '/modulo2',
                'function_name' => null,
                'modules' => 'all',
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ir al Módulo 3',
                'trigger' => 'módulo tres,configurar voz,comandos de voz,módulo 3',
                'action' => 'navigate',
                'target' => '/modulo3',
                'function_name' => null,
                'modules' => 'all',
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ir al Módulo 4',
                'trigger' => 'módulo cuatro,módulo 4,ir al módulo cuatro',
                'action' => 'navigate',
                'target' => '/modulo4',
                'function_name' => null,
                'modules' => 'all',
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ir al Módulo 5',
                'trigger' => 'módulo cinco,módulo 5,ir al módulo cinco',
                'action' => 'navigate',
                'target' => '/modulo5',
                'function_name' => null,
                'modules' => 'all',
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ir al Inicio',
                'trigger' => 'inicio,página principal,home,ir al inicio',
                'action' => 'navigate',
                'target' => '/',
                'function_name' => null,
                'modules' => 'all',
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Exportar a Excel',
                'trigger' => 'exportar,descargar excel,guardar datos,exportar datos',
                'action' => 'export',
                'target' => null,
                'function_name' => 'exportToExcel',
                'modules' => 'modulo2',
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Detener Reconocimiento',
                'trigger' => 'detener,parar,stop,desactivar voz',
                'action' => 'custom',
                'target' => null,
                'function_name' => 'stopVoiceRecognition',
                'modules' => 'all',
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('voice_commands')->insert($commands);
    }
}
