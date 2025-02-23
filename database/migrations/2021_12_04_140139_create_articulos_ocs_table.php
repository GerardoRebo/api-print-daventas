<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticulosOcsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('articulos_ocs', function (Blueprint $table) {
            $table->id();

            // $table->unsignedBigInteger('organization_id')->nullable();
            // $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');

            $table->unsignedBigInteger('orden_compra_id')->nullable();
            $table->foreign('orden_compra_id')->references('id')->on('orden_compras')->onDelete('cascade');

            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            
            $table->decimal('cantidad_ordenada')->nullable();             
            $table->decimal('cantidad_recibida')->nullable(); 
            $table->decimal('costo_al_ordenar')->nullable();                         
            $table->decimal('costo_al_recibir')->nullable();                         
            $table->integer('dias_en_recibir')->nullable(); 
            $table->decimal('utilidad_estimada_al_ordenar')->nullable();                                                 
            $table->decimal('utilidad_estimada_al_recibir')->nullable();                                                 
            $table->decimal('impuestos_al_recibir')->nullable();//uso este                                                 
            $table->decimal('subtotal_al_recibir')->nullable(); 
            $table->decimal('total_al_ordenar')->nullable(); 
            $table->decimal('total_al_recibir')->nullable(); 
            $table->decimal('precio_sin_impuestos')->nullable();                                                                                                                                                 
            $table->decimal('precio_con_impuestos')->nullable(); 

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
        Schema::dropIfExists('articulos_ocs');
    }
}
