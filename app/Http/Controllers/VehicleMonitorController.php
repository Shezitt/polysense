<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VehicleMonitorController extends Controller
{
    private $localDetectorUrl = 'http://localhost:8080';

    /**
     * Mostrar la página de monitoreo
     */
    public function index()
    {
        return view('vehicle-monitor');
    }

    /**
     * API: Obtener estadísticas de una cámara
     */
    public function getStats($cameraId)
    {
        try {
            $response = Http::timeout(5)->get("{$this->pythonServerUrl}/api/vehicles/{$cameraId}");
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            return response()->json(['error' => 'No se pudo conectar con el servidor'], 500);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Obtener todas las estadísticas
     */
    public function getAllStats()
    {
        try {
            $response = Http::timeout(5)->get("{$this->pythonServerUrl}/stats");
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            return response()->json(['error' => 'No se pudo conectar con el servidor'], 500);
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}