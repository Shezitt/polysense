<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Detection extends Model
{
    protected $fillable = [
        'camera_id',
        'camera_code',
        'camera_name',
        'vehicle_type',
        'color',
        'confidence',
        'detected_at',
        'detection_date',
        'detection_hour',
    ];

    protected $casts = [
        'detected_at' => 'datetime',
        'detection_date' => 'date',
        'confidence' => 'decimal:2',
        'detection_hour' => 'integer',
    ];

    /**
     * Relación con cámara
     */
    public function camera(): BelongsTo
    {
        return $this->belongsTo(Camera::class);
    }

    /**
     * Scope para filtrar por cámara
     */
    public function scopeForCamera($query, string $cameraCode)
    {
        return $query->where('camera_code', $cameraCode);
    }

    /**
     * Scope para filtrar por tipo de vehículo
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('vehicle_type', $type);
    }

    /**
     * Scope para filtrar por fecha
     */
    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('detection_date', $date);
    }

    /**
     * Scope para filtrar por rango de fechas
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('detection_date', [$startDate, $endDate]);
    }

    /**
     * Scope para filtrar por hora
     */
    public function scopeAtHour($query, int $hour)
    {
        return $query->where('detection_hour', $hour);
    }

    /**
     * Obtener estadísticas por tipo
     */
    public static function getStatsByType(array $filters = []): array
    {
        $query = self::query();

        if (isset($filters['camera_code'])) {
            $query->forCamera($filters['camera_code']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->betweenDates($filters['start_date'], $filters['end_date']);
        }

        return $query->selectRaw('vehicle_type, COUNT(*) as count')
            ->groupBy('vehicle_type')
            ->pluck('count', 'vehicle_type')
            ->toArray();
    }

    /**
     * Obtener estadísticas por hora
     */
    public static function getStatsByHour(array $filters = []): array
    {
        $query = self::query();

        if (isset($filters['camera_code'])) {
            $query->forCamera($filters['camera_code']);
        }

        if (isset($filters['date'])) {
            $query->onDate($filters['date']);
        }

        return $query->selectRaw('detection_hour, COUNT(*) as count')
            ->groupBy('detection_hour')
            ->orderBy('detection_hour')
            ->pluck('count', 'detection_hour')
            ->toArray();
    }
}
