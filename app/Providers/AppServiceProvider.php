<?php

namespace App\Providers;

use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\CatalogRepositoryInterface;
use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Contracts\Repositories\FinanceApplicationRepositoryInterface;
use App\Contracts\Repositories\LeadRepositoryInterface;
use App\Models\Customer;
use App\Models\FinanceApplication;
use App\Models\Lead;
use App\Observers\AuditableObserver;
use App\Repositories\Eloquent\AuditLogRepository;
use App\Repositories\Eloquent\CatalogRepository;
use App\Repositories\Eloquent\CustomerRepository;
use App\Repositories\Eloquent\FinanceApplicationRepository;
use App\Repositories\Eloquent\LeadRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LeadRepositoryInterface::class, LeadRepository::class);
        $this->app->bind(CustomerRepositoryInterface::class, CustomerRepository::class);
        $this->app->bind(FinanceApplicationRepositoryInterface::class, FinanceApplicationRepository::class);
        $this->app->bind(CatalogRepositoryInterface::class, CatalogRepository::class);
        $this->app->bind(AuditLogRepositoryInterface::class, AuditLogRepository::class);
    }

    public function boot(): void
    {
        Lead::observe(AuditableObserver::class);
        Customer::observe(AuditableObserver::class);
        FinanceApplication::observe(AuditableObserver::class);
    }
}
