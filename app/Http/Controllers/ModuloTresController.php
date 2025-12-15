<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Facades\NotificationFacade as Notification;
use App\Facades\ReportFacade as Report;

class ModuloTresController extends Controller
{
    /**
     * Dashboard principal del módulo 3
     */
    public function index()
    {
        $unreadCount = Notification::getUnreadCount(auth()->id());
        $recentNotifications = Notification::getUnread(auth()->id())->take(5);
        
        // Obtener reportes recientes
        $recentReports = Report::getHistory(auth()->id(), [])->take(5);
        
        // Estadísticas rápidas
        $quickStats = Report::getQuickStats([
            'date_from' => now()->subWeek()->toDateString(),
            'date_to' => now()->toDateString(),
        ]);

        return view('modulo3.index', compact(
            'unreadCount',
            'recentNotifications',
            'recentReports',
            'quickStats'
        ));
    }

    /**
     * Vista de notificaciones
     */
    public function notifications()
    {
        return view('modulo3.notifications');
    }

    /**
     * Vista de reportes
     */
    public function reports()
    {
        return view('modulo3.reports');
    }
}
