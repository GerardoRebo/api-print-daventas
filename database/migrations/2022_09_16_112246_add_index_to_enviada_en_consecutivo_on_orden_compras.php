<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToEnviadaEnConsecutivoOnOrdenCompras extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orden_compras', function (Blueprint $table) {
            //
            $table->index('enviada_en');
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
        Schema::table('orden_compras', function (Blueprint $table) {
            //
            $table->dropIndex(['consecutivo']);
            $table->dropIndex(['enviada_en']);
        });
    }
}
