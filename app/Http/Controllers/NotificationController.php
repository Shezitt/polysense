<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Facades\NotificationFacade as Notification;

class NotificationController extends Controller
{
    /**
     * Listar notificaciones del usuario con filtros
     */
    public function index(Request $request)
    {
        $filters = $request->only(['type', 'is_read', 'priority', 'date_from', 'date_to']);
        $notifications = Notification::getHistory(auth()->id(), $filters);

        return response()->json($notifications);
    }

    /**
     * Obtener notificaciones no leídas y contador
     */
    public function unread()
    {
        $count = Notification::getUnreadCount(auth()->id());
        $notifications = Notification::getUnread(auth()->id());

        return response()->json([
            'count' => $count,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Marcar notificación como leída
     */
    public function markAsRead($id)
    {
        try {
            $notification = Notification::markAsRead($id);
            return response()->json([
                'success' => true,
                'notification' => $notification,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notificación no encontrada',
            ], 404);
        }
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function markAllAsRead()
    {
        $count = Notification::markAllAsRead(auth()->id());
        
        return response()->json([
            'success' => true,
            'marked' => $count,
            'message' => "{$count} notificaciones marcadas como leídas",
        ]);
    }

    /**
     * Eliminar notificación
     */
    public function destroy($id)
    {
        try {
            Notification::delete($id);
            return response()->json([
                'success' => true,
                'message' => 'Notificación eliminada',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Notificación no encontrada',
            ], 404);
        }
    }
}
