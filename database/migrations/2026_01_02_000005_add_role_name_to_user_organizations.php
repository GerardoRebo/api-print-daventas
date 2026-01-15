<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds role_name column to user_organizations to support 
     * role assignment per organization (not global).
     */
    public function up(): void
    {
        Schema::table('user_organizations', function (Blueprint $table) {
            // Add role_name column - stores the role this user has in this specific organization
            // e.g., 'Owner', 'Admin', 'Contador', 'Cajero', etc.
            $table->string('role_name')->nullable()->after('active');

            // Add index for faster role lookups
            $table->index('role_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_organizations', function (Blueprint $table) {
            $table->dropIndex(['role_name']);
            $table->dropColumn('role_name');
        });
    }
};
