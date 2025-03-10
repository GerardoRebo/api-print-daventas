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
        Schema::table('ventaticket_articulos', function (Blueprint $table) {
            $table->decimal('area_total')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventaticket_articulos', function (Blueprint $table) {
            $table->dropColumn('area_total');
        });
    }
};
