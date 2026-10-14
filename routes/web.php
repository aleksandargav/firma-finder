<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyDataPointController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('companies.index');
});

Route::get('/companies', [CompanyController::class, 'index'])
    ->name('companies.index');

Route::get('/leads', [CompanyController::class, 'leadsIndex'])
    ->name('leads.index');

Route::get('/companies/{company:registration_number}', [CompanyController::class, 'show'])
    ->name('companies.show');

Route::post('/companies/{company:registration_number}/lead', [CompanyController::class, 'storeLead'])
    ->name('companies.leads.store');

Route::put('/companies/{company:registration_number}/lead', [CompanyController::class, 'updateLead'])
    ->name('companies.leads.update');

Route::post('/companies/{company:registration_number}/website-audits', [CompanyController::class, 'storeWebsiteAudit'])
    ->name('companies.website-audits.store');

Route::post('/website-audits/{audit}/run', [CompanyController::class, 'runWebsiteAudit'])
    ->name('website-audits.run');

Route::post('/companies/{company:registration_number}/data-points', [CompanyDataPointController::class, 'store'])
    ->name('companies.data-points.store');

Route::post('/data-points/{dataPoint}/verify', [CompanyDataPointController::class, 'verify'])
    ->name('data-points.verify');

Route::post('/data-points/{dataPoint}/reject', [CompanyDataPointController::class, 'reject'])
    ->name('data-points.reject');

Route::post('/data-points/{dataPoint}/needs-review', [CompanyDataPointController::class, 'needsReview'])
    ->name('data-points.needs-review');
