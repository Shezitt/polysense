<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage; // Necesario para leer archivos

class ReporteController extends Controller
{
    public function index()
    {
        // 1. LEER EL XML
        // Verificamos si existe el archivo, si no, array vacío
        $registros = [];
        if (Storage::exists('vehiculos_db.xml')) {
            $xmlContent = Storage::get('vehiculos_db.xml');
            $xml = simplexml_load_string($xmlContent);
            
            // Convertir XML a Array JSON para que sea fácil de usar en Blade
            foreach ($xml->deteccion as $det) {
                $registros[] = [
                    'fecha' => (string)$det->fecha,
                    'tipo'  => (string)$det->tipo,
                    'camara'=> (string)$det->camara,
                    'confianza' => (float)$det->confianza
                ];
            }
        }

        // Pasamos los datos a la vista
        return view('modulo2', compact('registros'));
    }

    // Funcionalidad del Botón: EXPORTAR A EXCEL (Simulado con CSV)
    public function exportarExcel()
    {
        // Aquí generarías el archivo real. 
        // Para este ejemplo, descargaremos un CSV simple.
        $filename = "reporte_vehiculos.csv";
        $handle = fopen('php://output', 'w');
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="'. $filename .'"');
        
        fputcsv($handle, ['Fecha', 'Tipo', 'Camara', 'Confianza']); // Cabeceras
        
        // Aquí leerías el XML de nuevo y escribirías las filas
        if (Storage::exists('vehiculos_db.xml')) {
            $xml = simplexml_load_string(Storage::get('vehiculos_db.xml'));
            foreach ($xml->deteccion as $det) {
                fputcsv($handle, [
                    $det->fecha, $det->tipo, $det->camara, $det->confianza
                ]);
            }
        }
        
        fclose($handle);
        exit;
    }
}