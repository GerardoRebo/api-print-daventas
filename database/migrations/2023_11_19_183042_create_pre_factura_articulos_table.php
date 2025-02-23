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
        Schema::create('pre_factura_articulos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pre_factura_id');
            $table->foreign('pre_factura_id')->references('id')->on('pre_facturas')->cascadeOnDelete();

            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            $table->decimal('cantidad', 10,2)->default(0);
            $table->decimal('precio', 10,2)->default(0);
            $table->decimal('descuento', 10,2)->default(0);
            $table->decimal('impuesto', 10,2)->default(0);
            $table->decimal('importe', 10,2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pre_factura_articulos');
    }
};
