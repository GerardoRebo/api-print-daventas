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
        Schema::table('pre_facturas', function (Blueprint $table) {
            $table->renameColumn('impuesto', 'impuesto_traslado');
        });
        Schema::table('pre_facturas', function (Blueprint $table) {
            // $table->foreignId('pre_factura_global_id')->after('ventaticket_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('impuesto_retenido')->after('impuesto_traslado')->nullable();
            $table->boolean('is_factura_global')->nullable()->default(false);
            $table->json('impuesto_traslado_array')->nullable();
            $table->json('impuesto_retenido_array')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pre_facturas', function (Blueprint $table) {
            $table->dropForeign(['pre_factura_global_id']);
            $table->dropColumn('pre_factura_global_id');
            $table->dropColumn('is_factura_global')->nullable()->default(false);
            $table->dropColumn('impuesto_retenido');
            $table->dropColumn('impuesto_traslado_array');
            $table->dropColumn('impuesto_retenido_array');
        });
    }
};
