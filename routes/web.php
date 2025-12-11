<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\VehicleMonitorController;
use App\Http\Controllers\VoiceCommandController;

Route::get('/', function () {
    return view('modulo1');
});

Route::get('/modulo1', function() {
    return view('modulo1');
})->name('modulo1');

Route::get('/modulo2', [ReporteController::class, 'index'])->name('modulo2');

Route::get('/modulo4', function() {
    return view('modulo4');
})->name('modulo4');

Route::get('/login', function() {
    return view('login');
})->name('login');

Route::get('/register', function() {
    return view('register');
})->name('register');

// API para obtener datos del detector Python
Route::get('/api/vehicle-monitor/{cameraId}', [VehicleMonitorController::class, 'getStats']);

Route::get('/modulo2/exportar/excel', [ReporteController::class, 'exportarExcel'])->name('exportar.excel');

// API para comandos de voz
Route::prefix('api/voice-commands')->group(function () {
    Route::get('/', [VoiceCommandController::class, 'index']);
    Route::get('/active/{module?}', [VoiceCommandController::class, 'getActiveCommands']);
    Route::post('/', [VoiceCommandController::class, 'store']);
    Route::get('/{id}', [VoiceCommandController::class, 'show']);
    Route::put('/{id}', [VoiceCommandController::class, 'update']);
    Route::delete('/{id}', [VoiceCommandController::class, 'destroy']);
    Route::post('/{id}/toggle', [VoiceCommandController::class, 'toggle']);
    Route::post('/defaults', [VoiceCommandController::class, 'createDefaults']);
});
