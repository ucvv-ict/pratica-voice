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

Route::get('/pdf/{cartella}/{file}', function ($cartella, $file) {
    $base = rtrim(config('pratica.pdf_base_path'), '/');
    $path = $base . '/' . $cartella . '/' . $file;

    if (!File::exists($path)) {
        abort(404);
    }

    // Impedisce directory traversal tipo ../../etc/
    if (str_contains($file, '..') || str_contains($cartella, '..')) {
        abort(400);
    }

    return response()->file($path);
})->where('file', '.*');
