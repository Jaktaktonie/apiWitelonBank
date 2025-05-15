<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Portfel;
use App\Models\Uzytkownik;

class PortfeleTableSeeder extends Seeder
{
    public function run(): void
    {
        $samuel = Uzytkownik::where('email', 'samuelbitner06@gmail.com')->first();
        $tester = Uzytkownik::where('email', 'tester@example.com')->first();

        if ($samuel) {
            Portfel::firstOrCreate(
                ['id_uzytkownika' => $samuel->id],
                [
                    'saldo_bitcoin' => 0.52345678,
                    'saldo_ethereum' => 2.12345678,
                ]
            );
        }

        if ($tester) {
            Portfel::firstOrCreate(
                ['id_uzytkownika' => $tester->id],
                [
                    'saldo_bitcoin' => 0.01000000,
                    'saldo_ethereum' => 0.00000000,
                ]
            );
        }
        // Admin i Anna mogą nie mieć portfeli krypto
    }
}
