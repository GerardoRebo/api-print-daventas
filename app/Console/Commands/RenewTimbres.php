<?php

namespace App\Console\Commands;

use App\Models\Organization;
use Illuminate\Console\Command;

class RenewTimbres extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:renew-timbres';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Agregar timbres a las organizaciones dependiendo de su plan';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $organizations = Organization::whereHas('latestOrganizationPlan.plan', function ($query) {
            $query->whereNotNull('timbres_mensuales');
        })->with('latestOrganizationPlan.plan')->get();

        foreach ($organizations as $org) {
            $plan = $org->latestOrganizationPlan->plan;

            $folioExists = $org->system_folios()
                ->where('facturable_type', 'Reseteo mensual')
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->exists();

            if ($folioExists) {
                continue; // solo salta esta organización, no toda la iteración
            }

            $org->setSystemFolios($plan->timbres_mensuales);
        }
    }
}
