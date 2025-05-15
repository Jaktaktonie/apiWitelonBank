<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Konto;
use App\Models\Przelew;
// Uzytkownik będzie pobierany przez relację z Konto, więc bezpośredni import nie jest tu kluczowy
use Illuminate\Support\Carbon; // Do manipulacji datami

class PrzelewyTableSeeder extends Seeder
{
    public function run(): void
    {
        // --- Pobieranie kont na podstawie numerów kont (zgodnie z Twoim KontaTableSeeder) ---
        // Używamy ->with('uzytkownik'), aby od razu załadować dane użytkownika (imię, nazwisko)
        $kontoAdminPLN = Konto::where('nr_konta', 'PL11100011100011100011100011')->with('uzytkownik')->first();
        $kontoSamuelaPLN = Konto::where('nr_konta', 'PL22200022200022200022200022')->with('uzytkownik')->first();
        $kontoSamuelaEUR = Konto::where('nr_konta', 'PL33300033300033300033300033')->with('uzytkownik')->first();
        $kontoTesteraPLN = Konto::where('nr_konta', 'PL44400044400044400044400044')->with('uzytkownik')->first();

        // Sprawdzenie, czy konta zostały znalezione i mają załadowanych użytkowników
        if (!$kontoAdminPLN || !$kontoAdminPLN->uzytkownik) {
            $this->command->warn('Nie znaleziono konta Admina PLN lub jego użytkownika. Niektóre przelewy mogą nie zostać utworzone.');
        }
        if (!$kontoSamuelaPLN || !$kontoSamuelaPLN->uzytkownik) {
            $this->command->warn('Nie znaleziono konta Samuela PLN lub jego użytkownika. Niektóre przelewy mogą nie zostać utworzone.');
        }
        if (!$kontoSamuelaEUR || !$kontoSamuelaEUR->uzytkownik) {
            $this->command->warn('Nie znaleziono konta Samuela EUR lub jego użytkownika. Niektóre przelewy mogą nie zostać utworzone.');
        }
        if (!$kontoTesteraPLN || !$kontoTesteraPLN->uzytkownik) {
            $this->command->warn('Nie znaleziono konta Testera PLN lub jego użytkownika. Niektóre przelewy mogą nie zostać utworzone.');
        }

        // Dane dla odbiorców zewnętrznych (pozostają fikcyjne)
        $nrKontaOdbiorcyZewnetrznego1 = 'PL99123456789012345678901234';
        $nazwaOdbiorcyZewnetrznego1 = 'Firma "Tech Solutions" Sp. z o.o.';
        $adresOdbiorcyZewnetrznego1Linia1 = 'ul. Innowacyjna 15';
        $adresOdbiorcyZewnetrznego1Linia2 = '02-591 Warszawa';

        $nrKontaOdbiorcyZewnetrznego2 = 'PL11098765432109876543210987';
        $nazwaOdbiorcyZewnetrznego2 = 'Sklep "Gadżetowo" Anna Bąk';
        $adresOdbiorcyZewnetrznego2Linia1 = 'Rynek Główny 7';
        $adresOdbiorcyZewnetrznego2Linia2 = '31-042 Kraków';

        // --- Tworzenie przelewów ---

        // 1. Przelew od Admina (PLN) do Samuela (PLN) - zrealizowany
        if ($kontoAdminPLN && $kontoSamuelaPLN && $kontoAdminPLN->uzytkownik && $kontoSamuelaPLN->uzytkownik) {
            Przelew::firstOrCreate(
                [
                    'id_konta_nadawcy' => $kontoAdminPLN->id,
                    'nr_konta_odbiorcy' => $kontoSamuelaPLN->nr_konta,
                    'kwota' => 250.00,
                    'data_zlecenia' => Carbon::now()->subDays(8)->setTime(10, 30, 00),
                ],
                [
                    'nazwa_odbiorcy' => $kontoSamuelaPLN->uzytkownik->imie . ' ' . $kontoSamuelaPLN->uzytkownik->nazwisko,
                    'adres_odbiorcy_linia1' => 'ul. Programistów 7', // Przykładowy adres
                    'adres_odbiorcy_linia2' => '80-298 Gdańsk',
                    'tytul' => 'Za wykonane zlecenie graficzne',
                    'waluta_przelewu' => 'PLN',
                    'status' => 'zrealizowany',
                    'data_realizacji' => Carbon::now()->subDays(8)->setTime(14, 15, 00),
                    'informacja_zwrotna' => 'Dziękujemy za współpracę.',
                ]
            );
        }

        // 2. Przelew od Samuela (PLN) do Testera (PLN) - oczekujący
        if ($kontoSamuelaPLN && $kontoTesteraPLN && $kontoSamuelaPLN->uzytkownik && $kontoTesteraPLN->uzytkownik) {
            Przelew::firstOrCreate(
                [
                    'id_konta_nadawcy' => $kontoSamuelaPLN->id,
                    'nr_konta_odbiorcy' => $kontoTesteraPLN->nr_konta,
                    'kwota' => 75.99,
                    'data_zlecenia' => Carbon::now()->subHours(3)->setTime(Carbon::now()->hour, 10, 00),
                ],
                [
                    'nazwa_odbiorcy' => $kontoTesteraPLN->uzytkownik->imie . ' ' . $kontoTesteraPLN->uzytkownik->nazwisko,
                    'adres_odbiorcy_linia1' => 'ul. Developerska 12/3',
                    'adres_odbiorcy_linia2' => '50-370 Wrocław',
                    'tytul' => 'Zwrot za bilety na konferencję',
                    'waluta_przelewu' => 'PLN',
                    'status' => 'oczekujacy',
                    'data_realizacji' => null,
                    'informacja_zwrotna' => null,
                ]
            );
        }

        // 3. Przelew od Testera (PLN) do Admina (PLN) - zrealizowany, starszy
        if ($kontoTesteraPLN && $kontoAdminPLN && $kontoTesteraPLN->uzytkownik && $kontoAdminPLN->uzytkownik) {
            Przelew::firstOrCreate(
                [
                    'id_konta_nadawcy' => $kontoTesteraPLN->id,
                    'nr_konta_odbiorcy' => $kontoAdminPLN->nr_konta,
                    'kwota' => 120.00,
                    'data_zlecenia' => Carbon::now()->subMonths(2)->day(15)->setTime(9, 0, 0),
                ],
                [
                    'nazwa_odbiorcy' => $kontoAdminPLN->uzytkownik->imie . ' ' . $kontoAdminPLN->uzytkownik->nazwisko,
                    'adres_odbiorcy_linia1' => 'ul. Centralna 1',
                    'adres_odbiorcy_linia2' => '00-001 Warszawa',
                    'tytul' => 'Opłata za subskrypcję premium',
                    'waluta_przelewu' => 'PLN',
                    'status' => 'zrealizowany',
                    'data_realizacji' => Carbon::now()->subMonths(2)->day(15)->setTime(11, 30, 0),
                    'informacja_zwrotna' => 'Płatność zaakceptowana.',
                ]
            );
        }

        // 4. Przelew od Samuela (PLN) do Odbiorcy Zewnętrznego 1 - odrzucony
        if ($kontoSamuelaPLN && $kontoSamuelaPLN->uzytkownik) {
            Przelew::firstOrCreate(
                [
                    'id_konta_nadawcy' => $kontoSamuelaPLN->id,
                    'nr_konta_odbiorcy' => $nrKontaOdbiorcyZewnetrznego1,
                    'kwota' => 15000.00, // Kwota przekraczająca saldo lub limit
                    'data_zlecenia' => Carbon::now()->subDays(5)->setTime(16, 0, 0),
                ],
                [
                    'nazwa_odbiorcy' => $nazwaOdbiorcyZewnetrznego1,
                    'adres_odbiorcy_linia1' => $adresOdbiorcyZewnetrznego1Linia1,
                    'adres_odbiorcy_linia2' => $adresOdbiorcyZewnetrznego1Linia2,
                    'tytul' => 'Faktura Proforma FP/2024/XYZ',
                    'waluta_przelewu' => 'PLN',
                    'status' => 'odrzucony',
                    'data_realizacji' => Carbon::now()->subDays(5)->setTime(16, 5, 0), // Data odrzucenia
                    'informacja_zwrotna' => 'Przekroczono limit dzienny przelewu lub niewystarczające środki.',
                ]
            );
        }

        // 5. Przelew od Admina (PLN) do Odbiorcy Zewnętrznego 2 - zrealizowany
        if ($kontoAdminPLN && $kontoAdminPLN->uzytkownik) {
            Przelew::firstOrCreate(
                [
                    'id_konta_nadawcy' => $kontoAdminPLN->id,
                    'nr_konta_odbiorcy' => $nrKontaOdbiorcyZewnetrznego2,
                    'kwota' => 89.90,
                    'data_zlecenia' => Carbon::now()->subDays(2)->setTime(11, 0, 0),
                ],
                [
                    'nazwa_odbiorcy' => $nazwaOdbiorcyZewnetrznego2,
                    'adres_odbiorcy_linia1' => $adresOdbiorcyZewnetrznego2Linia1,
                    'adres_odbiorcy_linia2' => $adresOdbiorcyZewnetrznego2Linia2,
                    'tytul' => 'Zakup w sklepie Gadżetowo, zam. GAD00123',
                    'waluta_przelewu' => 'PLN',
                    'status' => 'zrealizowany',
                    'data_realizacji' => Carbon::now()->subDays(2)->setTime(11, 45, 0),
                    'informacja_zwrotna' => 'Płatność zrealizowana pomyślnie.',
                ]
            );
        }

        // 6. Przelew od Samuela (PLN) na jego własne konto Samuela (EUR)
        // Przelew jest zlecany w PLN z konta PLN na numer konta EUR.
        // System docelowy musiałby obsłużyć przewalutowanie.
        // W tabeli 'przelewy' zapisujemy kwotę i walutę, w jakiej przelew został zlecony (czyli PLN).
        if ($kontoSamuelaPLN && $kontoSamuelaEUR && $kontoSamuelaPLN->uzytkownik && $kontoSamuelaEUR->uzytkownik) {
            Przelew::firstOrCreate(
                [
                    'id_konta_nadawcy' => $kontoSamuelaPLN->id,
                    'nr_konta_odbiorcy' => $kontoSamuelaEUR->nr_konta, // Numer konta EUR
                    'kwota' => 100.00, // Kwota w PLN
                    'data_zlecenia' => Carbon::now()->subDays(1)->setTime(17, 0, 0),
                    'tytul' => 'Przesunięcie środków na konto EUR',
                ],
                [
                    'nazwa_odbiorcy' => $kontoSamuelaPLN->uzytkownik->imie . ' ' . $kontoSamuelaPLN->uzytkownik->nazwisko, // To jego własne konto
                    'adres_odbiorcy_linia1' => 'ul. Prywatna 1', // Adres może być taki sam
                    'adres_odbiorcy_linia2' => '80-298 Gdańsk',
                    'waluta_przelewu' => 'PLN', // Przelew zlecony w PLN
                    'status' => 'zrealizowany', // Załóżmy, że system automatycznie to przetwarza
                    'data_realizacji' => Carbon::now()->subDays(1)->setTime(17, 5, 0),
                    'informacja_zwrotna' => 'Przelew własny na konto walutowe przetworzony. Kwota w PLN.',
                ]
            );
        }

        $this->command->info('PrzelewTableSeeder zakończył działanie.');
    }
}
