<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\AutomationConfig;
use App\Models\Notification;
use App\Models\Report;

class ModuloTresController extends Controller
{
    public function index()
    {
        $users = User::with('automationConfig')->get();
        
        $totalNotifications = Notification::count();
        $totalReports = Report::count();
        $activeUsers = User::where('role', '!=', 'admin')->count();
        
        return view('modulo3.index', compact('users', 'totalNotifications', 'totalReports', 'activeUsers'));
    }

    /**
     * Formulario de configuración para un usuario
     */
    public function configureUser($userId)
    {
        $user = User::with('automationConfig')->findOrFail($userId);
        $config = $user->automationConfig ?? AutomationConfig::getOrCreateForUser($userId);
        
        return view('modulo3.configure', compact('user', 'config'));
    }

    /**
     * Guardar configuración de usuario
     */
    public function saveConfiguration(Request $request, $userId)
    {
        $validated = $request->validate([
            'traffic_threshold' => 'required|integer|min:1|max:1000',
            'traffic_minutes' => 'required|integer|min:1|max:60',
            'notify_high_traffic' => 'boolean',
            'notify_camera_offline' => 'boolean',
            'camera_offline_minutes' => 'required|integer|min:1|max:120',
            'report_frequency' => 'required|in:daily,weekly,monthly,disabled',
            'report_generation_time' => 'required|date_format:H:i',
            'auto_generate_reports' => 'boolean',
        ]);

        $config = AutomationConfig::getOrCreateForUser($userId);
        $config->update($validated);

        return redirect()->route('modulo3')
            ->with('success', 'Configuración actualizada exitosamente');
    }

    /**
     * Ver todas las notificaciones (de todos los usuarios)
     */
    public function allNotifications(Request $request)
    {
        $query = Notification::with('user')->latest();
        
        // Filtros
        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }
        
        $notifications = $query->paginate(20);
        $users = User::all();
        
        return view('modulo3.notifications', compact('notifications', 'users'));
    }

    /**
     * Ver todos los reportes (de todos los usuarios)
     */
    public function allReports(Request $request)
    {
        $query = Report::with('user')->latest();
        
        // Filtros
        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }
        
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }
        
        $reports = $query->paginate(20);
        $users = User::all();
        
        return view('modulo3.reports', compact('reports', 'users'));
    }

    /**
     * Configuración global de limpieza XML
     */
    public function xmlCleanupConfig()
    {
        $adminUser = User::where('role', 'admin')->first();
        $config = $adminUser ? AutomationConfig::getOrCreateForUser($adminUser->id) : null;
        
        return view('modulo3.xml-cleanup', compact('config'));
    }

    /**
     * Guardar configuración de limpieza XML
     */
    public function saveXmlCleanupConfig(Request $request)
    {
        $validated = $request->validate([
            'xml_cleanup_frequency' => 'required|in:daily,weekly,monthly,never',
            'xml_retention_days' => 'required|integer|min:1|max:365',
        ]);

        $adminUser = User::where('role', 'admin')->first();
        if ($adminUser) {
            $config = AutomationConfig::getOrCreateForUser($adminUser->id);
            $config->update($validated);
        }

        return redirect()->route('modulo3')
            ->with('success', 'Configuración de limpieza XML actualizada');
    }
}

