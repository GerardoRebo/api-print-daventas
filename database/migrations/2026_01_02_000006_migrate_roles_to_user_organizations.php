<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\UserOrganization;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Migrates roles from Spatie's model_has_roles to user_organizations.role_name
     * Since all users currently have only one organization, we migrate their role
     * from model_has_roles to the user_organizations pivot table.
     */
    public function up(): void
    {
        // Get all users and migrate their roles
        $users = User::all();

        foreach ($users as $user) {
            // Get the user's current role from Spatie Permissions
            $spatieRole = DB::table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('model_has_roles.model_id', $user->id)
                ->where('model_has_roles.model_type', 'App\\Models\\User')
                ->pluck('roles.name')
                ->first();

            // If user has a role and is connected to an organization, update the pivot table
            if ($spatieRole) {
                $userOrganization = UserOrganization::where('user_id', $user->id)->first();

                if ($userOrganization) {
                    // Update the role_name in user_organizations
                    $userOrganization->update([
                        'role_name' => $spatieRole
                    ]);
                }
            }
        }

        // Optional: Log migration completion
        echo "\n✓ Roles migrated from model_has_roles to user_organizations\n";
    }

    /**
     * Reverse the migrations.
     * 
     * Clears role_name from user_organizations (doesn't touch model_has_roles in case it's still needed)
     */
    public function down(): void
    {
        DB::table('user_organizations')
            ->update(['role_name' => null]);
    }
};
