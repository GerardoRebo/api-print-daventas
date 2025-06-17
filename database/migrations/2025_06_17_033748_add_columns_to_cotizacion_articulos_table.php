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
        Schema::table('cotizacion_articulos', function (Blueprint $table) {
            $table->decimal('ancho')->nullable();
            $table->decimal('alto')->nullable();
            $table->decimal('area')->nullable();
            $table->decimal('area_total')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cotizacion_articulos', function (Blueprint $table) {
            $table->dropColumn('ancho');
            $table->dropColumn('alto');
            $table->dropColumn('area');
            $table->dropColumn('area_total');
        });
    }
};
