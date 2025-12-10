<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\VehicleMonitorController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/modulo1', function() {
    return view('modulo1');
})->name('modulo1');

Route::get('/modulo2', [ReporteController::class, 'index'])->name('modulo2');

Route::get('/login', function() {
    return view('login');
})->name('login');

Route::get('/register', function() {
    return view('register');
})->name('register');

// API para obtener datos del detector Python
Route::get('/api/vehicle-monitor/{cameraId}', [VehicleMonitorController::class, 'getStats']);

Route::get('/modulo2/exportar/excel', [ReporteController::class, 'exportarExcel'])->name('exportar.excel');
