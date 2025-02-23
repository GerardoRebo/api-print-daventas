<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropPromocionPorCantidads extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('promocion_por_cantidads');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('promocion_por_cantidads', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('organization_id')->nullable(); //no lo usaré
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');

            $table->unsignedBigInteger('product_id')->nullable(); 
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            $table->unsignedBigInteger('almacen_id')->nullable(); //por lo pronto no lo usaré
            $table->foreign('almacen_id')->references('id')->on('almacens')->onDelete('cascade');

            $table->string('name'); //no lo usaré
            $table->decimal('desde')->nullable();
            $table->decimal('hasta')->nullable();
            $table->decimal('precio_promocion')->nullable(); //porcentaje promoción
            $table->decimal('precio_promocion_con_impuestos')->nullable(); //no lo usaré
            $table->unique(['name','organization_id']); //estos no son los uniques

            $table->timestamps();
        });
    }
}
