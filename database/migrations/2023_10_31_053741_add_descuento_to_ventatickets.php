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
                $table->decimal('descuento')->nullable();
            });
            Schema::table('ventaticket_articulos', function (Blueprint $table) {
                $table->renameColumn('porcentaje_descuento', 'importe_descuento');
            });
            return;
        } else {
            Schema::table('ventaticket_articulos', function (Blueprint $table) {
                $table->decimal('descuento')->nullable();
                $table->renameColumn('porcentaje_descuento', 'importe_descuento');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventaticket_articulos', function (Blueprint $table) {
            $table->dropColumn('descuento')->nullable();
        });
    }
};
