<?php

namespace App\Services;

use App\Models\Notification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Crear una nueva notificación
     */
    public function create(
        int $userId,
        string $title,
        string $message,
        string $type = 'info',
        string $priority = 'medium',
        array $metadata = []
    ): Notification {
        return Notification::create([
            'user_id' => $userId,
            'title' => $title,
            'message' => $message,
            'type' => $type,
            'priority' => $priority,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Obtener notificaciones no leídas de un usuario
     */
    public function getUnread(int $userId): Collection
    {
        return Notification::forUser($userId)
            ->unread()
            ->latest()
            ->get();
    }

    /**
     * Obtener contador de notificaciones no leídas
     */
    public function getUnreadCount(int $userId): int
    {
        return Notification::forUser($userId)
            ->unread()
            ->count();
    }

    /**
     * Marcar notificación como leída
     */
    public function markAsRead(int $notificationId): Notification
    {
        $notification = Notification::findOrFail($notificationId);
        $notification->markAsRead();
        return $notification->fresh();
    }

    /**
     * Marcar todas las notificaciones de un usuario como leídas
     */
    public function markAllAsRead(int $userId): int
    {
        return Notification::forUser($userId)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    /**
     * Obtener historial de notificaciones con filtros
     */
    public function getHistory(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Notification::forUser($userId);

        // Filtrar por tipo
        if (isset($filters['type']) && !empty($filters['type'])) {
            $query->ofType($filters['type']);
        }

        // Filtrar por estado de lectura
        if (isset($filters['is_read'])) {
            if ($filters['is_read'] === 'true' || $filters['is_read'] === true) {
                $query->where('is_read', true);
            } elseif ($filters['is_read'] === 'false' || $filters['is_read'] === false) {
                $query->unread();
            }
        }

        // Filtrar por prioridad
        if (isset($filters['priority']) && !empty($filters['priority'])) {
            $query->ofPriority($filters['priority']);
        }

        // Filtrar por fecha desde
        if (isset($filters['date_from']) && !empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        // Filtrar por fecha hasta
        if (isset($filters['date_to']) && !empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->latest()->paginate(20);
    }

    /**
     * Eliminar una notificación
     */
    public function delete(int $notificationId): bool
    {
        $notification = Notification::findOrFail($notificationId);
        return $notification->delete();
    }

    /**
     * Verificar y notificar sobre alto tráfico
     * Se ejecuta automáticamente desde un comando o job
     */
    public function checkAndNotifyHighTraffic(int $userId, int $threshold = 50, int $minutes = 5): ?Notification
    {
        $xmlPath = storage_path('app/vehiculos_db.xml');
        if (!file_exists($xmlPath)) {
            return null;
        }

        $xml = simplexml_load_file($xmlPath);
        $recentDetections = 0;
        $thresholdTime = strtotime("-{$minutes} minutes");

        foreach ($xml->deteccion as $det) {
            $detTime = strtotime((string)$det->fecha);
            if ($detTime >= $thresholdTime) {
                $recentDetections++;
            }
        }

        if ($recentDetections > $threshold) {
            // Verificar si ya existe una notificación reciente similar
            $existingNotification = Notification::forUser($userId)
                ->where('type', 'warning')
                ->where('title', 'Alto Tráfico Detectado')
                ->where('created_at', '>=', now()->subMinutes(30))
                ->first();

            if (!$existingNotification) {
                return $this->create(
                    $userId,
                    'Alto Tráfico Detectado',
                    "Se detectaron {$recentDetections} vehículos en los últimos {$minutes} minutos.",
                    'warning',
                    'high',
                    [
                        'detections' => $recentDetections,
                        'minutes' => $minutes,
                        'threshold' => $threshold,
                    ]
                );
            }
        }

        return null;
    }

    /**
     * Verificar y notificar sobre cámara offline
     * Se ejecuta automáticamente desde un comando o job
     */
    public function checkAndNotifyCameraOffline(int $userId, int $minutesOffline = 10): ?Notification
    {
        $xmlPath = storage_path('app/vehiculos_db.xml');
        if (!file_exists($xmlPath)) {
            return null;
        }

        $xml = simplexml_load_file($xmlPath);
        if (count($xml->deteccion) == 0) {
            return null;
        }

        $lastDetection = $xml->deteccion[count($xml->deteccion) - 1];
        $lastTime = strtotime((string)$lastDetection->fecha);
        $diff = time() - $lastTime;

        if ($diff > ($minutesOffline * 60)) {
            // Verificar si ya existe una notificación reciente similar
            $existingNotification = Notification::forUser($userId)
                ->where('type', 'alert')
                ->where('title', 'Cámara Posiblemente Offline')
                ->where('created_at', '>=', now()->subHour())
                ->first();

            if (!$existingNotification) {
                $minutesSinceLastDetection = round($diff / 60);
                return $this->create(
                    $userId,
                    'Cámara Posiblemente Offline',
                    "No se han detectado vehículos en los últimos {$minutesSinceLastDetection} minutos.",
                    'alert',
                    'high',
                    [
                        'minutes_offline' => $minutesSinceLastDetection,
                        'last_detection' => (string)$lastDetection->fecha,
                    ]
                );
            }
        }

        return null;
    }

    /**
     * Notificar a todos los administradores
     */
    public function notifyAllAdmins(string $title, string $message, string $type = 'info', string $priority = 'medium'): int
    {
        $admins = \App\Models\User::where('role', 'admin')->get();
        $count = 0;

        foreach ($admins as $admin) {
            $this->create($admin->id, $title, $message, $type, $priority);
            $count++;
        }

        return $count;
    }
}
