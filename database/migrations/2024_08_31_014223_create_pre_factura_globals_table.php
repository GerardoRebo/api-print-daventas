<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('pre_factura_globals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->json('ticket_ids')->nullable();
            $table->string('detalles', 40)->nullable();
            $table->decimal('subtotal')->nullable();
            $table->decimal('descuento')->nullable();
            $table->decimal('impuesto_traslado')->nullable();
            $table->decimal('total')->nullable();
            $table->dateTime('terminado_en')->nullable();
            $table->dateTime('desde')->nullable();
            $table->dateTime('hasta')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pre_factura_globals', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
        });
        Schema::dropIfExists('pre_factura_globals');
    }
};
