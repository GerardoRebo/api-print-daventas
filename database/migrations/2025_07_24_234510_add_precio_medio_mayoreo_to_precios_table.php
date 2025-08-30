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
            $table->decimal('precio_medio_mayoreo', 12, 2)
                ->after('precio')
                ->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('precios', function (Blueprint $table) {
            $table->dropColumn('precio_medio_mayoreo');
        });
    }
};
