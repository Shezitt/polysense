<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Configuración de notificaciones
            $table->integer('traffic_threshold')->default(50); // Vehículos en X minutos
            $table->integer('traffic_minutes')->default(5);
            $table->boolean('notify_high_traffic')->default(true);
            $table->boolean('notify_camera_offline')->default(true);
            $table->integer('camera_offline_minutes')->default(10);
            
            // Configuración de reportes
            $table->enum('report_frequency', ['daily', 'weekly', 'monthly', 'disabled'])->default('disabled');
            $table->time('report_generation_time')->default('08:00:00');
            $table->boolean('auto_generate_reports')->default(false);
            
            // Configuración de limpieza XML (solo para admin)
            $table->enum('xml_cleanup_frequency', ['daily', 'weekly', 'monthly', 'never'])->default('never')->nullable();
            $table->integer('xml_retention_days')->default(30)->nullable();
            
            $table->timestamps();
            
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_configs');
    }
};
