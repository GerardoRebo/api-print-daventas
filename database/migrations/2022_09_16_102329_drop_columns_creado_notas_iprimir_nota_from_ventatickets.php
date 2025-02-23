<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropColumnsCreadoNotasIprimirNotaFromVentatickets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasColumn('ventatickets', 'creado_en')) {
            Schema::table('ventatickets', function (Blueprint $table) {
                $table->dropColumn('creado_en');
            });
        }
        if (Schema::hasColumn('ventatickets', 'notas')) {
            Schema::table('ventatickets', function (Blueprint $table) {
                $table->dropColumn('notas');
            });
        }
        if (Schema::hasColumn('ventatickets', 'imprimir_nota')) {
            Schema::table('ventatickets', function (Blueprint $table) {
                $table->dropColumn('imprimir_nota');
            });
        }
        if (Schema::hasColumn('ventatickets', 'es_modificable')) {
            Schema::table('ventatickets', function (Blueprint $table) {
                $table->dropColumn('es_modificable');
            });
        }
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
