<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInventarioBalancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inventario_balances', function (Blueprint $table) {
            $table->id();
            
            $table->decimal('invmin')->nullable();
            $table->decimal('invmax')->nullable();

            // $table->unsignedBigInteger('organization_id')->nullable();
            // $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');

            $table->unsignedBigInteger('product_id')->nullable();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');

            $table->unsignedBigInteger('almacen_id')->nullable();
            $table->foreign('almacen_id')->references('id')->on('almacens')->onDelete('cascade');

            $table->decimal('cantidad_actual')->default(0);
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
        Schema::dropIfExists('inventario_balances');
    }
}
