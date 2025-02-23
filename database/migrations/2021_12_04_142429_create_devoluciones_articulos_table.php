<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDevolucionesArticulosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('devoluciones_articulos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('ventaticket_articulo_id')->nullable();
            $table->foreign('ventaticket_articulo_id')->references('id')->on('ventaticket_articulos')->onDelete('cascade');

            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            $table->decimal('cantidad_devuelta')->nullable();
            $table->decimal('dinero_devuelto')->nullable();

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
        Schema::dropIfExists('devoluciones_articulos');
    }
}
