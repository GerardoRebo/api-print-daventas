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
        // Índice para ventaticket_articulos - optimiza getArticulosExtended()
        Schema::table('ventaticket_articulos', function (Blueprint $table) {
            $table->index(['ventaticket_id', 'product_id'], 'idx_ticket_product');
        });

        // Índice para inventario_balances - usado en getArticulosExtended() para cantidad_actual
        Schema::table('inventario_balances', function (Blueprint $table) {
            $table->index(['product_id', 'almacen_id'], 'idx_product_almacen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventaticket_articulos', function (Blueprint $table) {
            $table->dropIndex('idx_ticket_product');
        });

        Schema::table('inventario_balances', function (Blueprint $table) {
            $table->dropIndex('idx_product_almacen');
        });
    }
};
