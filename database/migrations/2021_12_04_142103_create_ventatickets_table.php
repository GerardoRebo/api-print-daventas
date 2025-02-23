<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVentaticketsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ventatickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');
            
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('turno_id')->nullable();
            $table->foreign('turno_id')->references('id')->on('turnos')->onDelete('cascade');

           
            $table->unsignedBigInteger('cliente_id')->nullable();
            $table->foreign('cliente_id')->references('id')->on('clientes')->onDelete('cascade');

            $table->unsignedBigInteger('almacen_id')->nullable();
            $table->foreign('almacen_id')->references('id')->on('almacens')->onDelete('cascade');

            $table->unsignedInteger('consecutivo')->nullable();
            $table->string('nombre')->nullable();
            // $table->dateTime('creado_en')->nullable();
            $table->decimal('subtotal')->nullable();
            $table->decimal('impuestos')->nullable();
            $table->decimal('total')->nullable();
            $table->decimal('ganancia')->nullable();
            $table->boolean('esta_abierto')->nullable();
            $table->date('vendido_en')->nullable();
            // $table->boolean('es_modificable')->nullable();
            $table->decimal('pago_con')->nullable();
            $table->integer('numero_de_articulos')->nullable();
            $table->dateTime('pagado_en')->nullable();
            $table->boolean('esta_cancelado')->nullable();
            // $table->string('notas')->nullable();
            // $table->boolean('imprimir_nota')->nullable();
            $table->enum('forma_de_pago',['E','C','T','M'])->nullable();
            $table->string('referencia')->nullable();
            $table->decimal('total_devuelto')->nullable();
            $table->boolean('pendiente')->nullable();
            $table->decimal('total_ahorrado')->nullable();
            $table->decimal('total_credito')->nullable();
            $table->boolean('refrescar_ticket')->nullable();

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
        Schema::dropIfExists('ventatickets');
    }
}
