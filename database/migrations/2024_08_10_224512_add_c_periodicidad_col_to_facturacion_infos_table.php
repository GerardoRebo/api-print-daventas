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
        Schema::table('facturacion_infos', function (Blueprint $table) {
            $table->tinyText('c_periodicidad')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturacion_infos', function (Blueprint $table) {
            $table->dropColumn('c_periodicidad');
        });
    }
};
