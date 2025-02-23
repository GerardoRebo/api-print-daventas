<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDevolucioneIdToDevolucionesArticulosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('devoluciones_articulos', 'devolucione_id')) {
            Schema::table('devoluciones_articulos', function (Blueprint $table) {
                $table->dropColumn('devolucione_id');
            });
        }
        Schema::table('devoluciones_articulos', function (Blueprint $table) {
            $table->foreignId('devolucione_id')->constrained()->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('devoluciones_articulos', function (Blueprint $table) {
            //
            $table->dropForeign(['devolucione_id']);
        });
    }
}
