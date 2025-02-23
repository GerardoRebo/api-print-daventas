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
        Schema::create('cotizacions', function (Blueprint $table) {
            $table->id();
            $table->foreignId("organization_id")->constrained()->cascadeOnDelete();
            $table->foreignId("user_id")->nullable()->constrained()->nullOnDelete();
            $table->foreignId("ventaticket_id")->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId("turno_id")->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger("cart_id")->nullable()->unique();
            $table->unsignedBigInteger("cliente_foraneo_id")->nullable();
            $table->foreignId("cliente_id")->nullable()->constrained()->nullOnDelete();
            $table->foreignId("almacen_id")->nullable()->constrained()->nullOnDelete();
            $table->integer("consecutivo")->nullable();
            $table->string("nombre")->nullable();
            $table->decimal("subtotal", 12, 2)->default(0);
            $table->decimal("impuesto_traslado", 10, 2)->default(0);
            $table->decimal("impuesto_retenido", 10, 2)->default(0);
            $table->decimal("total", 12, 2)->default(0);
            $table->boolean("esta_abierto")->default(true);
            $table->dateTime("enviada_en")->nullable();
            $table->integer("numero_de_articulos")->default(0);
            $table->text("comentarios")->nullable();
            $table->boolean("cancelado")->default(false);
            $table->boolean("pendiente")->default(false);
            $table->boolean("archivado")->default(false);
            $table->decimal("descuento", 10, 2)->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cotizacions');
    }
};
