<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventarioHistorialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    //
    //
    //Borrare esta tabla
    public function up()
    {
        Schema::create('inventario_historials', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('organization_id')->nullable();
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');

            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            $table->unsignedBigInteger('inventario_ajuste_id')->nullable();
            $table->foreign('inventario_ajuste_id')->references('id')->on('inventario_ajustes')->onDelete('cascade');

            $table->unsignedBigInteger('ventaticket_id')->nullable();
            $table->foreign('ventaticket_id')->references('id')->on('ventatickets')->onDelete('cascade');

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('almacen_id')->nullable();
            $table->foreign('almacen_id')->references('id')->on('almacens')->onDelete('cascade');

            $table->dateTime('cuando_fue')->nullable();
            $table->decimal('cantidad_anterior')->nullable();
            $table->decimal('cantidad')->nullable();
            $table->string('descripcion')->nullable();
            $table->decimal('costo_unitario')->nullable();
            $table->decimal('costo_despues')->nullable();
            $table->boolean('venta_por_kit')->nullable();
            $table->boolean('verificado')->nullable();
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
        Schema::dropIfExists('inventario_historials');
    }
}
