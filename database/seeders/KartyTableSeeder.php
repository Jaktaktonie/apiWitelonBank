<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Karta;
use App\Models\Konto;
use Illuminate\Support\Facades\Hash;

class KartyTableSeeder extends Seeder
{
    public function run(): void
    {
        $kontoSamuela1 = Konto::where('nr_konta', 'PL22200022200022200022200022')->first();
        $kontoTstera = Konto::where('nr_konta', 'PL44400044400044400044400044')->first();

        if ($kontoSamuela1) {
            Karta::firstOrCreate(
                ['nr_karty' => '4111222233334444'], // Użyj bardziej realistycznych, ale fałszywych numerów
                [
                    'id_konta' => $kontoSamuela1->id,
                    'cvc_hash' => Hash::make('123'),
                    'data_waznosci' => now()->addYears(3)->format('Y-m-d'),
                    'zablokowana' => false,
                    'limit_dzienny' => 1000.00,
                ]
            );
        }

        if ($kontoTstera) {
            Karta::firstOrCreate(
                ['nr_karty' => '5100123412341234'],
                [
                    'id_konta' => $kontoTstera->id,
                    'cvc_hash' => Hash::make('456'),
                    'data_waznosci' => now()->addYears(2)->format('Y-m-d'),
                    'zablokowana' => false,
                    'limit_dzienny' => 500.00,
                ]
            );
            Karta::firstOrCreate(
                ['nr_karty' => '5200567856785678'], // Druga karta dla testera
                [
                    'id_konta' => $kontoTstera->id,
                    'cvc_hash' => Hash::make('789'),
                    'data_waznosci' => now()->addYears(4)->format('Y-m-d'),
                    'zablokowana' => true, // Ta karta jest zablokowana
                    'limit_dzienny' => 200.00,
                ]
            );
        }
        Karta::firstOrCreate(
            ['nr_karty' => '4163222243334444'], // Użyj bardziej realistycznych, ale fałszywych numerów
            [
                'id_konta' => 1,
                'cvc_hash' => Hash::make('123'),
                'data_waznosci' => now()->addYears(3)->format('Y-m-d'),
                'zablokowana' => false,
                'limit_dzienny' => 1000.00,
            ]
        );
        Karta::firstOrCreate(
            ['nr_karty' => '4321002234334444'], // Użyj bardziej realistycznych, ale fałszywych numerów
            [
                'id_konta' => 3,
                'cvc_hash' => Hash::make('123'),
                'data_waznosci' => now()->addYears(3)->format('Y-m-d'),
                'zablokowana' => false,
                'limit_dzienny' => 1000.00,
            ]
        );
        Karta::firstOrCreate(
            ['nr_karty' => '4114822238334444'], // Użyj bardziej realistycznych, ale fałszywych numerów
            [
                'id_konta' => 5,
                'cvc_hash' => Hash::make('123'),
                'data_waznosci' => now()->addYears(3)->format('Y-m-d'),
                'zablokowana' => false,
                'limit_dzienny' => 1000.00,
            ]
        );
        Karta::firstOrCreate(
            ['nr_karty' => '5112345678932454'], // Użyj bardziej realistycznych, ale fałszywych numerów
            [
                'id_konta' => 6,
                'cvc_hash' => Hash::make('123'),
                'data_waznosci' => now()->addYears(3)->format('Y-m-d'),
                'zablokowana' => false,
                'limit_dzienny' => 1000.00,
            ]
        );
    }
}
