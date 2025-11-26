<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PraticaController;

Route::get('/pratica/{id}', [PraticaController::class, 'show']);


Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/dashboard/search', [DashboardController::class, 'search']);


Route::get('/', function () {
    return view('welcome');
});
