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
        Schema::create('cotizacion_articulo_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cotizacion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cotizacion_articulo_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tax_id')->constrained()->cascadeOnDelete();
            $table->decimal('importe', 10, 2)->default(0);
            $table->decimal('base', 12, 2)->default(0);
            $table->string('c_impuesto', 20)->nullable();
            $table->string('tipo_factor', 20)->nullable();
            $table->string('tasa_o_cuota', 20)->nullable();
            $table->string('tipo', 20)->nullable();
            $table->string('descripcion', 20)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotizacion_articulo_taxes');
    }
};
