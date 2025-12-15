<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade para NotificationService
 * 
 * @method static \App\Models\Notification create(int $userId, string $title, string $message, string $type = 'info', string $priority = 'medium', array $metadata = [])
 * @method static \Illuminate\Support\Collection getUnread(int $userId)
 * @method static int getUnreadCount(int $userId)
 * @method static \App\Models\Notification markAsRead(int $notificationId)
 * @method static int markAllAsRead(int $userId)
 * @method static \Illuminate\Pagination\LengthAwarePaginator getHistory(int $userId, array $filters = [])
 * @method static bool delete(int $notificationId)
 * @method static \App\Models\Notification|null checkAndNotifyHighTraffic(int $userId, int $threshold = 50, int $minutes = 5)
 * @method static \App\Models\Notification|null checkAndNotifyCameraOffline(int $userId, int $minutesOffline = 10)
 * @method static int notifyAllAdmins(string $title, string $message, string $type = 'info', string $priority = 'medium')
 * 
 * @see \App\Services\NotificationService
 */
class NotificationFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'notification.service';
    }
}
