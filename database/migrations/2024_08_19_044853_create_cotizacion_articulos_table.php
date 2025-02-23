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
        Schema::create('cotizacion_articulos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->decimal('cantidad');
            $table->decimal('precio', 10, 2)->nullable();
            $table->decimal('importe', 12, 2)->nullable();
            $table->decimal('descuento', 10, 2)->nullable();
            $table->decimal('importe_descuento', 10, 2)->nullable();
            $table->decimal('impuesto_traslado', 10, 2)->nullable();
            $table->decimal('impuesto_retenido', 10, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotizacion_articulos');
    }
};
