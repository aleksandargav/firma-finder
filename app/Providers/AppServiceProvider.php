<?php

namespace App\Providers;

use App\Models\CompanyLead;
use App\Observers\CompanyLeadObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        CompanyLead::observe(CompanyLeadObserver::class);
    }

}
