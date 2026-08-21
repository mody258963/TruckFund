<?php

use App\Http\Controllers\FinanceApplicationPdfController;
use App\Http\Controllers\StoredFileController;
use App\Livewire\Admin\CatalogManager;
use App\Livewire\Admin\Settings;
use App\Livewire\Audit\AuditLogIndex;
use App\Livewire\Auth\Login;
use App\Livewire\Customers\CustomerOnboardingWizard;
use App\Livewire\Customers\CustomerShow;
use App\Livewire\Customers\CustomersIndex;
use App\Livewire\Dashboard;
use App\Livewire\Finance\FinanceApplicationShow;
use App\Livewire\Finance\FinanceApplicationsIndex;
use App\Livewire\Freelancers\FreelancersIndex;
use App\Livewire\Leads\LeadShow;
use App\Livewire\Leads\LeadsIndex;
use App\Livewire\Users\UsersIndex;
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
    Route::get('/dashboard', function () {
        if (auth()->user()?->isMerchantAgent()) {
            return new \Illuminate\Http\RedirectResponse(route('finance.index'));
        }

        return app(Dashboard::class)();
    })->name('dashboard');

    Route::get('/leads', LeadsIndex::class)->name('leads.index');
    Route::get('/leads/{lead}', LeadShow::class)->name('leads.show');

    Route::get('/users', UsersIndex::class)->name('users.index');
    Route::get('/references', FreelancersIndex::class)->name('freelancers.index');

    Route::get('/customers', CustomersIndex::class)->name('customers.index');
    Route::get('/customers/{customer}', CustomerShow::class)->name('customers.show');
    Route::get('/customers/{customer}/onboarding', CustomerOnboardingWizard::class)->name('customers.onboarding');

    Route::get('/finance', FinanceApplicationsIndex::class)->name('finance.index');
    Route::get('/finance/{application}', FinanceApplicationShow::class)->name('finance.show');
    Route::get('/finance/{application}/pdf', FinanceApplicationPdfController::class)->name('finance.pdf');

    Route::get('/admin/{type}', CatalogManager::class)
        ->where('type', 'merchants|financial-products|auto-products|suppliers')
        ->name('admin.catalog');

    Route::get('/audit', AuditLogIndex::class)->name('audit.index');

    Route::get('/admin/settings', Settings::class)->name('admin.settings');

    Route::get('/files/{path}', [StoredFileController::class, 'show'])
        ->where('path', '[A-Za-z0-9_\-]+')
        ->name('files.show');
});
