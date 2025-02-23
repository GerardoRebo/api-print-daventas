<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('articulo_oc_taxes', function (Blueprint $table) {
                $table->renameColumn('cantidad', 'importe');
            });
            Schema::table('articulo_oc_taxes', function (Blueprint $table) {
                $table->decimal('base', 10, 2)->nullable();
                $table->tinyText('c_impuesto')->nullable();
                $table->tinyText('tipo_factor')->nullable();
                $table->tinyText('tasa_o_cuota')->nullable();
                $table->tinyText('tipo')->nullable();
                $table->tinyText('descripcion')->nullable();
            });
            return;
        }else{
            Schema::table('articulo_oc_taxes', function (Blueprint $table) {
                $table->renameColumn('cantidad', 'importe');
                $table->decimal('base', 10, 2)->nullable();
                $table->tinyText('c_impuesto')->nullable();
                $table->tinyText('tipo_factor')->nullable();
                $table->tinyText('tasa_o_cuota')->nullable();
                $table->tinyText('tipo')->nullable();
                $table->tinyText('descripcion')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articulo_oc_taxes', function (Blueprint $table) {
            $table->renameColumn('importe', 'cantidad');
            $table->dropColumn('c_impuesto');
            $table->dropColumn('tipo_factor');
            $table->dropColumn('tasa_o_cuota');
            $table->dropColumn('tipo');
            $table->dropColumn('descripcion');
        });
    }
};
