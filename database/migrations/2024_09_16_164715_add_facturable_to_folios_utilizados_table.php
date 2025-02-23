<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('folios_utilizados', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['ventaticket_id']);
            }
            $table->dropColumn('ventaticket_id');
        });
        Schema::table('folios_utilizados', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['venta_folio_id']);
            }
            $table->dropColumn('venta_folio_id');
        });
        Schema::table('folios_utilizados', function (Blueprint $table) {
            $table->morphs('facturable');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('folios_utilizados', function (Blueprint $table) {
            $table->foreignId('ventaticket_id')->constrained();
            $table->foreignId('venta_folio_id')->constrained();
            $table->dropMorphs('facturable');
        });
    }
};
