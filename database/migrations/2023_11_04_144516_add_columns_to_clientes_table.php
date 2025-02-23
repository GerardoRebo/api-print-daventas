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
        Schema::table('clientes', function (Blueprint $table) {
            $table->string('codigo_postal')->nullable();
            $table->string('razon_social')->nullable();
            $table->string('regimen_fiscal')->nullable();
            $table->string('rfc')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('codigo_postal');
            $table->dropColumn('razon_social');
            $table->dropColumn('regimen_fiscal');
            $table->dropColumn('rfc');
        });
    }
};
