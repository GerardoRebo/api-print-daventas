<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ModifyTableChangeOrganizationIdForeingOnUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }
        Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['organization_id']);
                $table->foreign('organization_id')->references('id')
                    ->on('organizations')->onUpdate('cascade')
                    ->onDelete('set null')->change();
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
}
