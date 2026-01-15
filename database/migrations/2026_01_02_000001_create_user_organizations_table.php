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
        Schema::create('user_organizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('organization_id')->constrained('organizations')->onDelete('cascade');
            $table->foreignId('shard_id')->default(1)->comment('Shard ID for sharding architecture');
            $table->string('shard_connection')->default('mysql')->comment('Database connection name for this shard');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->onDelete('set null')->comment('User who assigned this user to org');
            $table->timestamp('assigned_at')->useCurrent();
            $table->boolean('active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            // Unique constraint: one user can only have one relationship per organization
            $table->unique(['user_id', 'organization_id']);

            // Indexes for common queries
            $table->index('organization_id');
            $table->index('shard_id');
            $table->index('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_organizations');
    }
};
