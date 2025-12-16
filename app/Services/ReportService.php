<?php

namespace App\Services;

use App\Models\Report;
use Illuminate\Pagination\LengthAwarePaginator;

class ReportService
{
    public function generate(int $userId, string $type, array $filters = []): Report
    {
        $data = $this->generateReportData($filters);

        return Report::create([
            'user_id' => $userId,
            'title' => $this->getReportTitle($type),
            'description' => $this->getReportDescription($type, $filters),
            'type' => $type,
            'status' => 'generated',
            'report_date' => now()->toDateString(),
            'data' => $data,
            'filters' => $filters,
            'generated_at' => now(),
        ]);
    }

    private function generateReportData(array $filters): array
    {
        $xmlPath = storage_path('app/vehiculos_db.xml');
        if (!file_exists($xmlPath)) {
            return [
                'error' => 'No hay datos disponibles',
                'total_detections' => 0,
            ];
        }

        $xml = simplexml_load_file($xmlPath);
        $stats = [
            'total_detections' => 0,
            'by_type' => [],
            'by_hour' => [],
            'by_day' => [],
            'by_color' => [],
            'average_confidence' => 0,
            'peak_hour' => null,
            'busiest_day' => null,
        ];

        $totalConfidence = 0;

        foreach ($xml->deteccion as $det) {
            $fecha = (string)$det->fecha;
            $tipo = (string)$det->tipo;
            $confianza = (float)$det->confianza;
            $color = isset($det->color) ? (string)$det->color : 'desconocido';

            if (isset($filters['date_from']) && $fecha < $filters['date_from']) continue;
            if (isset($filters['date_to']) && $fecha > $filters['date_to']) continue;
            if (isset($filters['type']) && $tipo != $filters['type']) continue;

            $stats['total_detections']++;

            if (!isset($stats['by_type'][$tipo])) {
                $stats['by_type'][$tipo] = 0;
            }
            $stats['by_type'][$tipo]++;

            $hour = date('H', strtotime($fecha));
            if (!isset($stats['by_hour'][$hour])) {
                $stats['by_hour'][$hour] = 0;
            }
            $stats['by_hour'][$hour]++;

            $day = date('Y-m-d', strtotime($fecha));
            if (!isset($stats['by_day'][$day])) {
                $stats['by_day'][$day] = 0;
            }
            $stats['by_day'][$day]++;

            if (!isset($stats['by_color'][$color])) {
                $stats['by_color'][$color] = 0;
            }
            $stats['by_color'][$color]++;

            $totalConfidence += $confianza;
        }

        if ($stats['total_detections'] > 0) {
            $stats['average_confidence'] = round($totalConfidence / $stats['total_detections'], 2);
        }

        if (!empty($stats['by_hour'])) {
            arsort($stats['by_hour']);
            $stats['peak_hour'] = array_key_first($stats['by_hour']);
        }

        if (!empty($stats['by_day'])) {
            arsort($stats['by_day']);
            $stats['busiest_day'] = array_key_first($stats['by_day']);
        }

        return $stats;
    }

    private function getReportTitle(string $type): string
    {
        $titles = [
            'daily' => 'Reporte Diario de Detecciones',
            'weekly' => 'Reporte Semanal de Detecciones',
            'monthly' => 'Reporte Mensual de Detecciones',
            'custom' => 'Reporte Personalizado',
        ];

        return $titles[$type] ?? 'Reporte de Detecciones';
    }

    private function getReportDescription(string $type, array $filters): string
    {
        $desc = "Reporte generado el " . now()->format('d/m/Y H:i');

        if (isset($filters['date_from']) || isset($filters['date_to'])) {
            $desc .= " - Período: ";
            $desc .= $filters['date_from'] ?? 'inicio';
            $desc .= " a ";
            $desc .= $filters['date_to'] ?? 'hoy';
        }

        if (isset($filters['type'])) {
            $desc .= " - Tipo: " . $filters['type'];
        }

        return $desc;
    }

    public function getHistory(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Report::forUser($userId);

        if (isset($filters['type']) && !empty($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (isset($filters['status']) && !empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from']) && !empty($filters['date_from'])) {
            $query->whereDate('report_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && !empty($filters['date_to'])) {
            $query->whereDate('report_date', '<=', $filters['date_to']);
        }

        return $query->latest()->paginate(20);
    }

    public function view(int $reportId): Report
    {
        $report = Report::findOrFail($reportId);
        
        if (!$report->hasBeenViewed()) {
            $report->markAsViewed();
        }

        return $report->fresh();
    }

    public function delete(int $reportId): bool
    {
        $report = Report::findOrFail($reportId);
        return $report->delete();
    }

    public function generateDailyReportForAdmins(): int
    {
        $admins = \App\Models\User::where('role', 'admin')->get();
        $count = 0;

        $filters = [
            'date_from' => now()->subDay()->toDateString(),
            'date_to' => now()->toDateString(),
        ];

        foreach ($admins as $admin) {
            $this->generate($admin->id, 'daily', $filters);
            $count++;
        }

        return $count;
    }

    public function getQuickStats(array $filters = []): array
    {
        return $this->generateReportData($filters);
    }
}
