<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Uzytkownik; // Zaimportuj swój model Uzytkownik
use Illuminate\Support\Facades\Hash; // Do hashowania haseł
use Illuminate\Support\Str; // Do generowania remember_token

class UzytkownicyTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Użytkownik 1: Administrator, zweryfikowany
        Uzytkownik::firstOrCreate(
            ['email' => 'admin@example.com'], // Kryteria wyszukiwania
            [ // Wartości do utworzenia, jeśli nie znaleziono
                'imie' => 'Admin',
                'nazwisko' => 'Systemu',
                'telefon' => '111222333',
                'weryfikacja' => true,
                'administrator' => true,
                'haslo_hash' => Hash::make('password123'),
                'remember_token' => Str::random(10),
            ]
        );

        // Użytkownik 2: Zwykły użytkownik, zweryfikowany
        Uzytkownik::firstOrCreate(
            ['email' => 'samuelbitner06@gmail.com'],
            [
                'imie' => 'Jan',
                'nazwisko' => 'Kowalski',
                'telefon' => '444555666',
                'weryfikacja' => true,
                'administrator' => false,
                'haslo_hash' => Hash::make('password123'),
                'remember_token' => Str::random(10),
            ]
        );

        // Użytkownik 3: Zwykły użytkownik, niezweryfikowany
        Uzytkownik::firstOrCreate(
            ['email' => 'andrzejczabajski@gmail.com'],
            [
                'imie' => 'Andrzej',
                'nazwisko' => 'Czabajski',
                'telefon' => null,
                'weryfikacja' => true,
                'administrator' => false,
                'haslo_hash' => Hash::make('password123'),
                'remember_token' => Str::random(10),
            ]
        );

        // Użytkownik 4: Użytkownik do testów, zweryfikowany
        Uzytkownik::firstOrCreate(
            ['email' => 'tester@example.com'],
            [
                'imie' => 'Tester',
                'nazwisko' => 'Testowy',
                'telefon' => '777888999',
                'weryfikacja' => true,
                'administrator' => false,
                'haslo_hash' => Hash::make('tester123'),
                'remember_token' => Str::random(10),
            ]
        );
    }
}
