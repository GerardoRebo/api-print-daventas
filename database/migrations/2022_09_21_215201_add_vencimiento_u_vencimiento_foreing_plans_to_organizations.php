<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVencimientoUVencimientoForeingPlansToOrganizations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->foreignId('plan_id')
                ->nullable()
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('set null');
            $table->dateTime('vencimiento')->nullable();
            $table->dateTime('u_vencimiento')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('organizations', function (Blueprint $table) {
            //
            $table->dropColumn('vencimiento');
            $table->dropColumn('u_vencimiento');
            $table->dropForeign(['plan_id']);
            $table->dropColumn('plan_id');
        });
    }
}
