<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToDevueltoEnOnDevoluciones extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('devoluciones', function (Blueprint $table) {
            //
            $table->index('devuelto_en');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('devoluciones', function (Blueprint $table) {
            //
            $table->dropIndex(['devuelto_en']);
        });
    }
}
