<?php

use Illuminate\Support\Facades\Schedule; // Dodaj tę linię
use Illuminate\Foundation\Console\ClosureCommand;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    /** @var ClosureCommand $this */
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// Twoje zaplanowane zadanie do przetwarzania płatności cyklicznych:
Schedule::command('payments:process-scheduled')
    ->dailyAt('01:00') // Uruchom codziennie o 1:00 w nocy
    ->timezone('Europe/Warsaw') // Ustaw swoją strefę czasową
    ->withoutOverlapping(10) // Zapobiega nakładaniu się, jeśli poprzednie trwało długo (10 minut)
    ->onFailure(function () {
        // Opcjonalnie: obsługa błędów, np. wysłanie emaila lub logowanie
        \Illuminate\Support\Facades\Log::critical('Scheduler: Zadanie payments:process-scheduled nie powiodło się!');
    })
    ->onSuccess(function () {
        // Opcjonalnie: logowanie sukcesu
        \Illuminate\Support\Facades\Log::info('Scheduler: Zadanie payments:process-scheduled wykonane pomyślnie.');
    });
