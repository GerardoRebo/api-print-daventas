<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventarioAjustesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    ///
    ///
    ///borrare esta tabla
    
    public function up()
    {
        Schema::create('inventario_ajustes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            $table->unsignedBigInteger('organization_id')->nullable();
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');
            
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            

            $table->unsignedBigInteger('almacen_id')->nullable();
            $table->foreign('almacen_id')->references('id')->on('almacens')->onDelete('cascade');

            $table->dateTime('cuando_fue')->nullable();
            $table->dateTime('caducidad')->nullable();

            $table->decimal('cantidad')->nullable();
            $table->decimal('costo_unitario')->nullable();
            $table->string('motivo')->nullable();

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
        Schema::dropIfExists('inventario_ajustes');
    }
}
