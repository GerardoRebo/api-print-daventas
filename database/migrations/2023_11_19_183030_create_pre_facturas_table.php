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
        Schema::create('pre_facturas', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('ventaticket_id')->nullable();
            $table->foreign('ventaticket_id')->references('id')->on('ventatickets')->cascadeOnDelete();

            $table->decimal('subtotal')->nullable();
            $table->decimal('descuento')->nullable();
            $table->decimal('impuesto')->nullable();
            $table->decimal('total')->nullable();
            $table->dateTime('facturado_en')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_facturas');
    }
};
