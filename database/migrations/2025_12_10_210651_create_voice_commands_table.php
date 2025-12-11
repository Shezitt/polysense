<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('voice_commands', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre descriptivo del comando
            $table->string('trigger', 500); // Palabras clave (separadas por coma)
            $table->enum('action', ['navigate', 'export', 'toggle', 'custom']); // Tipo de acción
            $table->string('target')->nullable(); // URL o target para la acción
            $table->string('function_name')->nullable(); // Nombre de función JS para custom
            $table->string('modules')->default('all'); // Módulos donde está activo
            $table->boolean('enabled')->default(true); // Si el comando está activo
            $table->timestamps();
            
            // Índices para búsqueda rápida
            $table->index('enabled');
            $table->index('modules');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voice_commands');
    }
};
