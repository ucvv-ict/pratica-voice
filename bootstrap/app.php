<?php

use App\Console\Commands\FascicoliCleanup;
use App\Console\Commands\ImportPdfFiles;
use App\Console\Commands\ImportPratiche;
use App\Console\Commands\IndexPdfCommand;
use App\Console\Commands\PdfAiIndexCommand;
use App\Console\Commands\RecordDeployCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('fascicoli:cleanup')->hourly();
    })
    ->withCommands([
        FascicoliCleanup::class,
        ImportPdfFiles::class,
        ImportPratiche::class,
        IndexPdfCommand::class,
        PdfAiIndexCommand::class,
        RecordDeployCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
