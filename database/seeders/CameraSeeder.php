<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Camera;

class CameraSeeder extends Seeder
{
    public function run(): void
    {
        $cameras = [
            [
                'code' => 'CAM_001',
                'name' => 'Oracle Server',
                'type' => 'websocket',
                'url' => 'ws://144.22.56.85:5000/ws/CAM_001',
                'is_active' => true,
            ],
            [
                'code' => 'CAM_002',
                'name' => 'Skyline Cochabamba',
                'type' => 'skyline',
                'url' => 'https://www.skylinewebcams.com/es/webcam/bolivia/cercado/cochabamba/plaza-14-de-septiembre.html',
                'is_active' => true,
            ],
        ];

        foreach ($cameras as $camera) {
            Camera::updateOrCreate(
                ['code' => $camera['code']],
                $camera
            );
        }

        $this->command->info('Cámaras creadas/actualizadas exitosamente.');
    }
}
