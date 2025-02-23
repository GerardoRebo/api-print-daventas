<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('organization_id')->nullable();
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');

            $table->unsignedBigInteger('organization_foranea_id')->nullable();
            $table->foreign('organization_foranea_id')->references('id')->on('organizations')->onDelete('set null');

            $table->string('name');
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->string('domicilio')->nullable();
            $table->decimal('total_ventas')->default(0)->nullable();
            $table->decimal('total_ganancias')->default(0)->nullable();
            $table->integer('total_tickets')->default(0)->nullable();
            $table->boolean('activo')->default(1);
            $table->decimal('saldo_actual')->default(0)->nullable();
            $table->decimal('limite_credito')->default(0)->nullable();
            $table->dateTime('ultimo_pago_en')->nullable();
            $table->unique(['name','organization_id']);
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
        Schema::dropIfExists('clientes');
    }
}
