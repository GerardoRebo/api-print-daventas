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
        Schema::create('user_shards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('shard_id')->default(1)->comment('Assigned shard ID for this user');
            $table->string('shard_connection')->default('mysql')->comment('Database connection name for user lookup');
            $table->string('shard_location')->nullable()->comment('Connection string or location identifier for this shard');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();

            // Indexes for fast lookups
            $table->index('shard_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_shards');
    }
};
