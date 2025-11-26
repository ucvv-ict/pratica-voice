<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PraticaController;

/*
|--------------------------------------------------------------------------
| Home → Dashboard
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect('/dashboard');
});

/*
|--------------------------------------------------------------------------
| Dashboard
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard');

Route::get('/dashboard/search', [DashboardController::class, 'search'])
    ->name('dashboard.search');

/*
|--------------------------------------------------------------------------
| Pratica
|--------------------------------------------------------------------------
*/

Route::get('/pratica/{id}', [PraticaController::class, 'show'])
    ->name('pratica.show');

Route::post('/pratica/{id}/zip', [PraticaController::class, 'downloadZip'])
    ->name('pratica.zip');
