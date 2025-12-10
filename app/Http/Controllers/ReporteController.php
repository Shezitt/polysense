<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        // 1. LEER EL XML
        // Verificamos si existe el archivo, si no, array vacío
        $registros = [];
        $xmlPath = storage_path('app/vehiculos_db.xml');
        
        if (file_exists($xmlPath)) {
            $xmlContent = file_get_contents($xmlPath);
            $xml = simplexml_load_string($xmlContent);
            
            // Convertir XML a Array JSON para que sea fácil de usar en Blade
            foreach ($xml->deteccion as $det) {
                $registro = [
                    'fecha' => (string)$det->fecha,
                    'tipo'  => (string)$det->tipo,
                    'camara'=> (string)$det->camara,
                    'confianza' => (float)$det->confianza,
                    'color' => isset($det->color) ? (string)$det->color : 'desconocido'
                ];
                
                // Aplicar filtros si existen
                $incluir = true;
                
                // Filtrar por tipo
                if ($request->has('tipo') && $request->tipo != '') {
                    if ($registro['tipo'] != $request->tipo) {
                        $incluir = false;
                    }
                }
                
                // Filtrar por fecha
                if ($request->has('fecha_inicio') && $request->fecha_inicio != '') {
                    $fechaRegistro = strtotime($registro['fecha']);
                    $fechaInicio = strtotime($request->fecha_inicio . ' 00:00:00');
                    if ($fechaRegistro < $fechaInicio) {
                        $incluir = false;
                    }
                }
                
                if ($request->has('fecha_fin') && $request->fecha_fin != '') {
                    $fechaRegistro = strtotime($registro['fecha']);
                    $fechaFin = strtotime($request->fecha_fin . ' 23:59:59');
                    if ($fechaRegistro > $fechaFin) {
                        $incluir = false;
                    }
                }
                
                if ($incluir) {
                    $registros[] = $registro;
                }
            }
        }

        // Ordenar por fecha descendente (más reciente primero)
        usort($registros, function($a, $b) {
            return strtotime($b['fecha']) - strtotime($a['fecha']);
        });

        // Pasamos los datos a la vista
        return view('modulo2', compact('registros'));
    }

    // Funcionalidad del Botón: EXPORTAR A EXCEL (Simulado con CSV)
    public function exportarExcel()
    {
        // Aquí generarías el archivo real. 
        // Para este ejemplo, descargaremos un CSV simple.
        $filename = "reporte_vehiculos_" . date('Y-m-d_His') . ".csv";
        $handle = fopen('php://output', 'w');
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'. $filename .'"');
        
        // BOM para UTF-8
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        
        fputcsv($handle, ['Fecha', 'Tipo', 'Color', 'Camara', 'Confianza (%)']); // Cabeceras
        
        // Aquí leerías el XML de nuevo y escribirías las filas
        $xmlPath = storage_path('app/vehiculos_db.xml');
        if (file_exists($xmlPath)) {
            $xml = simplexml_load_string(file_get_contents($xmlPath));
            foreach ($xml->deteccion as $det) {
                fputcsv($handle, [
                    $det->fecha, 
                    $det->tipo, 
                    isset($det->color) ? $det->color : 'desconocido',
                    $det->camara, 
                    $det->confianza
                ]);
            }
        }
        
        fclose($handle);
        exit;
    }
}