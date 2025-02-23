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
        Schema::table('pre_factura_articulos', function (Blueprint $table) {
            $table->renameColumn('impuesto', 'impuesto_traslado');
        });
        Schema::table('pre_factura_articulos', function (Blueprint $table) {
            $table->decimal('impuesto_retenido')->after('impuesto_traslado')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pre_factura_articulos', function (Blueprint $table) {
            $table->dropColumn('impuesto_retenido');
        });
    }
};
