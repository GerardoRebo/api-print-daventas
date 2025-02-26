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
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ventaticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ventaticket_articulo_id')->constrained()->cascadeOnDelete();

            // Estado actual del proceso
            $table->enum('status', ['pending', 'design', 'production', 'finishing', 'finished', 'delivered'])->default('pending');

            // Control de consumibles
            $table->boolean('uses_consumable')->default(false);
            $table->boolean('consumable_deducted')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_articulos');
    }
};
