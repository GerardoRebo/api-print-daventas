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
        Schema::create('production_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_order_id')->constrained()->cascadeOnDelete(); // Relación con la producción

            // Etapa del proceso
            $table->enum('stage', ['design', 'production', 'finishing', 'quality_check', 'packaging', 'delivered']);

            // Responsable de la etapa
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Quién realizó la acción

            // Registro de tiempos
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            // Notas u observaciones
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_logs');
    }
};
