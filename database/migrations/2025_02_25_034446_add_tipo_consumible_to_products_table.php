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
        Schema::table('products', function (Blueprint $table) {
            $table->enum('tipo_consumible', ['generico', 'especifico', 'regular'])->nullable();
            $table->boolean("es_consumible");
            $table->boolean("usa_medidas");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn("tipo_consumible");
            $table->dropColumn("es_consumible");
            $table->dropColumn("usa_medidas");
        });
    }
};
