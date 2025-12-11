<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PraticaController;
use App\Http\Controllers\AccessoAttiController;

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

/*
|--------------------------------------------------------------------------
| Accesso agli Atti
|--------------------------------------------------------------------------
*/

Route::get('/pratica/{praticaId}/accesso-atti',
    [AccessoAttiController::class, 'index']
)->name('accesso-atti.index');

Route::get('/pratica/{praticaId}/accesso-atti/create',
    [AccessoAttiController::class, 'create']
)->name('accesso-atti.create');

Route::post('/pratica/{praticaId}/accesso-atti',
    [AccessoAttiController::class, 'store']
)->name('accesso-atti.store');

Route::get('/accesso-atti/{id}',
    [AccessoAttiController::class, 'show']
)->name('accesso-atti.show');

Route::get('/accesso-atti/{id}/download',
    [AccessoAttiController::class, 'download']
)->name('accesso-atti.download');

Route::put('/accesso-atti/{id}', [AccessoAttiController::class, 'update'])
    ->name('accesso-atti.update');

Route::get('/accesso-atti/{id}/duplica',
    [AccessoAttiController::class, 'duplica']
)->name('accesso-atti.duplica');

Route::get('/accesso-atti/{id}/preview/{elementoId}', 
    [AccessoAttiController::class, 'previewElemento']
)->name('accesso-atti.preview-elemento');

Route::get('/accesso-atti/{id}/preview',
    [AccessoAttiController::class, 'previewFascicolo']
)->name('accesso-atti.preview');

Route::get('/accesso-atti/{id}/ordinamento', 
    [AccessoAttiController::class, 'editOrdinamento']
)->name('accesso-atti.ordinamento');

Route::post('/accesso-atti/{id}/ordinamento',
    [AccessoAttiController::class, 'saveOrdinamento']
)->name('accesso-atti.ordinamento.salva');

Route::get('/accesso-atti/{id}/preview-inline', 
    [AccessoAttiController::class, 'previewInline']
)->name('accesso-atti.preview.inline');

Route::delete('/accesso-atti/{id}', [AccessoAttiController::class, 'destroy'])
    ->name('accesso-atti.destroy');

/*
|--------------------------------------------------------------------------
| Servizio di apertura PDF dai filesystem esterni
|--------------------------------------------------------------------------
*/

Route::get('/pdf/{cartella}/{file}', function ($cartella, $file) {
    // decodifica segmenti per gestire spazi e caratteri speciali
    $cartella = urldecode($cartella);
    $file = urldecode($file);

    $base = rtrim(config('pratica.pdf_base_path'), '/');
    $path = $base . '/' . $cartella . '/' . $file;

    // blocca directory traversal
    if (str_contains($file, '..') || str_contains($cartella, '..') || str_contains($file, '/')) {
        abort(400);
    }

    // consenti solo PDF (anche con estensione maiuscola)
    if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'pdf') {
        abort(400);
    }

    if (!File::exists($path)) {
        abort(404);
    }

    return response()->file($path);
})
// accetta cartelle/file con spazi o caratteri speciali, ma senza slash
->where([
    'cartella' => '[^/]+',
    'file'     => '[^/]+',
]);
