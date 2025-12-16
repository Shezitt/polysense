<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VoiceCommandSeeder extends Seeder
{
    public function run(): void
    {
        $commands = [
            [
                'name' => 'Ir a Deteccion',
                'trigger' => 'detección,deteccion,ir al monitor,monitoreo,módulo uno,módulo 1',
                'action' => 'navigate',
                'target' => '/modulo1',
                'function_name' => null,
                'modules' => 'all',
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ir a Reportes',
                'trigger' => 'reportes,ir al historial,estadísticas,módulo dos,módulo 2',
                'action' => 'navigate',
                'target' => '/modulo2',
                'function_name' => null,
                'modules' => 'all',
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ir a Automatizaciones',
                'trigger' => 'automatizaciones,automatización,configurar voz,módulo tres,módulo 3',
                'action' => 'navigate',
                'target' => '/modulo3',
                'function_name' => null,
                'modules' => 'all',
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ir a Accesibilidad',
                'trigger' => 'accesibilidad,comandos de voz,módulo cuatro,módulo 4',
                'action' => 'navigate',
                'target' => '/modulo4',
                'function_name' => null,
                'modules' => 'all',
                'enabled' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ir a Gestion',
                'trigger' => 'gestión,gestion,usuarios,módulo cinco,módulo 5',
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
            ]
        ];

        DB::table('voice_commands')->insert($commands);
    }
}
