<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('set null');
            
            $table->string('name')->nullable();
            $table->string('codigo');
            $table->string('descripcion')->nullable();
            $table->enum('tventa',['U','G'])->nullable();
            $table->decimal('pcosto',19,3)->nullable()->default(0);
            $table->decimal('ucosto',19,3)->nullable()->default(0);
            $table->decimal('costoPromedio',19,3)->nullable();
            $table->boolean('prioridad')->nullable()->default(0);
            $table->boolean('es_presentacion_de_compra')->nullable()->default(0);
            $table->boolean('es_kit')->nullable()->default(0);
            $table->boolean('usa_impuestos')->nullable()->default(0);
            $table->boolean('es_publico')->nullable()->default(0);
            $table->decimal('porcentaje_ganancia')->nullable()->default(0);
            
            $table->unique(['codigo','organization_id']);
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
        Schema::dropIfExists('products');
    }
}
