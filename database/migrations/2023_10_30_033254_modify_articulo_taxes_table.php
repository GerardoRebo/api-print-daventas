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
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('articulo_taxes', function (Blueprint $table) {
                $table->renameColumn('cantidad', 'importe');
            });
            Schema::table('articulo_taxes', function (Blueprint $table) {
                $table->decimal('base', 16, 6);
            });
            return;
        } else {
            Schema::table('articulo_taxes', function (Blueprint $table) {
                $table->renameColumn('cantidad', 'importe');
                $table->decimal('base', 16, 6);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
