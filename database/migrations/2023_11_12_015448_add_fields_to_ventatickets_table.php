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
        Schema::table('ventatickets', function (Blueprint $table) {
            $table->string('pdf_factura_path')->nullable();
            $table->string('xml_factura_path')->nullable();
            $table->dateTime('facturado_en')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ventatickets', function (Blueprint $table) {
            $table->dropColumn("pdf_factura_path");
            $table->dropColumn('xml_factura_path');
            $table->dropColumn("facturado_en");
        });
    }
};
