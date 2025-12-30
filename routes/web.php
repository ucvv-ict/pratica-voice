<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PraticaController;
use App\Http\Controllers\AccessoAttiController;
use App\Http\Controllers\FascicoloZipController;
use App\Http\Controllers\InfoSistemaController;

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

Route::post('/pratica/{id}/metadata', [PraticaController::class, 'storeMetadata'])
    ->name('pratica.metadata.store');

Route::get('/pratica/{praticaId}/fascicoli/{fascicoloId}/status',
    [PraticaController::class, 'fascicoloStatus']
)->name('pratica.fascicolo.status');

Route::get('/pratica/{praticaId}/fascicoli/{fascicoloId}/download',
    [PraticaController::class, 'downloadFascicolo']
)->name('pratica.fascicolo.download');

Route::get('/fascicoli/{id}/status', [FascicoloZipController::class, 'status'])
    ->name('fascicoli.status');
Route::get('/fascicoli/{id}/download', [FascicoloZipController::class, 'download'])
    ->name('fascicoli.download');

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

Route::post('/accesso-atti/{id}/r2',
    [AccessoAttiController::class, 'r2Upload']
)->name('accesso-atti.r2');

Route::delete('/accesso-atti/{id}', [AccessoAttiController::class, 'destroy'])
    ->name('accesso-atti.destroy');

/*
|--------------------------------------------------------------------------
| Servizio di apertura PDF dai filesystem esterni
|--------------------------------------------------------------------------
*/

Route::get('/pdf-laravel/{cartella}/{file}', function ($cartella, $file) {
    // decodifica singola senza trasformare "+" in spazio
    $cartella = rawurldecode($cartella);
    $file = rawurldecode($file);

    $base = \App\Support\Tenant::praticaPdfFolder($cartella);
    $path = $base . '/' . $file;

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
])->name('pdf.serve');

Route::get('/info-sistema', [InfoSistemaController::class, 'index'])
    ->name('info-sistema');
