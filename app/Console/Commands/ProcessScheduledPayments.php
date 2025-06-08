<?php

namespace App\Console\Commands;

use App\Mail\PrzelewOtrzymanyOdbiorcaMail;
use App\Mail\PrzelewWykonanyNadawcaMail;
use App\Models\Konto;
use App\Models\Przelew;
use App\Models\ZlecenieStale;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessScheduledPayments extends Command
{
    protected $signature = 'payments:process-scheduled';
    protected $description = 'Przetwarza aktywne zlecenia stałe, których termin wykonania nadszedł.';

    public function handle(): int
    {
        $this->info('Rozpoczęto przetwarzanie zleceń stałych...');
        Log::info('Scheduler: Rozpoczęto przetwarzanie zleceń stałych.');

        $dzisiaj = Carbon::today();
        $zleceniaDoWykonania = ZlecenieStale::where('aktywne', true)
            ->whereNotNull('data_nastepnego_wykonania')
            ->whereDate('data_nastepnego_wykonania', '<=', $dzisiaj)
            ->with('kontoZrodlowe')
            ->get();

        if ($zleceniaDoWykonania->isEmpty()) {
            $this->info('Brak zleceń stałych do wykonania na dzień dzisiejszy.');
            Log::info('Scheduler: Brak zleceń stałych do wykonania.');
            return Command::SUCCESS;
        }

        foreach ($zleceniaDoWykonania as $zlecenie) {
            $this->info("Przetwarzanie zlecenia ID: {$zlecenie->id} dla konta {$zlecenie->kontoZrodlowe->nr_konta}");
            Log::info("Scheduler: Przetwarzanie zlecenia ID: {$zlecenie->id}");

            DB::beginTransaction();
            try {
                $kontoNadawcy = $zlecenie->kontoZrodlowe;

                // 1. Sprawdzenie środków
                if ($kontoNadawcy->saldo < $zlecenie->kwota) {
                    $this->warn("Brak wystarczających środków na koncie {$kontoNadawcy->nr_konta} dla zlecenia ID: {$zlecenie->id}. Kwota: {$zlecenie->kwota}, Saldo: {$kontoNadawcy->saldo}");
                    Log::warning("Scheduler: Brak środków dla zlecenia ID: {$zlecenie->id}. Konto: {$kontoNadawcy->nr_konta}");
                    // Można dodać logikę powiadomienia użytkownika lub dezaktywacji zlecenia po X nieudanych próbach
                    // Na razie tylko logujemy i przechodzimy dalej, nie aktualizując daty następnego wykonania
                    DB::rollBack(); // Jeśli nie wykonujemy, to rollback
                    continue;
                }

                // 2. Zmniejszenie salda nadawcy
                $kontoNadawcy->saldo -= $zlecenie->kwota;
                $kontoNadawcy->save();

                // 3. Zwiększenie salda odbiorcy (jeśli konto wewnętrzne)
                // Zakładając, że nr_konta_docelowego może być kontem w naszym banku
                $kontoOdbiorcy = Konto::where('nr_konta', $zlecenie->nr_konta_docelowego)->first();
                if ($kontoOdbiorcy) {
                    // Sprawdzenie waluty - uproszczone, zakładamy, że zlecenie jest w walucie konta nadawcy
                    // a przelew między kontami w tej samej walucie, lub jest jakaś konwersja
                    if ($kontoOdbiorcy->waluta !== $kontoNadawcy->waluta) {
                        Log::warning("Scheduler: Próba przelewu między kontami o różnych walutach dla zlecenia ID: {$zlecenie->id}. Nadawca: {$kontoNadawcy->waluta}, Odbiorca: {$kontoOdbiorcy->waluta}. Przelew nie został wykonany.");
                        // W tym przypadku nie powinniśmy kontynuować, rollback
                        DB::rollBack();
                        continue; // Przejdź do następnego zlecenia
                    }
                    $kontoOdbiorcy->saldo += $zlecenie->kwota;
                    $kontoOdbiorcy->save();
                } else {
                    // Konto odbiorcy jest zewnętrzne - tylko logujemy lub obsługa przez system zewnętrzny
                    Log::info("Scheduler: Zlecenie ID: {$zlecenie->id} - przelew na konto zewnętrzne: {$zlecenie->nr_konta_docelowego}");
                }

                // 4. Zapis do historii przelewów
                $nowyPrzelew = Przelew::create([
                    'id_konta_nadawcy' => $kontoNadawcy->id,
                    'nr_konta_odbiorcy' => $zlecenie->nr_konta_docelowego,
                    // 'id_konta_odbiorcy_wewnetrznego_lub_uzytkownika' => $kontoOdbiorcy ? $kontoOdbiorcy->id : null, // Jeśli masz takie pole
                    'nazwa_odbiorcy' => $zlecenie->nazwa_odbiorcy,
                    'adres_odbiorcy_linia1' => null, // Zlecenia stałe mogą nie mieć pełnego adresu
                    'adres_odbiorcy_linia2' => null,
                    'tytul' => $zlecenie->tytul_przelewu,
                    'kwota' => $zlecenie->kwota,
                    'waluta_przelewu' => $kontoNadawcy->waluta, // Zakładamy walutę konta nadawcy
                    'status' => 'zrealizowany',
                    'data_zlecenia' => Carbon::now(),
                    'data_realizacji' => Carbon::now(),
                    'id_zlecenia_stalego' => $zlecenie->id,
                    'typ_przelewu' => $kontoOdbiorcy ? 'wewnętrzny' : 'zewnętrzny', // Dodatkowe pole, jeśli masz
                    'informacja_zwrotna' => 'Przelew cykliczny wykonany automatycznie.'
                ]);
                Log::info("Scheduler: Zlecenie ID: {$zlecenie->id} - przelew zapisany do historii.");
                $nowyPrzelew->load(['kontoNadawcy.uzytkownik']); // Załaduj relacje dla maila

                // 5. Aktualizacja zlecenia stałego
                $nastepnaData = $zlecenie->obliczNastepneWykonanie(Carbon::now()->format('Y-m-d')); // Przekazujemy dzisiejszą datę jako bazę do obliczeń

                if ($nastepnaData) {
                    $zlecenie->data_nastepnego_wykonania = $nastepnaData;
                } else {
                    // Brak następnej daty oznacza, że zlecenie się zakończyło (np. osiągnięto data_zakonczenia)
                    $zlecenie->aktywne = false;
                    $zlecenie->data_nastepnego_wykonania = null; // Wyczyść datę następnego wykonania
                    Log::info("Scheduler: Zlecenie ID: {$zlecenie->id} zostało zdezaktywowane po ostatnim wykonaniu.");
                }
                $zlecenie->save();

                DB::commit();
                // WYSYŁANIE POWIADOMIEŃ EMAIL PO UDANEJ TRANSAKCJI BANKOWEJ
                try {
                    // 1. Powiadomienie dla nadawcy (właściciela zlecenia)
                    $nadawcaZlecenia = $zlecenie->uzytkownik;
                    if ($nadawcaZlecenia) {
                        Mail::to($nadawcaZlecenia->email)->send(new PrzelewWykonanyNadawcaMail($nowyPrzelew, $nadawcaZlecenia));
                    }

                    // 2. Powiadomienie dla odbiorcy (jeśli jest użytkownikiem naszego banku)
                    $kontoOdbiorcyWewnetrzny = Konto::where('nr_konta', $nowyPrzelew->nr_konta_odbiorcy)->with('uzytkownik')->first();
                    if ($kontoOdbiorcyWewnetrzny && $kontoOdbiorcyWewnetrzny->uzytkownik) {
                        // Upewnij się, że saldo konta odbiorcy jest aktualne po wykonaniu przelewu
                        $kontoOdbiorcyWewnetrzny->refresh(); // Odśwież model z bazy
                        Mail::to($kontoOdbiorcyWewnetrzny->uzytkownik->email)->send(new PrzelewOtrzymanyOdbiorcaMail($nowyPrzelew, $kontoOdbiorcyWewnetrzny->uzytkownik, $kontoOdbiorcyWewnetrzny));
                    }
                    Log::info("Scheduler: Powiadomienia email dla przelewu ID: {$nowyPrzelew->id} (ze zlecenia ID: {$zlecenie->id}) zostały wysłane.");

                } catch (\Exception $e) {
                    Log::error("Scheduler: Błąd podczas wysyłania powiadomień email dla przelewu ID: {$nowyPrzelew->id} (ze zlecenia ID: {$zlecenie->id}). Błąd: " . $e->getMessage());
                }
                $this->info("Zlecenie ID: {$zlecenie->id} przetworzone pomyślnie.");

            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Błąd podczas przetwarzania zlecenia ID: {$zlecenie->id}. Błąd: " . $e->getMessage());
                Log::error("Scheduler: Błąd przetwarzania zlecenia ID: {$zlecenie->id}. " . $e->getMessage(), ['exception' => $e]);
                // Można tu dodać logikę ponawiania lub powiadamiania admina
            }
        }

        $this->info('Zakończono przetwarzanie zleceń stałych.');
        Log::info('Scheduler: Zakończono przetwarzanie zleceń stałych.');
        return Command::SUCCESS;
    }
}
