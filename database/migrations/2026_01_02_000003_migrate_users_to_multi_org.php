<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all current users with organizations
        $usersWithOrgs = DB::table('users')
            ->whereNotNull('organization_id')
            ->get(['id', 'organization_id']);

        // Migrate existing relationships to new pivot table
        foreach ($usersWithOrgs as $user) {
            DB::table('user_organizations')->insert([
                'user_id' => $user->id,
                'organization_id' => $user->organization_id,
                'shard_id' => 1,
                'shard_connection' => 'mysql',
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create user_shards for all users
        $allUsers = DB::table('users')->get(['id']);
        foreach ($allUsers as $user) {
            DB::table('user_shards')->insertOrIgnore([
                'user_id' => $user->id,
                'shard_id' => 1,
                'shard_connection' => 'mysql',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('user_shards')->truncate();
        DB::table('user_organizations')->truncate();
    }
};
