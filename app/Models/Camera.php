<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Camera extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'url',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relación con detecciones
     */
    public function detections(): HasMany
    {
        return $this->hasMany(Detection::class);
    }

    /**
     * Scope para cámaras activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Obtener cámara por código
     */
    public static function findByCode(string $code): ?self
    {
        return self::where('code', $code)->first();
    }
}
