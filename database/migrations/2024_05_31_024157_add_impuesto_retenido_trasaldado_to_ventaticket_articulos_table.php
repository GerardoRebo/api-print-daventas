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
            Schema::table('ventaticket_articulos', function (Blueprint $table) {
                $table->renameColumn('impuesto_unitario', 'impuesto_traslado');
            });
            Schema::table('ventaticket_articulos', function (Blueprint $table) {
                $table->decimal('impuesto_retenido', 10, 2)->default(0);
            });
            return;
        } else {
            Schema::table('ventaticket_articulos', function (Blueprint $table) {
                $table->renameColumn('impuesto_unitario', 'impuesto_traslado');
                $table->decimal('impuesto_retenido', 10, 2)->default(0);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventaticket_articulos', function (Blueprint $table) {
            $table->renameColumn('impuesto_traslado', 'impuesto_unitario');
            $table->dropColumn('impuesto_retenido');
        });
    }
};
