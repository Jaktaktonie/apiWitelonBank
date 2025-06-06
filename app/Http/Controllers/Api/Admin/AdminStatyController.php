<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Karta;
use App\Models\Konto;
use App\Models\Portfel;
use App\Models\Przelew;
use App\Models\Uzytkownik;
use App\Models\ZlecenieStale;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Admin-Statystyki",
 *     description="Sprawdzanie statystyk systemowych (WBK-05)"
 * )
 */
class AdminStatyController
{
    /**
     * @OA\Get(
     *     path="/api/admin/statystyki",
     *     operationId="getSystemStatistics",
     *     tags={"Admin-Statystyki"},
     *     summary="Pobiera kompleksowe statystyki systemowe",
     *     description="Zwraca różnorodne statystyki dotyczące użytkowników, kont, transakcji, kryptowalut itp. Wymaga uprawnień administratora. Klucze w odpowiedzi są w języku polskim bez znaków diakrytycznych.",
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Pomyślnie pobrano statystyki",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="uzytkownicy",
     *                 type="object",
     *                 description="Statystyki użytkowników",
     *                 @OA\Property(property="calkowita_liczba", type="integer", example=150, description="Całkowita liczba użytkowników"),
     *                 @OA\Property(property="liczba_zweryfikowanych", type="integer", example=120, description="Liczba zweryfikowanych użytkowników"),
     *                 @OA\Property(property="liczba_administratorow", type="integer", example=5, description="Liczba administratorów"),
     *                 @OA\Property(property="nowi_uzytkownicy_ostatnie_30_dni", type="integer", example=15, description="Liczba nowych użytkowników w ciągu ostatnich 30 dni")
     *             ),
     *             @OA\Property(
     *                 property="konta_bankowe",
     *                 type="object",
     *                 description="Statystyki kont bankowych",
     *                 @OA\Property(property="calkowita_liczba", type="integer", example=200, description="Całkowita liczba kont bankowych"),
     *                 @OA\Property(
     *                     property="podsumowanie_sald_wg_waluty",
     *                     type="array",
     *                     description="Podsumowanie sald na kontach według waluty",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="waluta", type="string", example="PLN", description="Kod waluty"),
     *                         @OA\Property(property="calkowita_kwota", type="number", format="float", example=1250000.75, description="Całkowita suma sald w danej walucie"),
     *                         @OA\Property(property="liczba_kont", type="integer", example=180, description="Liczba kont w danej walucie"),
     *                         @OA\Property(property="srednia_kwota", type="number", format="float", example=6944.45, description="Średnie saldo na koncie dla danej waluty")
     *                     )
     *                 ),
     *                 @OA\Property(property="liczba_zablokowanych_kont", type="integer", example=10, description="Liczba zablokowanych kont"),
     *                 @OA\Property(property="liczba_kont_z_limitem_przelewu", type="integer", example=50, description="Liczba kont z ustawionym limitem przelewu (>0)")
     *             ),
     *             @OA\Property(
     *                 property="karty_platnicze",
     *                 type="object",
     *                 description="Statystyki kart płatniczych",
     *                 @OA\Property(property="calkowita_liczba", type="integer", example=180, description="Całkowita liczba kart"),
     *                 @OA\Property(property="liczba_zablokowanych_kart", type="integer", example=5, description="Liczba zablokowanych kart"),
     *                 @OA\Property(property="liczba_aktywne_zblizeniowe", type="integer", example=150, description="Liczba kart z aktywnymi płatnościami zbliżeniowymi"),
     *                 @OA\Property(property="liczba_aktywne_internetowe", type="integer", example=160, description="Liczba kart z aktywnymi płatnościami internetowymi"),
     *                 @OA\Property(
     *                     property="liczba_wg_typu",
     *                     type="array",
     *                     description="Liczba kart według typu",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="typ", type="string", nullable=true, example="Visa Debit", description="Typ karty (może być null)"),
     *                         @OA\Property(property="liczba", type="integer", example=100, description="Liczba kart danego typu")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="przelewy",
     *                 type="object",
     *                 description="Statystyki przelewów",
     *                 @OA\Property(property="calkowita_liczba", type="integer", example=5000, description="Całkowita liczba zarejestrowanych przelewów"),
     *                 @OA\Property(
     *                     property="podsumowanie_kwot_wg_waluty",
     *                     type="array",
     *                     description="Podsumowanie kwot przelewów według waluty",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="waluta", type="string", example="PLN", description="Waluta przelewu"),
     *                         @OA\Property(property="calkowita_kwota", type="number", format="float", example=850000.00, description="Całkowita suma kwot przelewów w danej walucie"),
     *                         @OA\Property(property="liczba_przelewow", type="integer", example=4500, description="Liczba przelewów w danej walucie"),
     *                         @OA\Property(property="srednia_kwota", type="number", format="float", example=188.89, description="Średnia kwota przelewu dla danej waluty")
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="liczba_wg_statusu",
     *                     type="array",
     *                     description="Liczba przelewów według statusu",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="status", type="string", example="zrealizowany", description="Status przelewu"),
     *                         @OA\Property(property="liczba", type="integer", example=4800, description="Liczba przelewów o danym statusie")
     *                     )
     *                 ),
     *                 @OA\Property(property="przelewy_ostatnie_24_godziny", type="integer", example=50, description="Liczba przelewów zleconych w ciągu ostatnich 24 godzin"),
     *                 @OA\Property(property="przelewy_ostatnie_7_dni", type="integer", example=300, description="Liczba przelewów zleconych w ciągu ostatnich 7 dni"),
     *                 @OA\Property(property="przelewy_ostatnie_30_dni", type="integer", example=1200, description="Liczba przelewów zleconych w ciągu ostatnich 30 dni")
     *             ),
     *             @OA\Property(
     *                 property="zlecenia_stale",
     *                 type="object",
     *                 description="Statystyki zleceń stałych",
     *                 @OA\Property(property="calkowita_liczba", type="integer", example=50, description="Całkowita liczba zleceń stałych"),
     *                 @OA\Property(property="liczba_aktywnych", type="integer", example=40, description="Liczba aktywnych zleceń stałych"),
     *                 @OA\Property(
     *                      property="suma_kwot_aktywnych_wg_waluty",
     *                      type="array",
     *                      description="Suma kwot aktywnych zleceń stałych według waluty konta źródłowego",
     *                      @OA\Items(
     *                          type="object",
     *                          @OA\Property(property="waluta", type="string", example="PLN", description="Waluta konta źródłowego zlecenia"),
     *                          @OA\Property(property="calkowita_kwota", type="number", format="float", example=12000.00, description="Suma kwot aktywnych zleceń w danej walucie")
     *                      )
     *                  ),
     *                 @OA\Property(
     *                     property="liczba_wg_czestotliwosci",
     *                     type="array",
     *                     description="Liczba zleceń stałych według częstotliwości",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="czestotliwosc", type="string", example="miesiecznie", description="Częstotliwość wykonywania"),
     *                         @OA\Property(property="liczba", type="integer", example=30, description="Liczba zleceń o danej częstotliwości")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="portfele_kryptowalut",
     *                 type="object",
     *                 description="Statystyki portfeli kryptowalutowych",
     *                 @OA\Property(property="calkowita_liczba_portfeli", type="integer", example=30, description="Całkowita liczba utworzonych portfeli (rekordów w tabeli)"),
     *                 @OA\Property(property="liczba_uzytkownikow_z_portfelami", type="integer", example=28, description="Liczba unikalnych użytkowników posiadających jakikolwiek portfel krypto"),
     *                 @OA\Property(property="calkowita_suma_bitcoin", type="number", format="float", example=5.12345678, description="Całkowita suma Bitcoinów na wszystkich portfelach"),
     *                 @OA\Property(property="calkowita_suma_ethereum", type="number", format="float", example=102.87654321, description="Całkowita suma Ethereum na wszystkich portfelach")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Nieautoryzowany dostęp (brak lub nieprawidłowy token)"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Brak uprawnień (użytkownik nie jest administratorem)"
     *     )
     * )
     */
    public function getSystemStatistics(): JsonResponse
    {
        // --- Użytkownicy ---
        $totalUsers = Uzytkownik::count();
        $verifiedUsers = Uzytkownik::where('weryfikacja', true)->count();
        $adminUsers = Uzytkownik::where('administrator', true)->count();
        $newUsersLast30Days = Uzytkownik::where('created_at', '>=', Carbon::now()->subDays(30))->count();

        // --- Konta Bankowe ---
        $totalAccounts = Konto::count();
        $lockedAccounts = Konto::where('zablokowane', true)->count();
        $accountsWithTransferLimit = Konto::where('limit_przelewu', '>', 0)->count();

        $accountBalancesByCurrency = Konto::query()
            ->select('waluta', DB::raw('SUM(saldo) as total_amount'), DB::raw('COUNT(id) as accounts_count'))
            ->groupBy('waluta')
            ->get()
            ->map(function ($item) {
                $totalAmount = (float)$item->total_amount;
                $accountsCount = (int)$item->accounts_count;
                return [
                    'waluta' => $item->waluta, // Klucz 'waluta'
                    'calkowita_kwota' => $totalAmount, // Klucz 'calkowita_kwota'
                    'liczba_kont' => $accountsCount, // Klucz 'liczba_kont'
                    'srednia_kwota' => $accountsCount > 0 ? round($totalAmount / $accountsCount, 2) : 0, // Klucz 'srednia_kwota'
                ];
            });

        // --- Karty Płatnicze ---
        $totalCards = Karta::count();
        $blockedCards = Karta::where('zablokowana', true)->count();
        $contactlessActiveCards = Karta::where('platnosci_zblizeniowe_aktywne', true)->count();
        $onlinePaymentsActiveCards = Karta::where('platnosci_internetowe_aktywne', true)->count();
        $cardsByType = Karta::query()
            ->select('typ_karty', DB::raw('COUNT(id) as count_val')) // Zmieniono alias, aby uniknąć konfliktu z 'count'
            ->groupBy('typ_karty')
            ->orderByRaw('ISNULL(typ_karty) ASC, typ_karty ASC')
            ->get()
            ->map(fn($item) => ['typ' => $item->typ_karty, 'liczba' => (int)$item->count_val]); // Klucz 'typ', 'liczba'


        // --- Przelewy ---
        $totalTransfers = Przelew::count();
        $transfersByStatus = Przelew::query()
            ->select('status', DB::raw('COUNT(id) as count_val')) // Zmieniono alias
            ->groupBy('status')
            ->orderByRaw('ISNULL(status) ASC, status ASC')
            ->get()
            ->map(fn($item) => ['status' => $item->status, 'liczba' => (int)$item->count_val]); // Klucz 'status', 'liczba'


        $transfersAmountByCurrency = Przelew::query()
            ->select('waluta_przelewu', DB::raw('SUM(kwota) as total_amount'), DB::raw('COUNT(id) as transfers_count_val')) // Zmieniono aliasy
            ->groupBy('waluta_przelewu')
            ->orderByRaw('ISNULL(waluta_przelewu) ASC, waluta_przelewu ASC')
            ->get()
            ->map(function ($item) {
                $totalAmount = (float)$item->total_amount;
                $transfersCount = (int)$item->transfers_count_val;
                return [
                    'waluta' => $item->waluta_przelewu, // Klucz 'waluta'
                    'calkowita_kwota' => $totalAmount, // Klucz 'calkowita_kwota'
                    'liczba_przelewow' => $transfersCount, // Klucz 'liczba_przelewow'
                    'srednia_kwota' => $transfersCount > 0 ? round($totalAmount / $transfersCount, 2) : 0, // Klucz 'srednia_kwota'
                ];
            });

        $now = Carbon::now();
        $transfersLast24Hours = Przelew::where('data_zlecenia', '>=', $now->copy()->subDay())->count();
        $transfersLast7Days = Przelew::where('data_zlecenia', '>=', $now->copy()->subDays(7))->count();
        $transfersLast30Days = Przelew::where('data_zlecenia', '>=', $now->copy()->subDays(30))->count();

        // --- Zlecenia Stałe ---
        $totalStandingOrders = ZlecenieStale::count();
        $activeStandingOrders = ZlecenieStale::where('aktywne', true)->count();
        $standingOrdersByFrequency = ZlecenieStale::query()
            ->select('czestotliwosc', DB::raw('COUNT(id) as count_val')) // Zmieniono alias
            ->groupBy('czestotliwosc')
            ->orderByRaw('ISNULL(czestotliwosc) ASC, czestotliwosc ASC')
            ->get()
            ->map(fn($item) => ['czestotliwosc' => $item->czestotliwosc, 'liczba' => (int)$item->count_val]); // Klucz 'czestotliwosc', 'liczba'


        $activeStandingOrdersAmounts = ZlecenieStale::query()
            ->where('zlecenia_stale.aktywne', true)
            ->join('konta', 'zlecenia_stale.id_konta_zrodlowego', '=', 'konta.id')
            ->select('konta.waluta', DB::raw('SUM(zlecenia_stale.kwota) as total_amount'))
            ->groupBy('konta.waluta')
            ->orderByRaw('ISNULL(konta.waluta) ASC, konta.waluta ASC')
            ->get()
            ->map(fn($item) => [
                'waluta' => $item->waluta, // Klucz 'waluta'
                'calkowita_kwota' => round((float)$item->total_amount, 2), // Klucz 'calkowita_kwota'
            ]);

        // --- Portfele Kryptowalutowe ---
        $totalCryptoWallets = Portfel::count();
        $usersWithCryptoWallets = Portfel::distinct()->count('id_uzytkownika');
        $totalBitcoin = Portfel::sum('saldo_bitcoin');
        $totalEthereum = Portfel::sum('saldo_ethereum');

        return response()->json([
            'uzytkownicy' => [ // Klucz główny
                'calkowita_liczba' => $totalUsers,
                'liczba_zweryfikowanych' => $verifiedUsers,
                'liczba_administratorow' => $adminUsers,
                'nowi_uzytkownicy_ostatnie_30_dni' => $newUsersLast30Days,
            ],
            'konta_bankowe' => [ // Klucz główny
                'calkowita_liczba' => $totalAccounts,
                'podsumowanie_sald_wg_waluty' => $accountBalancesByCurrency,
                'liczba_zablokowanych_kont' => $lockedAccounts,
                'liczba_kont_z_limitem_przelewu' => $accountsWithTransferLimit,
            ],
            'karty_platnicze' => [ // Klucz główny
                'calkowita_liczba' => $totalCards,
                'liczba_zablokowanych_kart' => $blockedCards,
                'liczba_aktywne_zblizeniowe' => $contactlessActiveCards,
                'liczba_aktywne_internetowe' => $onlinePaymentsActiveCards,
                'liczba_wg_typu' => $cardsByType,
            ],
            'przelewy' => [ // Klucz główny
                'calkowita_liczba' => $totalTransfers,
                'podsumowanie_kwot_wg_waluty' => $transfersAmountByCurrency,
                'liczba_wg_statusu' => $transfersByStatus,
                'przelewy_ostatnie_24_godziny' => $transfersLast24Hours,
                'przelewy_ostatnie_7_dni' => $transfersLast7Days,
                'przelewy_ostatnie_30_dni' => $transfersLast30Days,
            ],
            'zlecenia_stale' => [ // Klucz główny
                'calkowita_liczba' => $totalStandingOrders,
                'liczba_aktywnych' => $activeStandingOrders,
                'suma_kwot_aktywnych_wg_waluty' => $activeStandingOrdersAmounts,
                'liczba_wg_czestotliwosci' => $standingOrdersByFrequency,
            ],
            'portfele_kryptowalut' => [ // Klucz główny
                'calkowita_liczba_portfeli' => $totalCryptoWallets,
                'liczba_uzytkownikow_z_portfelami' => $usersWithCryptoWallets,
                'calkowita_suma_bitcoin' => round((float)$totalBitcoin, 8),
                'calkowita_suma_ethereum' => round((float)$totalEthereum, 8),
            ],
        ]);
    }
}
