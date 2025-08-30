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
            $table->string("slug")->nullable();
            $table->string("description")->nullable();
            $table->boolean('is_active')->default(true); // Por si deseas ocultar/desactivar
            $table->json('features')->nullable(); // Lista de beneficios del plan (opcional)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn("slug");
            $table->dropColumn("description");
            $table->dropColumn("is_active");
            $table->dropColumn("features");
        });
    }
};
