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
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('slug_name')->after('name')->unique()->nullable();
        });
        Schema::table('products', function (Blueprint $table) {
            $table->string('slug_name')->after('name')->unique()->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn('slug_name');
        });
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('slug_name');
        });
    }
};
