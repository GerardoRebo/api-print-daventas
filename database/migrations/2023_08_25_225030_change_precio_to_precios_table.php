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
        Schema::table('precios', function (Blueprint $table) {
            $table->decimal('precio',19,3)->nullable()->default(0)->change();            
            $table->decimal('precio_mayoreo',19,3)->nullable()->default(0)->change();            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('precios', function (Blueprint $table) {
            //
        });
    }
};
