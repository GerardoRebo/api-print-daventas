<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdenComprasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orden_compras', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('organization_id')->nullable();
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');

            $table->unsignedBigInteger('proveedor_id')->nullable();
            $table->foreign('proveedor_id')->references('id')->on('proveedors')->onDelete('set null');
            
            $table->unsignedBigInteger('almacen_origen_id')->nullable();
            $table->foreign('almacen_origen_id')->references('id')->on('almacens')->onDelete('set null');

            $table->unsignedBigInteger('almacen_destino_id')->nullable();
            $table->foreign('almacen_destino_id')->references('id')->on('almacens')->onDelete('set null');

            
            $table->enum('tipo',['','C','T'])->nullable(); //compra,trasnferencia
            
            $table->unsignedInteger('consecutivo')->nullable();
            $table->dateTime('cancelada_en')->nullable();
            $table->dateTime('enviada_en')->nullable();
            $table->dateTime('recibida_en')->nullable();
            $table->enum('estado',['P','C','R','B'])->nullable(); //recibido,pendiente,cancelado,borrador
            $table->decimal('utilidad_enviado')->nullable();
            $table->boolean('pendiente')->nullable();
            $table->decimal('impuestos_enviado')->nullable();
            $table->decimal('subtotal_enviado')->nullable();
            $table->decimal('total_enviado')->nullable();
            $table->decimal('utilidad_recibido')->nullable();
            $table->decimal('impuestos_recibido')->nullable();
            $table->decimal('subtotal_recibido')->nullable();
            $table->decimal('total_recibido')->nullable();
            $table->string('notas')->nullable();
            $table->integer('total_articulos')->nullable();

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
        Schema::dropIfExists('orden_compras');
    }
}
