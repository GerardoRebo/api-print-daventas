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
        Schema::create('ventaticket_retention_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ventaticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('retention_rule_id')->constrained()->cascadeOnDelete();
            $table->string('regimen_fiscal', 10);
            $table->string('c_impuesto', 5);
            $table->decimal('tasa_cuota');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventaticket_retention_rules');
    }
};
