<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropColumnsCreadaEnInOrdenCompras extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orden_compras', function (Blueprint $table) {
            if (Schema::hasColumn('orden_compras', 'creada_en')) {
                Schema::table('orden_compras', function (Blueprint $table) {
                    $table->dropColumn('creada_en');
                });
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
