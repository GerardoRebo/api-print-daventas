<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToPagadoEnOnVentaticket extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ventatickets', function (Blueprint $table) {
            //
            $table->index('pagado_en');
            $table->index('consecutivo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ventatickets', function (Blueprint $table) {
            //
            $table->dropIndex(['pagado_en']);
            $table->dropIndex(['consecutivo']);
        });
    }
}
