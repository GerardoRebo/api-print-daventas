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
            $table->enum('consumible', ['generico', 'regular'])->nullable();
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
            $table->dropColumn("consumible");
            $table->dropColumn("necesita_produccion");
            $table->dropColumn("usa_medidas");
        });
    }
};
