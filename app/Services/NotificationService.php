<?php

namespace App\Services;

use App\Models\Notification;
use App\Utils\PriorityQueue;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class NotificationService
{
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

    public function getUnread(int $userId): Collection
    {
        return Notification::forUser($userId)
            ->unread()
            ->latest()
            ->get();
    }

    public function getUnreadCount(int $userId): int
    {
        return Notification::forUser($userId)
            ->unread()
            ->count();
    }

    public function markAsRead(int $notificationId): Notification
    {
        $notification = Notification::findOrFail($notificationId);
        $notification->markAsRead();
        return $notification->fresh();
    }

    public function markAllAsRead(int $userId): int
    {
        return Notification::forUser($userId)
            ->unread()
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
    }

    public function getHistory(int $userId, array $filters = []): LengthAwarePaginator
    {
        $query = Notification::forUser($userId);

        if (isset($filters['type']) && !empty($filters['type'])) {
            $query->ofType($filters['type']);
        }

        if (isset($filters['is_read'])) {
            if ($filters['is_read'] === 'true' || $filters['is_read'] === true) {
                $query->where('is_read', true);
            } elseif ($filters['is_read'] === 'false' || $filters['is_read'] === false) {
                $query->unread();
            }
        }

        if (isset($filters['priority']) && !empty($filters['priority'])) {
            $query->ofPriority($filters['priority']);
        }

        if (isset($filters['date_from']) && !empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to']) && !empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->latest()->paginate(20);
    }

    public function delete(int $notificationId): bool
    {
        $notification = Notification::findOrFail($notificationId);
        return $notification->delete();
    }

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

    /**
     * Create multiple notifications at once, processing higher priority items first.
     *
     * Each item must be an array with keys: user_id, title, message, type (optional), priority (optional), metadata (optional).
     * This method sorts by priority (high > medium > low) and then creates notifications in that order so
     * that when multiple notifications are requested "simultaneously" the higher priority ones are persisted/issued first.
     *
     * @param array $items
     * @return \Illuminate\Support\Collection Created Notification models
     */
    public function createBulk(array $items)
    {
        // priority weight map
        $weights = [
            'low' => 0,
            'medium' => 1,
            'high' => 2,
        ];

        // normalize and sort items by weight descending, then preserve original order for same priority
        $normalized = [];
        foreach ($items as $i => $it) {
            $priority = isset($it['priority']) ? $it['priority'] : 'medium';
            $weight = $weights[$priority] ?? $weights['medium'];
            $normalized[] = array_merge($it, ['_priority_weight' => $weight, '_original_index' => $i]);
        }

        usort($normalized, function ($a, $b) {
            if ($a['_priority_weight'] === $b['_priority_weight']) {
                return $a['_original_index'] <=> $b['_original_index'];
            }
            return $b['_priority_weight'] <=> $a['_priority_weight'];
        });

        $created = collect();

        foreach ($normalized as $it) {
            $created->push($this->create(
                (int)($it['user_id'] ?? 0),
                (string)($it['title'] ?? 'Sin título'),
                (string)($it['message'] ?? ''),
                (string)($it['type'] ?? 'info'),
                (string)($it['priority'] ?? 'medium'),
                (array)($it['metadata'] ?? [])
            ));
        }

        return $created;
    }

    /**
     * Alternative bulk creation using the explicit PriorityQueue utility.
     * Items are enqueued by their 'priority' key and then dequeued in order
     * high -> medium -> low, preserving FIFO within the same priority.
     *
     * @param array $items
     * @return \Illuminate\Support\Collection
     */
    public function createBulkWithQueue(array $items)
    {
        $pq = new PriorityQueue();

        foreach ($items as $it) {
            $priority = isset($it['priority']) ? $it['priority'] : 'medium';
            $pq->enqueue($it, $priority);
        }

        $created = collect();

        while (!$pq->isEmpty()) {
            $it = $pq->dequeue();
            $created->push($this->create(
                (int)($it['user_id'] ?? 0),
                (string)($it['title'] ?? 'Sin título'),
                (string)($it['message'] ?? ''),
                (string)($it['type'] ?? 'info'),
                (string)($it['priority'] ?? 'medium'),
                (array)($it['metadata'] ?? [])
            ));
        }

        return $created;
    }
}
