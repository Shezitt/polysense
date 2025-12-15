<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Detection;
use App\Models\Camera;
use Carbon\Carbon;

class SyncDetectionsFromXml extends Command
{
    protected $signature = 'detections:sync {--force : Force sync all detections}';
    protected $description = 'Sincroniza detecciones desde XML a base de datos';

    private $xmlPath;
    private $lastSyncFile;

    public function __construct()
    {
        parent::__construct();
        $this->xmlPath = storage_path('app/vehiculos_db.xml');
        $this->lastSyncFile = storage_path('app/last_sync_index.txt');
    }

    public function handle()
    {
        if (!file_exists($this->xmlPath)) {
            $this->error('❌ Archivo XML no encontrado');
            return 1;
        }

        $this->info('🔄 Iniciando sincronización XML → BD...');

        try {
            $xml = simplexml_load_file($this->xmlPath);
            
            if (!$xml || !isset($xml->deteccion)) {
                $this->warn('⚠️  XML vacío o sin detecciones');
                return 0;
            }

            $totalDetections = count($xml->deteccion);
            $lastSyncIndex = $this->getLastSyncIndex();
            $force = $this->option('force');

            if ($force) {
                $this->warn('🔥 Modo FORCE: Sincronizando TODAS las detecciones');
                $lastSyncIndex = 0;
            }

            $this->info("📊 Total en XML: {$totalDetections}");
            $this->info("📌 Última sincronización: índice {$lastSyncIndex}");

            $newDetections = 0;
            $errors = 0;
            $currentIndex = 0;

            $bar = $this->output->createProgressBar($totalDetections - $lastSyncIndex);
            $bar->start();

            foreach ($xml->deteccion as $deteccion) {
                $currentIndex++;

                // Saltar detecciones ya sincronizadas
                if ($currentIndex <= $lastSyncIndex) {
                    continue;
                }

                try {
                    $this->syncDetection($deteccion);
                    $newDetections++;
                    $bar->advance();
                } catch (\Exception $e) {
                    $errors++;
                    $this->newLine();
                    $this->error("Error en índice {$currentIndex}: " . $e->getMessage());
                }
            }

            $bar->finish();
            $this->newLine(2);

            // Guardar último índice sincronizado
            $this->saveLastSyncIndex($currentIndex);

            $this->info("✅ Sincronización completada!");
            $this->info("   📥 Nuevas detecciones: {$newDetections}");
            if ($errors > 0) {
                $this->warn("   ⚠️  Errores: {$errors}");
            }
            $this->info("   📌 Último índice: {$currentIndex}");

            return 0;

        } catch (\Exception $e) {
            $this->error('❌ Error fatal: ' . $e->getMessage());
            return 1;
        }
    }

    private function syncDetection($deteccion): void
    {
        // Extraer datos del XML
        $fecha = (string)$deteccion->fecha;
        $tipo = (string)$deteccion->tipo;
        $confianza = (float)$deteccion->confianza;
        $color = isset($deteccion->color) ? (string)$deteccion->color : 'desconocido';
        $cameraCode = isset($deteccion->camara) ? (string)$deteccion->camara : 'UNKNOWN';
        $cameraName = isset($deteccion->nombre_camara) ? (string)$deteccion->nombre_camara : 'Desconocida';

        // Parsear fecha y hora
        $detectedAt = Carbon::parse($fecha);
        $detectionDate = $detectedAt->toDateString();
        $detectionHour = $detectedAt->hour;

        // Buscar cámara en BD
        $camera = Camera::findByCode($cameraCode);

        // Crear detección
        Detection::create([
            'camera_id' => $camera?->id,
            'camera_code' => $cameraCode,
            'camera_name' => $cameraName,
            'vehicle_type' => $tipo,
            'color' => $color,
            'confidence' => $confianza,
            'detected_at' => $detectedAt,
            'detection_date' => $detectionDate,
            'detection_hour' => $detectionHour,
        ]);
    }

    private function getLastSyncIndex(): int
    {
        if (!file_exists($this->lastSyncFile)) {
            return 0;
        }

        $content = file_get_contents($this->lastSyncFile);
        return (int)$content;
    }

    private function saveLastSyncIndex(int $index): void
    {
        file_put_contents($this->lastSyncFile, $index);
    }
}
