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
        Schema::table('pre_factura_articulo_taxes', function (Blueprint $table) {
            $table->tinyText('tipo')->nullable()->after('tasa_o_cuota');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pre_factura_articulo_taxes', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
