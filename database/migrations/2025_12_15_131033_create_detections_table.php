<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camera_id')->nullable()->constrained()->onDelete('set null');
            $table->string('camera_code'); // CAM_001, CAM_002 (redundante pero útil)
            $table->string('camera_name'); // Oracle Server, Skyline Cochabamba
            $table->string('vehicle_type'); // Auto, Moto, Bus, Camión
            $table->string('color');
            $table->decimal('confidence', 5, 2);
            $table->dateTime('detected_at'); // Fecha y hora completa
            $table->date('detection_date'); // Solo fecha (para queries rápidas)
            $table->integer('detection_hour'); // 0-23 (para estadísticas por hora)
            $table->timestamps();
            
            // Índices para optimizar queries
            $table->index(['detection_date', 'detection_hour']);
            $table->index('camera_code');
            $table->index('vehicle_type');
            $table->index('detected_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detections');
    }
};
