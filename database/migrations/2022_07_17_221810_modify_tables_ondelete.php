<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ModifyTablesOndelete extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['organization_id']);
                $table->foreign('organization_id')->references('id')
                    ->on('users')->onUpdate('cascade')
                    ->onDelete('set null')->change();
            });
            Schema::table('codes', function (Blueprint $table) {
                $table->dropForeign(['product_id']);
                $table->foreign('product_id')->references('id')
                    ->on('products')
                    ->onUpdate('cascade')
                    ->onDelete('cascade')->change();
            });
            Schema::table('departamento_product', function (Blueprint $table) {
                $table->dropForeign(['product_id']);
                $table->foreign('product_id')->references('id')
                    ->on('products')
                    ->onUpdate('cascade')
                    ->onDelete('cascade')->change();
                $table->dropForeign(['departamento_id']);
                $table->foreign('departamento_id')->references('id')
                    ->on('departamentos')
                    ->onUpdate('cascade')
                    ->onDelete('cascade')->change();
            });
            Schema::table('articulos_ocs', function (Blueprint $table) {
                $table->dropForeign(['product_id']);
                $table->foreign('product_id')->references('id')
                    ->on('products')
                    ->onUpdate('cascade')
                    ->onDelete('set null')->change();
            });
            Schema::table('inventario_ajustes', function (Blueprint $table) {
                $table->dropForeign(['product_id']);
                $table->foreign('product_id')->references('id')
                    ->on('products')
                    ->onUpdate('cascade')
                    ->onDelete('set null')->change();

                $table->dropForeign(['user_id']);
                $table->foreign('user_id')->references('id')
                    ->on('users')
                    ->onUpdate('cascade')
                    ->onDelete('set null')->change();

                $table->dropForeign(['almacen_id']);
                $table->foreign('almacen_id')->references('id')
                    ->on('almacens')
                    ->onUpdate('cascade')
                    ->onDelete('set null')->change();
            });
            Schema::table('ventatickets', function (Blueprint $table) {

                $table->dropForeign(['cliente_id']);
                $table->foreign('cliente_id')->references('id')
                    ->on('clientes')
                    ->onUpdate('cascade')
                    ->onDelete('set null')->change();

                $table->dropForeign(['almacen_id']);
                $table->foreign('almacen_id')->references('id')
                    ->on('almacens')
                    ->onUpdate('cascade')
                    ->onDelete('set null')->change();
            });
            Schema::table('ventaticket_articulos', function (Blueprint $table) {
                $table->dropForeign(['product_id']);
                $table->foreign('product_id')->references('id')
                    ->on('products')
                    ->onUpdate('cascade')
                    ->onDelete('set null')->change();


                $table->dropForeign(['departamento_id']);
                $table->foreign('departamento_id')->references('id')
                    ->on('departamentos')
                    ->onUpdate('cascade')
                    ->onDelete('set null')->change();
            });
            Schema::table('deudas', function (Blueprint $table) {
                $table->dropForeign(['cliente_id']);
                $table->foreign('cliente_id')->references('id')
                    ->on('clientes')
                    ->onUpdate('cascade')
                    ->onDelete('set null')->change();
            });
            Schema::table('abonos', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->foreign('user_id')->references('id')
                    ->on('users')
                    ->onUpdate('cascade')
                    ->onDelete('set null')->change();

                $table->dropForeign(['turno_id']);
                $table->foreign('turno_id')->references('id')
                    ->on('turnos')
                    ->onUpdate('cascade')
                    ->onDelete('set null')->change();
            });
            Schema::table('inventario_historials', function (Blueprint $table) {

                $table->dropForeign(['product_id']);
                $table->foreign('product_id')->references('id')
                    ->on('products')
                    ->onUpdate('cascade')
                    ->onDelete('set null')->change();

                $table->dropForeign(['ventaticket_id']);
                $table->foreign('ventaticket_id')->references('id')
                    ->on('ventatickets')
                    ->onUpdate('cascade')
                    ->onDelete('set null')->change();

                $table->dropForeign(['user_id']);
                $table->foreign('user_id')->references('id')
                    ->on('users')
                    ->onUpdate('cascade')
                    ->onDelete('set null')->change();

                $table->dropForeign(['almacen_id']);
                $table->foreign('almacen_id')->references('id')
                    ->on('almacens')
                    ->onUpdate('cascade')
                    ->onDelete('set null')->change();
            });
            Schema::table('histories', function (Blueprint $table) {

                $table->dropForeign(['product_id']);
                $table->foreign('product_id')->references('id')
                    ->on('products')
                    ->onUpdate('cascade')
                    ->onDelete('set null')->change();

                $table->dropForeign(['user_id']);
                $table->foreign('user_id')->references('id')
                    ->on('users')
                    ->onUpdate('cascade')
                    ->onDelete('set null')->change();

                $table->dropForeign(['almacen_id']);
                $table->foreign('almacen_id')->references('id')
                    ->on('almacens')
                    ->onUpdate('cascade')
                    ->onDelete('set null')->change();
            });
            Schema::table('movimiento_cajas', function (Blueprint $table) {

                $table->dropForeign(['user_id']);
                $table->foreign('user_id')->references('id')
                    ->on('users')
                    ->onUpdate('cascade')
                    ->onDelete('set null')->change();

                $table->dropForeign(['turno_id']);
                $table->foreign('turno_id')->references('id')
                    ->on('turnos')
                    ->onUpdate('cascade')
                    ->onDelete('set null')->change();
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
        //
    }
}
