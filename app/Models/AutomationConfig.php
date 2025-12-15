<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationConfig extends Model
{
    protected $fillable = [
        'user_id',
        'traffic_threshold',
        'traffic_minutes',
        'notify_high_traffic',
        'notify_camera_offline',
        'camera_offline_minutes',
        'report_frequency',
        'report_generation_time',
        'auto_generate_reports',
        'xml_cleanup_frequency',
        'xml_retention_days',
    ];

    protected $casts = [
        'notify_high_traffic' => 'boolean',
        'notify_camera_offline' => 'boolean',
        'auto_generate_reports' => 'boolean',
        'report_generation_time' => 'datetime:H:i:s',
    ];

    /**
     * Relación con usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtener o crear configuración para un usuario
     */
    public static function getOrCreateForUser(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId],
            [
                'traffic_threshold' => 50,
                'traffic_minutes' => 5,
                'notify_high_traffic' => true,
                'notify_camera_offline' => true,
                'camera_offline_minutes' => 10,
                'report_frequency' => 'disabled',
                'report_generation_time' => '08:00:00',
                'auto_generate_reports' => false,
            ]
        );
    }
}
