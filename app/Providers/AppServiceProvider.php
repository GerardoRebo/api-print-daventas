<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\InventarioBalance;
use App\Observers\InventarioBalanceObserver;
use Illuminate\Database\Eloquent\Model;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        InventarioBalance::observe(InventarioBalanceObserver::class);
        Model::preventLazyLoading(! app()->isProduction());
    }
}
