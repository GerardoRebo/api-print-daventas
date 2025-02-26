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
            $table->boolean("es_consumible_generico")->nullable();
            $table->boolean("necesita_produccion")->nullable();
            $table->boolean("usa_medidas")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn("es_consumible_generico");
            $table->dropColumn("necesita_produccion");
            $table->dropColumn("usa_medidas");
        });
    }
};
