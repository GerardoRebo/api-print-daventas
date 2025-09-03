<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\Plan;
use Illuminate\Console\Command;

class CheckExpiredPlans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plans:check-expired-plans';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Downgrade organizations with expired plans to the free plan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredOrganizations = Organization::whereHas('latestOrganizationPlan', function ($query) {
            $query->whereNotNull('ends_at')
                ->where('ends_at', '<', now())
                ->where('is_active', true);
        })->get();

        foreach ($expiredOrganizations as $org) {
            $org->resetDefaultAssets();
            $org->assignDefaultPlan();
            $this->info("Downgraded organization ID {$org->id} to free plan.");
        }

        return Command::SUCCESS;
    }
}
