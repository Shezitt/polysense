<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Report;
use App\Models\User;

class ReportSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();
        
        if (!$admin) {
            $userId = 1;
        } else {
            $userId = $admin->id;
        }

        Report::create([
            'user_id' => $userId,
            'title' => 'Reporte Diario de Detecciones',
            'description' => 'Reporte generado el ' . now()->format('d/m/Y H:i'),
            'type' => 'daily',
            'status' => 'generated',
            'report_date' => now()->toDateString(),
            'data' => [
                'total_detections' => 150,
                'by_type' => [
                    'auto' => 80,
                    'camion' => 40,
                    'moto' => 30,
                ],
                'by_hour' => [
                    '08' => 20,
                    '09' => 35,
                    '10' => 25,
                    '14' => 30,
                    '18' => 40,
                ],
                'average_confidence' => 87.5,
                'peak_hour' => '18',
                'busiest_day' => now()->toDateString(),
            ],
            'filters' => [],
            'generated_at' => now(),
        ]);

        Report::create([
            'user_id' => $userId,
            'title' => 'Reporte Semanal de Detecciones',
            'description' => 'Reporte de la última semana',
            'type' => 'weekly',
            'status' => 'generated',
            'report_date' => now()->toDateString(),
            'data' => [
                'total_detections' => 1050,
                'by_type' => [
                    'auto' => 560,
                    'camion' => 280,
                    'moto' => 210,
                ],
                'average_confidence' => 85.2,
                'peak_hour' => '17',
            ],
            'filters' => [
                'date_from' => now()->subWeek()->toDateString(),
                'date_to' => now()->toDateString(),
            ],
            'generated_at' => now()->subDay(),
        ]);

    }
}
