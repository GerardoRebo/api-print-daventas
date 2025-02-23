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
        Schema::create('pre_factura_articulo_taxes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pre_factura_articulo_id')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreignId('tax_id')
                ->nullable()
                ->constrained()
                ->onUpdate('cascade')
                ->nullOnDelete();

            $table->decimal('base', 12, 2);
            $table->decimal('importe', 12, 2);
            $table->string('c_impuesto');
            $table->string('tipo_factor');
            $table->string('tasa_o_cuota');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_factura_articulo_taxes');
    }
};
