<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\VehicleMonitorController;

// Ruta principal para la página de monitoreo
Route::get('/monitor', [VehicleMonitorController::class, 'index']);

// APIs para obtener datos
Route::get('/api/vehicle-monitor/stats', [VehicleMonitorController::class, 'getAllStats']);
Route::get('/api/vehicle-monitor/{cameraId}', [VehicleMonitorController::class, 'getStats']);