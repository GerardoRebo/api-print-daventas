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
        Schema::create('facturacion_infos', function (Blueprint $table) {
            $table->id();
            $table->string('rfc')->nullable();
            $table->string('razon_social')->nullable();
            $table->string('regimen_fiscal')->nullable();
            $table->string('clave_privada_sat')->nullable();
            $table->string('clave_privada_local')->nullable();
            $table->string('cer_path')->nullable();
            $table->string('cer_name')->nullable();
            $table->string('key_path')->nullable();
            $table->string('key_name')->nullable();
            $table->integer('infoable_id')->nullable();
            $table->string('infoable_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facturacion_infos');
    }
};
