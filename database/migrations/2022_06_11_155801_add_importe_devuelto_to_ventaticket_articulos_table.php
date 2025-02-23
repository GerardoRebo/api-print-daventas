<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImporteDevueltoToVentaticketArticulosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ventaticket_articulos', function (Blueprint $table) {
            $table->decimal('importe_devuelto',12,2)->default(0.0)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ventaticket_articulos', function (Blueprint $table) {
            //
             $table->dropColumn('importe_devuelto');
        });
    }
}
