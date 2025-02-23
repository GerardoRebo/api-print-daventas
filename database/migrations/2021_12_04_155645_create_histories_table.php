<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    //
    //
    //borraré
    public function up()
    {
        Schema::create('histories', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');

            //inventario_ajuste_id inventario_recibo_id ventaticket_id ordencompra_id
            $table->unsignedBigInteger('historiable_id');
            $table->string('historiable_type');

            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('almacen_id')->nullable();
            $table->foreign('almacen_id')->references('id')->on('almacens')->onDelete('cascade');
            
            $table->dateTime('cuando_fue')->nullable();//este no me convence
            $table->decimal('cantidad')->nullable();
            $table->decimal('cantidad_anterior')->nullable();
            $table->decimal('cantidad_posterior')->nullable();
            $table->string('descripcion')->nullable();
            $table->decimal('costo_anterior')->nullable();
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
        Schema::dropIfExists('histories');
    }
}
