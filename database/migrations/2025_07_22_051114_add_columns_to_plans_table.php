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
        Schema::table('plans', function (Blueprint $table) {
            $table->boolean('is_free');
            $table->boolean('is_public');
            $table->boolean('is_default');
            $table->boolean('is_popular');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('is_free');
            $table->dropColumn('is_public');
            $table->dropColumn('is_default');
            $table->dropColumn('is_popular');
        });
    }
};
