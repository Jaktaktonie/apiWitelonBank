<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ZlecenieStale;
use App\Models\Uzytkownik;
use App\Models\Konto;

class ZleceniaStaleTableSeeder extends Seeder
{
    public function run(): void
    {
        $samuel = Uzytkownik::where('email', 'samuelbitner06@gmail.com')->first();
        $kontoSamuelaPLN = Konto::where('nr_konta', 'PL22200022200022200022200022')->first();

        if ($samuel && $kontoSamuelaPLN) {
            ZlecenieStale::firstOrCreate(
                [ // Kryteria do wyszukania (np. unikalna kombinacja)
                    'id_uzytkownika' => $samuel->id,
                    'id_konta_zrodlowego' => $kontoSamuelaPLN->id,
                    'nr_konta_docelowego' => 'PL00011122233344455566677788',
                ],
                [ // Wartości do utworzenia
                    'nazwa_odbiorcy' => 'Fundacja Pomocy Dzieciom',
                    'tytul_przelewu' => 'Comiesięczna darowizna',
                    'kwota' => 100.00,
                    'czestotliwosc' => 'miesiecznie',
                    'data_startu' => now()->startOfMonth()->format('Y-m-d'),
                    'data_nastepnego_wykonania' => now()->addMonth()->startOfMonth()->format('Y-m-d'),
                    'aktywne' => true,
                ]
            );

            ZlecenieStale::firstOrCreate(
                [
                    'id_uzytkownika' => $samuel->id,
                    'id_konta_zrodlowego' => $kontoSamuelaPLN->id,
                    'nr_konta_docelowego' => 'PL10110110110110110110110110',
                ],
                [
                    'nazwa_odbiorcy' => 'Netflix Subskrypcja',
                    'tytul_przelewu' => 'Opłata miesięczna Netflix',
                    'kwota' => 43.00,
                    'czestotliwosc' => 'miesiecznie',
                    'data_startu' => now()->day(15)->format('Y-m-d'), // 15-go każdego miesiąca
                    'data_nastepnego_wykonania' => now()->addMonth()->day(15)->format('Y-m-d'),
                    'aktywne' => true,
                ]
            );
        }
    }
}
