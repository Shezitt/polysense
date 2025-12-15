<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\VehicleMonitorController;
use App\Http\Controllers\VoiceCommandController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;

// Rutas de autenticación
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Rutas públicas (o protégelas si quieres)
Route::get('/', function () {
    return view('modulo1');
});

Route::get('/modulo1', function() {
    return view('modulo1');
})->name('modulo1');

Route::get('/modulo2', [ReporteController::class, 'index'])->name('modulo2');

// Módulo 3 - Solo para administradores
Route::middleware(['isAdmin'])->group(function () {
    Route::get('/modulo3', [App\Http\Controllers\ModuloTresController::class, 'index'])->name('modulo3');
    Route::get('/modulo3/notifications', [App\Http\Controllers\ModuloTresController::class, 'notifications'])->name('modulo3.notifications');
    Route::get('/modulo3/reports', [App\Http\Controllers\ModuloTresController::class, 'reports'])->name('modulo3.reports');
});

Route::get('/modulo4', function() {
    return view('modulo4');
})->name('modulo4');

// API para obtener datos del detector Python
Route::get('/api/vehicle-monitor/{cameraId}', [VehicleMonitorController::class, 'getStats']);

Route::get('/modulo2/exportar/excel', [ReporteController::class, 'exportarExcel'])->name('exportar.excel');

// Módulo 5 - Gestión de usuarios (solo admin)
Route::middleware(['isAdmin'])->group(function () {
    Route::get('/modulo5', [UserController::class, 'index'])->name('modulo5');
    Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');
    Route::delete('/usuarios/{id}', [UserController::class, 'destroy'])->name('usuarios.destroy');
    Route::put('/usuarios/{id}/role', [UserController::class, 'changeRole'])->name('usuarios.changeRole');
});

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

// API para notificaciones y reportes - Solo para administradores
Route::middleware(['isAdmin'])->prefix('api')->group(function () {
    Route::get('/notifications', [App\Http\Controllers\NotificationController::class, 'index']);
    Route::get('/notifications/unread', [App\Http\Controllers\NotificationController::class, 'unread']);
    Route::post('/notifications/{id}/read', [App\Http\Controllers\NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all', [App\Http\Controllers\NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [App\Http\Controllers\NotificationController::class, 'destroy']);
    
    Route::get('/reports', [App\Http\Controllers\ReportController::class, 'index']);
    Route::post('/reports/generate', [App\Http\Controllers\ReportController::class, 'generate']);
    Route::get('/reports/{id}', [App\Http\Controllers\ReportController::class, 'show']);
    Route::delete('/reports/{id}', [App\Http\Controllers\ReportController::class, 'destroy']);
    Route::get('/reports/quick-stats', [App\Http\Controllers\ReportController::class, 'quickStats']);
});
