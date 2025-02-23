<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDevolucionesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('devoluciones', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');

            $table->unsignedBigInteger('ventaticket_id')->nullable();
            $table->foreign('ventaticket_id')->references('id')->on('ventatickets')->onDelete('cascade');

            $table->unsignedBigInteger('turno_id')->nullable();
            $table->foreign('turno_id')->references('id')->on('turnos')->onDelete('cascade');

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->enum('tipo_devolucion',['C','P']);//C=realizada P=pendiente
            $table->dateTime('pagado_en');
            $table->dateTime('devuelto_en');
            $table->decimal('total_devuelto');

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
        Schema::dropIfExists('devoluciones');
    }
}
