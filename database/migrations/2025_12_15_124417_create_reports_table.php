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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['daily', 'weekly', 'monthly', 'custom'])->default('custom');
            $table->enum('status', ['pending', 'generated', 'viewed'])->default('pending');
            $table->date('report_date');
            $table->json('data');
            $table->json('filters')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamps();
            
            // Índices para optimizar queries
            $table->index(['user_id', 'status']);
            $table->index('report_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
