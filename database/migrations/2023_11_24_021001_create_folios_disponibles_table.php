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
        Schema::create('folios_disponibles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proveedor_folio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('compra_folio_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('folios_utilizado_id')->nullable()->constrained()->cascadeOnDelete();
            $table->integer('cantidad')->default(0);
            $table->integer('antes')->default(0);
            $table->integer('despues')->default(0);
            $table->integer('saldo')->default(0);
            $table->tinyText('referencia')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folios_disponibles');
    }
};
