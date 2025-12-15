<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Facades\ReportFacade as Report;

class ReportController extends Controller
{
    /**
     * Listar reportes del usuario con filtros
     */
    public function index(Request $request)
    {
        $filters = $request->only(['type', 'status', 'date_from', 'date_to']);
        $reports = Report::getHistory(auth()->id(), $filters);

        return response()->json($reports);
    }

    /**
     * Generar nuevo reporte
     */
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:daily,weekly,monthly,custom',
            'filters' => 'sometimes|array',
            'filters.date_from' => 'sometimes|date',
            'filters.date_to' => 'sometimes|date',
            'filters.type' => 'sometimes|string',
        ]);

        try {
            $report = Report::generate(
                auth()->id(),
                $validated['type'],
                $validated['filters'] ?? []
            );

            return response()->json([
                'success' => true,
                'report' => $report,
                'message' => 'Reporte generado exitosamente',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar reporte: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ver reporte específico
     */
    public function show($id)
    {
        try {
            $report = Report::view($id);
            return response()->json([
                'success' => true,
                'report' => $report,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Reporte no encontrado',
            ], 404);
        }
    }

    /**
     * Eliminar reporte
     */
    public function destroy($id)
    {
        try {
            Report::delete($id);
            return response()->json([
                'success' => true,
                'message' => 'Reporte eliminado',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Reporte no encontrado',
            ], 404);
        }
    }

    /**
     * Obtener estadísticas rápidas sin crear reporte
     */
    public function quickStats(Request $request)
    {
        $filters = $request->only(['date_from', 'date_to', 'type']);
        $stats = Report::getQuickStats($filters);

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }
}
