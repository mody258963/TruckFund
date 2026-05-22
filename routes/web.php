<?php

use App\Livewire\Admin\CatalogManager;
use App\Livewire\Audit\AuditLogIndex;
use App\Livewire\Auth\Login;
use App\Livewire\Customers\CustomerOnboardingWizard;
use App\Livewire\Customers\CustomersIndex;
use App\Livewire\Dashboard;
use App\Livewire\Finance\FinanceApplicationShow;
use App\Livewire\Finance\FinanceApplicationsIndex;
use App\Livewire\Leads\LeadShow;
use App\Livewire\Leads\LeadsIndex;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', Dashboard::class)->name('dashboard');

    Route::get('/leads', LeadsIndex::class)->name('leads.index');
    Route::get('/leads/{lead}', LeadShow::class)->name('leads.show');

    Route::get('/customers', CustomersIndex::class)->name('customers.index');
    Route::get('/customers/{customer}/onboarding', CustomerOnboardingWizard::class)->name('customers.onboarding');

    Route::get('/finance', FinanceApplicationsIndex::class)->name('finance.index');
    Route::get('/finance/{application}', FinanceApplicationShow::class)->name('finance.show');

    Route::get('/admin/{type}', CatalogManager::class)
        ->where('type', 'merchants|financial-products|auto-products|suppliers')
        ->name('admin.catalog');

    Route::get('/audit', AuditLogIndex::class)->name('audit.index');
});
