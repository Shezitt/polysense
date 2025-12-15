<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\User;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        // Obtener el primer admin
        $admin = User::where('role', 'admin')->first();
        
        if (!$admin) {
            $this->command->warn('No se encontró ningún usuario admin. Creando notificaciones para user_id=1');
            $userId = 1;
        } else {
            $userId = $admin->id;
        }

        // Crear notificaciones de ejemplo
        Notification::create([
            'user_id' => $userId,
            'title' => 'Bienvenido a Automatizaciones',
            'message' => 'Sistema de notificaciones y reportes configurado correctamente.',
            'type' => 'success',
            'priority' => 'low',
        ]);

        Notification::create([
            'user_id' => $userId,
            'title' => 'Alto Tráfico Detectado',
            'message' => 'Se detectaron 75 vehículos en los últimos 5 minutos.',
            'type' => 'warning',
            'priority' => 'high',
        ]);

        Notification::create([
            'user_id' => $userId,
            'title' => 'Cámara Posiblemente Offline',
            'message' => 'No se han detectado vehículos en los últimos 15 minutos.',
            'type' => 'alert',
            'priority' => 'high',
        ]);

        Notification::create([
            'user_id' => $userId,
            'title' => 'Sistema Actualizado',
            'message' => 'El sistema de detección vehicular ha sido actualizado a la versión 2.0.',
            'type' => 'info',
            'priority' => 'medium',
        ]);

        $this->command->info('Notificaciones de ejemplo creadas exitosamente.');
    }
}
