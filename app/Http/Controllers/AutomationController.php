<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AutomationController extends Controller
{
    private $dbPath = 'vehiculos_db.xml';
    private $configPath = 'users_config.xml';

    public function index()
    {
        $this->ensureConfigExists();
        
        $config = simplexml_load_string(Storage::get($this->configPath));
        $dbPath = storage_path('app/' . $this->dbPath);
        $dbSize = file_exists($dbPath) ? $this->formatBytes(filesize($dbPath)) : '0 B';

        // Get cameras from existing vehicle DB for the dropdown
        $cameras = $this->getUniqueCameras();
        
        // Get status for each camera (simplified for now as we only track one active stream in XML effectively)
        $cameraStatus = [];
        foreach($cameras as $cam) {
            $cameraStatus[$cam] = $this->getCameraStatus($cam);
        }

        return view('modulo3', compact('config', 'dbSize', 'cameras', 'cameraStatus'));
    }

    private function getUniqueCameras()
    {
        // En un escenario real con múltiples cámaras, esto vendría de una tabla de configuración
        // o se extraería de los metadatos del XML si el script de Python los guardara.
        // Basado en el código del ESP32 (esp.cpp), el ID del nodo es "CAM_001".
        // Asumiremos que es la cámara principal activa.
        return ['CAM_001'];
    }

    private function getCameraStatus($camera = 'CAM_001')
    {
        // Verificar si la cámara está activa basándose en la última detección en el XML
        $xmlPath = storage_path('app/' . $this->dbPath);
        $status = 'OFFLINE';
        $lastSeen = 'Nunca';

        if (file_exists($xmlPath)) {
            $xml = simplexml_load_file($xmlPath);
            // Obtener la última detección
            if ($xml->deteccion && count($xml->deteccion) > 0) {
                // Asumiendo que el último elemento es el más reciente (appends)
                // O buscar la fecha más reciente
                $lastNode = $xml->deteccion[count($xml->deteccion) - 1];
                $lastDateStr = (string)$lastNode->fecha;
                
                // Si la última detección fue hace menos de 5 minutos, consideramos ONLINE
                $lastTime = strtotime($lastDateStr);
                $diff = time() - $lastTime;
                
                $lastSeen = $lastDateStr;
                
                if ($diff < 300) { // 5 minutos
                    $status = 'ONLINE';
                }
            }
        }
        
        return ['status' => $status, 'last_seen' => $lastSeen];
    }

    public function cleanData(Request $request)
    {
        $frequency = $request->input('execution_frequency', 'now');
        $period = $request->input('period');
        $customDate = $request->input('custom_date');
        $camera = $request->input('camera');

        if ($frequency !== 'now') {
            $this->ensureConfigExists();
            $xml = simplexml_load_string(Storage::get($this->configPath));
            
            if (!isset($xml->cleaning_schedule)) {
                $xml->addChild('cleaning_schedule');
            }
            
            $task = $xml->cleaning_schedule->addChild('task');
            $task->addChild('camera', $camera);
            $task->addChild('period', $period);
            $task->addChild('frequency', $frequency);
            if ($period === 'custom') {
                $task->addChild('custom_date', $customDate);
            }

            Storage::put($this->configPath, $xml->asXML());
            return redirect()->back()->with('success', "Tarea de limpieza programada ($frequency).");
        }

        $xmlPath = storage_path('app/' . $this->dbPath);
        if (!file_exists($xmlPath)) {
             return redirect()->back()->with('error', 'Base de datos no encontrada.');
        }

        $xml = simplexml_load_file($xmlPath);
        $dom = new \DOMDocument('1.0');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());
        $root = $dom->documentElement;

        $retentionDate = null;
        if ($period == '1week') {
            $retentionDate = strtotime('-1 week');
        } elseif ($period == '1month') {
            $retentionDate = strtotime('-1 month');
        } elseif ($period == 'custom' && $customDate) {
            $retentionDate = strtotime($customDate);
        }

        $count = 0;
        $detecciones = $root->getElementsByTagName('deteccion');
        
        for ($i = $detecciones->length - 1; $i >= 0; $i--) {
            $node = $detecciones->item($i);
            $fechaNode = $node->getElementsByTagName('fecha')->item(0);
            
            if ($fechaNode) {
                $fechaTime = strtotime($fechaNode->nodeValue);
                if ($retentionDate && $fechaTime < $retentionDate) {
                    $root->removeChild($node);
                    $count++;
                }
            }
        }

        $dom->save($xmlPath);
        
        return redirect()->back()->with('success', "Limpieza completada. $count registros eliminados.");
    }

    public function saveUser(Request $request)
    {
        $this->ensureConfigExists();
        $xml = simplexml_load_string(Storage::get($this->configPath));

        $newUser = $xml->users->addChild('user');
        $newUser->addChild('id', uniqid());
        $newUser->addChild('name', $request->input('name'));
        $newUser->addChild('email', $request->input('email'));

        // Default report config
        $reports = $xml->reports;
        $newReport = $reports->addChild('report');
        $newReport->addChild('user_email', $request->input('email'));
        $newReport->addChild('frequency', $request->input('report_frequency', 'daily'));
        $newReport->addChild('format', $request->input('report_format', 'pdf'));

        Storage::put($this->configPath, $xml->asXML());
        return redirect()->back()->with('success', 'Usuario y configuración guardados.');
    }

    public function updateNotification(Request $request)
    {
        $this->ensureConfigExists();
        $xml = simplexml_load_string(Storage::get($this->configPath));

        // Add or update notification for a specific user/camera
        // For simplicity, just appending a new rule logic
        $notifs = $xml->notifications;
        $rule = $notifs->addChild('notification');
        $rule->addChild('user_email', $request->input('user_email')); // Select from existing
        $rule->addChild('camera', $request->input('camera'));
        $rule->addChild('min_threshold', $request->input('min_threshold'));
        $rule->addChild('max_threshold', $request->input('max_threshold'));
        $rule->addChild('notify_black_screen', $request->has('notify_black_screen') ? 'true' : 'false');

        Storage::put($this->configPath, $xml->asXML());
        return redirect()->back()->with('success', 'Configuración de notificaciones actualizada.');
    }

    private function ensureConfigExists()
    {
        if (!Storage::exists($this->configPath)) {
            $initialXml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<config>
    <users></users>
    <reports></reports>
    <notifications></notifications>
</config>
XML;
            Storage::put($this->configPath, $initialXml);
        }
    }

    private function formatBytes($bytes, $precision = 2) 
    { 
        $units = ['B', 'KB', 'MB', 'GB', 'TB']; 
        $bytes = max($bytes, 0); 
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
        $pow = min($pow, count($units) - 1); 
        $bytes /= pow(1024, $pow); 
        return round($bytes, $precision) . ' ' . $units[$pow]; 
    }
}
