<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTurnosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('turnos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

            $table->unsignedBigInteger('organization_id')->nullable();
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');

            $table->unsignedBigInteger('ejercicio_id')->nullable();
            $table->foreign('ejercicio_id')->references('id')->on('ejercicios')->onDelete('set null');

            $table->integer('operacion_id_inicio')->nullable();
            $table->integer('operacion_id_fin')->nullable();
            $table->date('inicio_en')->nullable();
            $table->date('termino_en')->nullable();
            $table->decimal('dinero_inicial')->nullable()->default(0);
            $table->decimal('acumulado_ventas',19,2)->nullable()->default(0);
            $table->decimal('acumulado_entradas')->nullable()->default(0);
            $table->decimal('acumulado_salidas')->nullable()->default(0);
            $table->decimal('acumulado_ganancias',19,2)->nullable()->default(0);
            $table->decimal('compras',19,2)->nullable()->default(0);
            $table->decimal('ventas_efectivo',19,2)->nullable()->default(0);
            $table->decimal('ventas_tarjeta')->nullable()->default(0);
            $table->decimal('ventas_credito')->nullable()->default(0);
            $table->decimal('efectivo_al_cierre',19,2)->nullable()->default(0);
            $table->decimal('diferencia')->nullable()->default(0);
            $table->decimal('comments')->nullable()->default(0);
            $table->decimal('abonos_efectivo')->nullable()->default(0);
            $table->decimal('devoluciones_ventas_efectivo')->nullable()->default(0);
            $table->decimal('devoluciones_ventas_credito')->nullable()->default(0);
            $table->decimal('devoluciones_abonos_efectivo')->nullable()->default(0);
            $table->integer('numero_ventas')->nullable()->default(0);
            $table->decimal('abonos_tarjeta')->nullable()->default(0);
            $table->decimal('comisiones_tarjeta')->nullable()->default(0);
            $table->decimal('devoluciones_ventas')->nullable()->default(0);

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
        Schema::dropIfExists('turnos');
    }
}
