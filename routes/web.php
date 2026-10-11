<?php

use App\Http\Controllers\CompanyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
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

