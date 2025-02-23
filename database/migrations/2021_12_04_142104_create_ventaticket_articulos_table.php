<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVentaticketArticulosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ventaticket_articulos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('ventaticket_id')->nullable();
            $table->foreign('ventaticket_id')->references('id')->on('ventatickets')->onDelete('cascade');

            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            $table->unsignedBigInteger('departamento_id')->nullable();
            $table->foreign('departamento_id')->references('id')->on('departamentos')->onDelete('cascade');

            $table->date('caducidad')->nullable();
            $table->decimal('cantidad')->nullable();
            $table->decimal('ganancia')->nullable();
            $table->date('pagado_en')->nullable();
            $table->decimal('porcentaje_descuento')->nullable();
            $table->decimal('impuesto_unitario')->nullable();
            $table->decimal('precio_usado')->nullable();
            $table->decimal('cantidad_devuelta')->default(0)->nullable();
            $table->boolean('fue_devuelto')->nullable();
            $table->decimal('porcentaje_pagado')->nullable();
            $table->decimal('precio_final')->nullable();
            $table->date('agregado_en')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ventaticket_articulos');
    }
}
