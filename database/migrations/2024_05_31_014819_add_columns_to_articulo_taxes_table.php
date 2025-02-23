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
        Schema::table('articulo_taxes', function (Blueprint $table) {
            $table->tinyText('c_impuesto')->nullable();
            $table->tinyText('tipo_factor')->nullable();
            $table->tinyText('tasa_o_cuota')->nullable();
            $table->tinyText('tipo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articulo_taxes', function (Blueprint $table) {
            $table->dropColumn('c_impuesto');
            $table->dropColumn('tipo_factor');
            $table->dropColumn('tasa_o_cuota');
            $table->dropColumn('tipo');
        });
    }
};
