<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ReporteController extends Controller
{
    /**
     * Obtiene y filtra los registros del XML
     */
    private function obtenerRegistrosFiltrados(Request $request)
    {
        $registros = [];
        $xmlPath = storage_path('app/vehiculos_db.xml');
        
        if (file_exists($xmlPath)) {
            $xmlContent = file_get_contents($xmlPath);
            $xml = simplexml_load_string($xmlContent);
            
            // Convertir XML a Array y aplicar filtros
            foreach ($xml->deteccion as $det) {
                $registro = [
                    'fecha' => (string)$det->fecha,
                    'tipo'  => (string)$det->tipo,
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
                
                // Filtrar por fecha inicio
                if ($request->has('fecha_inicio') && $request->fecha_inicio != '') {
                    $fechaRegistro = strtotime($registro['fecha']);
                    $fechaInicio = strtotime($request->fecha_inicio . ' 00:00:00');
                    if ($fechaRegistro < $fechaInicio) {
                        $incluir = false;
                    }
                }
                
                // Filtrar por fecha fin
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

        return $registros;
    }

    public function index(Request $request)
    {
        $registros = $this->obtenerRegistrosFiltrados($request);
        return view('modulo2', compact('registros'));
    }

    // Funcionalidad del Botón: EXPORTAR A EXCEL (Simulado con CSV)
    public function exportarExcel(Request $request)
    {
        // Obtener registros filtrados usando la misma lógica que index()
        $registros = $this->obtenerRegistrosFiltrados($request);
        
        // Generar nombre de archivo con información de filtros
        $filename = "reporte_vehiculos_" . date('Y-m-d_His');
        if ($request->has('tipo') && $request->tipo != '') {
            $filename .= "_" . strtolower($request->tipo);
        }
        if ($request->has('fecha_inicio') && $request->fecha_inicio != '') {
            $filename .= "_desde_" . $request->fecha_inicio;
        }
        if ($request->has('fecha_fin') && $request->fecha_fin != '') {
            $filename .= "_hasta_" . $request->fecha_fin;
        }
        $filename .= ".csv";
        
        $handle = fopen('php://output', 'w');
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="'. $filename .'"');
        
        // BOM para UTF-8
        fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Cabeceras
        fputcsv($handle, ['Fecha', 'Tipo', 'Color', 'Confianza (%)']);
        
        // Escribir solo los registros filtrados
        foreach ($registros as $registro) {
            fputcsv($handle, [
                $registro['fecha'], 
                $registro['tipo'], 
                $registro['color'],
                $registro['confianza']
            ]);
        }
        
        fclose($handle);
        exit;
    }
}