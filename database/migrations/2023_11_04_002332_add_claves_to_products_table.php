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
            $table->string('c_ClaveUnidad')->nullable();
            $table->string('c_ClaveUnidad_descripcion')->nullable();
            $table->string('c_claveProdServ')->nullable();
            $table->string('c_claveProdServ_descripcion')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('c_ClaveUnidad');
            $table->dropColumn('c_ClaveUnidad_descripcion');
            $table->dropColumn('c_claveProdServ');
            $table->dropColumn('c_claveProdServ_descripcion');
        });
    }
};
