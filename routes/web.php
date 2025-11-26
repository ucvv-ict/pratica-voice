<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PraticaController;

Route::get('/pratica/{id}', [PraticaController::class, 'show']);


Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/dashboard/search', [DashboardController::class, 'search']);
Route::post('/pratica/{id}/zip', [\App\Http\Controllers\PraticaController::class, 'downloadZip']);


Route::get('/', function () {
    return view('welcome');
});
