<?php

use App\Http\Controllers\Api\LeadApiController;
use Illuminate\Support\Facades\Route;

Route::middleware('truckfund.api')->group(function () {
    Route::post('/leads', [LeadApiController::class, 'store'])->name('api.leads.store');
});
