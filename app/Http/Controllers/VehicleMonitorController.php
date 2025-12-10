<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VehicleMonitorController extends Controller
{
    private $detectorUrl = 'http://localhost:8080';

    /**
     * Obtiene estadísticas del detector Python
     */
    public function getStats($cameraId = 'camera_01')
    {
        try {
            $response = Http::timeout(5)->get("{$this->detectorUrl}/api/vehicles");
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            return response()->json([
                'error' => 'No se pudo conectar con el detector',
                'current_vehicles' => 0,
                'total_detected' => 0,
                'fps' => 0,
                'avg_vehicles' => 0,
                'vehicle_types' => [],
                'vehicle_colors' => [],
                'history' => []
            ], 503);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error de conexión: ' . $e->getMessage(),
                'current_vehicles' => 0,
                'total_detected' => 0,
                'fps' => 0,
                'avg_vehicles' => 0,
                'vehicle_types' => [],
                'vehicle_colors' => [],
                'history' => []
            ], 500);
        }
    }
}
