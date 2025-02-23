<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArticuloOcTaxesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('articulo_oc_taxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orden_compra_id')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');
                
            $table->foreignId('articulos_oc_id')
                ->constrained()
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreignId('tax_id')
                ->nullable()
                ->constrained()
                ->onUpdate('cascade')
                ->nullOnDelete();

            $table->decimal('cantidad', 16, 6);
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
        Schema::dropIfExists('articulo_oc_taxes');
    }
}
