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
            $table->dropColumn('is_factura_global');
            $table->foreignId('pre_factura_global_id')->after('ventaticket_id')->nullable()->constrained()->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pre_facturas', function (Blueprint $table) {
            $table->boolean('is_factura_global');
            $table->dropForeign(['pre_factura_global_id']);
            $table->dropColumn('pre_factura_global_id');
        });
    }
};
