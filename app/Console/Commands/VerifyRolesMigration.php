<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class VerifyRolesMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roles:verify-migration';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify that roles have been correctly migrated from model_has_roles to user_organizations';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Verifying role migration...');
        $this->newLine();

        $users = User::with(['organizations' => function ($query) {
            $query->select('organizations.id', 'organizations.name');
        }])->get();

        $migratedCount = 0;
        $notMigratedCount = 0;
        $noOrganizationCount = 0;

        foreach ($users as $user) {
            // Get role from Spatie
            $spatieRole = DB::table('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('model_has_roles.model_id', $user->id)
                ->where('model_has_roles.model_type', 'App\\Models\\User')
                ->pluck('roles.name')
                ->first();

            if (!$spatieRole) {
                continue; // Skip users without roles
            }

            $userOrg = $user->organizations()->first();

            if (!$userOrg) {
                $this->warn("❌ User {$user->name} (ID: {$user->id}) - Has Spatie role '{$spatieRole}' but NO organization");
                $noOrganizationCount++;
                continue;
            }

            $pivotRole = DB::table('user_organizations')
                ->where('user_id', $user->id)
                ->where('organization_id', $userOrg->id)
                ->value('role_name');

            if ($pivotRole === $spatieRole) {
                $this->info("✓ User {$user->name} (ID: {$user->id}) - Role '{$spatieRole}' in '{$userOrg->name}' ✓");
                $migratedCount++;
            } else {
                $this->error("❌ User {$user->name} (ID: {$user->id}) - Spatie: '{$spatieRole}' vs Pivot: '{$pivotRole}'");
                $notMigratedCount++;
            }
        }

        $this->newLine();
        $this->info("Migration Summary:");
        $this->line("  ✓ Successfully migrated: $migratedCount");
        $this->line("  ❌ Failed/Mismatched: $notMigratedCount");
        $this->line("  ⚠ No organization: $noOrganizationCount");
        $this->newLine();

        if ($notMigratedCount === 0 && $noOrganizationCount === 0) {
            $this->info('✓ All roles successfully migrated!');
            return 0;
        } else {
            $this->warn('⚠ Some roles could not be migrated. Review the errors above.');
            return 1;
        }
    }
}
