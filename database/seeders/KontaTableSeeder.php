<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Konto;
use App\Models\Uzytkownik; // Aby pobrać ID użytkowników

class KontaTableSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Uzytkownik::where('email', 'admin@example.com')->first();
        $samuel = Uzytkownik::where('email', 'samuelbitner06@gmail.com')->first();
        $tester = Uzytkownik::where('email', 'tester@example.com')->first();
        // Anna Nowak jest niezweryfikowana, więc może nie mieć konta, albo mieć zablokowane, zależy od logiki aplikacji

        if ($admin) {
            Konto::firstOrCreate(
                ['nr_konta' => 'PL11100011100011100011100011'],
                [
                    'id_uzytkownika' => $admin->id,
                    'saldo' => 50000.00,
                    'limit_przelewu' => 10000.00,
                    'zablokowane' => false,
                    'waluta' => 'PLN',
                ]
            );
        }

        if ($samuel) {
            Konto::firstOrCreate(
                ['nr_konta' => 'PL22200022200022200022200022'],
                [
                    'id_uzytkownika' => $samuel->id,
                    'saldo' => 15000.75,
                    'limit_przelewu' => 5000.00,
                    'zablokowane' => false,
                    'waluta' => 'PLN',
                ]
            );
            Konto::firstOrCreate(
                ['nr_konta' => 'PL33300033300033300033300033'], // Drugie konto dla Samuela
                [
                    'id_uzytkownika' => $samuel->id,
                    'saldo' => 250.00,
                    'limit_przelewu' => 1000.00,
                    'zablokowane' => false,
                    'waluta' => 'EUR', // Załóżmy konto walutowe
                ]
            );
        }

        if ($tester) {
            Konto::firstOrCreate(
                ['nr_konta' => 'PL44400044400044400044400044'],
                [
                    'id_uzytkownika' => $tester->id,
                    'saldo' => 1234.56,
                    'limit_przelewu' => 2000.00,
                    'zablokowane' => false,
                    'waluta' => 'PLN',
                ]
            );
        }
    }
}
