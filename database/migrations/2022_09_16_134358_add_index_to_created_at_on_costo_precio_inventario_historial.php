<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToCreatedAtOnCostoPrecioInventarioHistorial extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('costo_historials', function (Blueprint $table) {
            //
            $table->index('created_at');
        });
        Schema::table('precio_historials', function (Blueprint $table) {
            //
            $table->index('created_at');
        });
        Schema::table('invent_historials', function (Blueprint $table) {
            //
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('costo_historials', function (Blueprint $table) {
            //
            $table->dropIndex(['created_at']);
        });
        Schema::table('precio_historials', function (Blueprint $table) {
            //
            $table->dropIndex(['created_at']);
        });
        Schema::table('invent_historials', function (Blueprint $table) {
            //
            $table->dropIndex(['created_at']);
        });
    }
}
