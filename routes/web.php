<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\VehicleMonitorController;
use App\Http\Controllers\VoiceCommandController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserPreferencesController;

// Rutas de autenticación
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::post('/preferences/header-position', [UserPreferencesController::class, 'updateHeaderPosition'])->name('preferences.header-position');
});

Route::get('/', function () {
    return view('modulo1');
});

Route::get('/modulo1', function() {
    return view('modulo1');
})->name('modulo1');

Route::prefix('modulo2')->group(function() {
    Route::get('/', [ReporteController::class, 'index'])->name('modulo2');
    Route::get('/my-notifications', [ReporteController::class, 'myNotifications'])->name('modulo2.my-notifications');
    Route::get('/my-reports', [ReporteController::class, 'myReports'])->name('modulo2.my-reports');
    Route::post('/generate-report', [ReporteController::class, 'generateMyReport'])->name('modulo2.generate-report');
});

Route::middleware(['isAdmin'])->prefix('modulo3')->group(function () {
    Route::get('/', [App\Http\Controllers\ModuloTresController::class, 'index'])->name('modulo3');
    Route::get('/user/{userId}/configure', [App\Http\Controllers\ModuloTresController::class, 'configureUser'])->name('modulo3.configure');
    Route::post('/user/{userId}/configure', [App\Http\Controllers\ModuloTresController::class, 'saveConfiguration'])->name('modulo3.save-config');
    Route::get('/notifications/all', [App\Http\Controllers\ModuloTresController::class, 'allNotifications'])->name('modulo3.all-notifications');
    Route::get('/reports/all', [App\Http\Controllers\ModuloTresController::class, 'allReports'])->name('modulo3.all-reports');
    Route::get('/xml-cleanup', [App\Http\Controllers\ModuloTresController::class, 'xmlCleanupConfig'])->name('modulo3.xml-cleanup');
    Route::post('/xml-cleanup', [App\Http\Controllers\ModuloTresController::class, 'saveXmlCleanupConfig'])->name('modulo3.save-xml-cleanup');
});

Route::get('/modulo4', function() {
    return view('modulo4');
})->name('modulo4');

Route::get('/api/vehicle-monitor/{cameraId}', [VehicleMonitorController::class, 'getStats']);

Route::get('/modulo2/exportar/excel', [ReporteController::class, 'exportarExcel'])->name('exportar.excel');

Route::middleware(['isAdmin'])->group(function () {
    Route::get('/modulo5', [UserController::class, 'index'])->name('modulo5');
    Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');
    Route::delete('/usuarios/{id}', [UserController::class, 'destroy'])->name('usuarios.destroy');
    Route::put('/usuarios/{id}/role', [UserController::class, 'changeRole'])->name('usuarios.changeRole');
});

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
