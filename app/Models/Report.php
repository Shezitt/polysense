<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'type',
        'status',
        'report_date',
        'data',
        'filters',
        'generated_at',
        'viewed_at',
    ];

    protected $casts = [
        'report_date' => 'date',
        'generated_at' => 'datetime',
        'viewed_at' => 'datetime',
        'data' => 'array',
        'filters' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope para filtrar por usuario
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para obtener solo reportes generados
     */
    public function scopeGenerated($query)
    {
        return $query->where('status', 'generated');
    }

    /**
     * Scope para obtener solo reportes vistos
     */
    public function scopeViewed($query)
    {
        return $query->where('status', 'viewed');
    }

    /**
     * Scope para filtrar por tipo
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Marcar reporte como visto
     */
    public function markAsViewed(): void
    {
        $this->update([
            'status' => 'viewed',
            'viewed_at' => now(),
        ]);
    }

    /**
     * Verificar si el reporte ya fue visto
     */
    public function hasBeenViewed(): bool
    {
        return $this->status === 'viewed';
    }

    /**
     * Obtener el total de detecciones del reporte
     */
    public function getTotalDetections(): int
    {
        return $this->data['total_detections'] ?? 0;
    }

    /**
     * Obtener detecciones por tipo
     */
    public function getDetectionsByType(): array
    {
        return $this->data['by_type'] ?? [];
    }
}
