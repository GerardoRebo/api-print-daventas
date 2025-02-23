<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAlmacensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('almacens', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');
            
            $table->string('name');
            $table->string('direccion')->nullable();
            $table->string('telefono')->nullable();
            $table->string('rfc')->nullable();
            $table->boolean('es_publico')->nullable()->default(0);
            $table->unique(['name','organization_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('almacens');
    }
}
