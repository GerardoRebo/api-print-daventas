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
        Schema::table('retention_rules', function (Blueprint $table) {
            $table->string('regimen_fiscal', 10)->nullable()->change();
            // Drop foreign key and column for tax_id
            $table->dropForeign(['tax_id']);
            $table->dropColumn('tax_id');

            // Drop foreign key for organization_id
            $table->dropForeign(['organization_id']);

            // Make organization_id nullable and re-add foreign key
            $table->foreignId('organization_id')
                ->nullable()
                ->change();
            $table->foreign('organization_id')
                ->references('id')->on('organizations')
                ->nullOnDelete();

            $table->string('name');
            $table->decimal('isr_percentage')->nullable();
            $table->decimal('iva_percentage')->nullable();
            $table->enum('iva_type', ['iva', 'importe'])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('retention_rules', function (Blueprint $table) {
            // Add tax_id back
            $table->foreignId('tax_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // Drop the new nullable foreign key for organization_id
            $table->dropForeign(['organization_id']);

            // Make organization_id not nullable and re-add original constraint
            $table->foreignId('organization_id')
                ->nullable(false)
                ->change();

            $table->foreign('organization_id')
                ->references('id')->on('organizations')
                ->cascadeOnDelete();
        });
    }
};
