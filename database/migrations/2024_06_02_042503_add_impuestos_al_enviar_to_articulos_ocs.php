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
        Schema::table('articulos_ocs', function (Blueprint $table) {
            $table->decimal('impuestos_al_enviar', 10, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articulos_ocs', function (Blueprint $table) {
            $table->dropColumn('impuestos_al_enviar');
        });
    }
};
