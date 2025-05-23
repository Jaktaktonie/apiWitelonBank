<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PortfelResource;
use App\Models\Konto;
use App\Models\Portfel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http; // << Importuj klienta HTTP
use Illuminate\Support\Facades\Log;   // << Importuj Log do logowania błędów
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="Inwestycje",
 *     description="Operacje związane z inwestowaniem w kryptowaluty"
 * )
 */
class InwestycjaController extends Controller
{
    // Usunięto symulowane ceny, będą pobierane z API
    // private $cenyKryptowalut = [
    //     'BTC' => 150000.00,
    //     'ETH' => 10000.00,
    // ];

    // Dostępne symbole kryptowalut i ich ID w CoinGecko
    private $dostepneKrypto = [
        'BTC' => 'bitcoin',
        'ETH' => 'ethereum',
    ];

    // Adres URL API CoinGecko do pobierania cen
    private const COINGECKO_API_URL = 'https://api.coingecko.com/api/v3/simple/price';

    /**
     * Prywatna metoda do pobierania aktualnych cen kryptowalut z API.
     * @param array $symboleKrypto Lista symboli do pobrania (np. ['BTC', 'ETH'])
     * @return array|null Zwraca tablicę z cenami ['SYMBOL' => cena] lub null w przypadku błędu.
     */
    private function pobierzAktualneCenyZApi(array $symboleKrypto): ?array
    {
        if (empty($symboleKrypto)) {
            return [];
        }

        $idsCoinGecko = [];
        foreach ($symboleKrypto as $symbol) {
            if (isset($this->dostepneKrypto[$symbol])) {
                $idsCoinGecko[] = $this->dostepneKrypto[$symbol];
            }
        }

        if (empty($idsCoinGecko)) {
            Log::warning('Brak poprawnych symboli do pobrania cen z CoinGecko.');
            return null;
        }

        try {
            $response = Http::timeout(5)->get(self::COINGECKO_API_URL, [
                'ids' => implode(',', $idsCoinGecko),
                'vs_currencies' => 'pln',
            ]);

            if ($response->successful()) {
                $daneApi = $response->json();
                $ceny = [];
                // Mapowanie z powrotem na nasze symbole (BTC, ETH)
                foreach ($this->dostepneKrypto as $symbolNaszejAplikacji => $idCoinGecko) {
                    if (isset($daneApi[$idCoinGecko]['pln'])) {
                        $ceny[$symbolNaszejAplikacji] = (float) $daneApi[$idCoinGecko]['pln'];
                    }
                }
                return $ceny;
            } else {
                Log::error('Błąd podczas pobierania cen z CoinGecko: ' . $response->status(), [
                    'response_body' => $response->body()
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Wyjątek podczas pobierania cen z CoinGecko: ' . $e->getMessage());
            return null;
        }
    }


    /**
     * @OA\Get(
     *     path="/api/kryptowaluty/ceny",
     *     summary="Pobiera listę dostępnych kryptowalut i ich aktualne ceny z API",
     *     tags={"Inwestycje"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista kryptowalut z cenami",
     *         @OA\JsonContent(
     *             type="object",
     *             example={"BTC": 250000.50, "ETH": 12000.75}
     *         )
     *     ),
     *     @OA\Response(
     *         response=503,
     *         description="Nie udało się pobrać cen z zewnętrznego API",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Nie udało się pobrać aktualnych cen kryptowalut."))
     *     )
     * )
     */
    public function pobierzCeny()
    {
        $ceny = $this->pobierzAktualneCenyZApi(array_keys($this->dostepneKrypto));

        if ($ceny === null) {
            return response()->json(['message' => 'Nie udało się pobrać aktualnych cen kryptowalut. Spróbuj ponownie później.'], 503);
        }
        return response()->json($ceny);
    }

    /**
     * @OA\Post(
     *     path="/api/inwestycje/kup",
     *     summary="Kupuje kryptowalutę za środki z konta PLN",
     *     tags={"Inwestycje"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Dane do zakupu kryptowaluty",
     *         @OA\JsonContent(
     *             required={"id_konta_pln", "symbol_krypto", "kwota_pln"},
     *             @OA\Property(property="id_konta_pln", type="integer", example=1, description="ID konta PLN, z którego pobierane są środki"),
     *             @OA\Property(property="symbol_krypto", type="string", example="BTC", enum={"BTC", "ETH"}, description="Symbol kryptowaluty do kupienia"),
     *             @OA\Property(property="kwota_pln", type="number", format="float", example=100.00, description="Kwota w PLN do zainwestowania")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Kryptowaluta zakupiona pomyślnie",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Zakupiono X BTC za Y PLN."),
     *             @OA\Property(property="portfel", ref="#/components/schemas/PortfelResource")
     *         )
     *     ),
     *     @OA\Response(response=422, description="Błąd walidacji lub niewystarczające środki", @OA\JsonContent(ref="#/components/schemas/ErrorValidation")),
     *     @OA\Response(response=404, description="Konto PLN lub portfel nie znalezione"),
     *     @OA\Response(response=503, description="Nie udało się pobrać aktualnej ceny kryptowaluty z API")
     * )
     */
    public function kup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_konta_pln' => 'required|integer|exists:konta,id',
            'symbol_krypto' => ['required', 'string', Rule::in(array_keys($this->dostepneKrypto))],
            'kwota_pln' => 'required|numeric|min:0.01', // Minimalna kwota inwestycji
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $uzytkownik = Auth::user();
        $kwotaPLN = floatval($request->input('kwota_pln'));
        $symbolKrypto = strtoupper($request->input('symbol_krypto'));
        $idKontaPLN = $request->input('id_konta_pln');

        // 1. Sprawdź konto PLN użytkownika
        $kontoPLN = Konto::where('id', $idKontaPLN)
            ->where('id_uzytkownika', $uzytkownik->id)
            ->where('waluta', 'PLN')
            ->first();

        if (!$kontoPLN) {
            return response()->json(['message' => 'Nie znaleziono Twojego konta PLN lub podane ID konta jest nieprawidłowe.'], 404);
        }

        if ($kontoPLN->zablokowane) {
            return response()->json(['message' => 'Twoje konto PLN jest zablokowane.'], 403);
        }

        if ($kontoPLN->saldo < $kwotaPLN) {
            return response()->json(['message' => 'Niewystarczające środki na koncie PLN.', 'saldo_dostepne' => $kontoPLN->saldo], 422);
        }

        // 2. Pobierz aktualną cenę kryptowaluty z API
        $aktualneCeny = $this->pobierzAktualneCenyZApi([$symbolKrypto]);
        if ($aktualneCeny === null || !isset($aktualneCeny[$symbolKrypto])) {
            return response()->json(['message' => 'Nie można ustalić aktualnej ceny dla wybranej kryptowaluty. Spróbuj ponownie później.'], 503);
        }
        $cenaJednostkowaKrypto = $aktualneCeny[$symbolKrypto];

        if ($cenaJednostkowaKrypto <= 0) { // Dodatkowe zabezpieczenie
            Log::error("Pobrana cena dla {$symbolKrypto} jest nieprawidłowa: {$cenaJednostkowaKrypto}");
            return response()->json(['message' => 'Pobrana cena kryptowaluty jest nieprawidłowa. Spróbuj ponownie później.'], 503);
        }


        // 3. Oblicz ilość kryptowaluty
        $iloscKryptoDoKupienia = $kwotaPLN / $cenaJednostkowaKrypto;

        // 4. Znajdź lub utwórz portfel użytkownika
        $portfel = Portfel::firstOrCreate(
            ['id_uzytkownika' => $uzytkownik->id],
            ['saldo_bitcoin' => 0, 'saldo_ethereum' => 0]
        );

        // 5. Wykonaj transakcję w bazie danych
        try {
            DB::transaction(function () use ($kontoPLN, $portfel, $symbolKrypto, $iloscKryptoDoKupienia, $kwotaPLN) {
                $kontoPLN->saldo -= $kwotaPLN;
                $kontoPLN->save();

                $mapowanieSymbolNaKolumne = [
                    'BTC' => 'saldo_bitcoin',
                    'ETH' => 'saldo_ethereum',
                ];

                if (!isset($mapowanieSymbolNaKolumne[$symbolKrypto])) {
                    throw new \Exception("Nieobsługiwany symbol kryptowaluty: $symbolKrypto");
                }

                $kolumnaSaldoKrypto = $mapowanieSymbolNaKolumne[$symbolKrypto];
                $biezaceSaldoKrypto = (float) $portfel->{$kolumnaSaldoKrypto};
                $portfel->{$kolumnaSaldoKrypto} = $biezaceSaldoKrypto + $iloscKryptoDoKupienia;
                $portfel->save();
            });
        } catch (\Exception $e) {
            Log::error("Błąd podczas zakupu krypto: " . $e->getMessage(), ['exception' => $e]);
            return response()->json(['message' => 'Wystąpił błąd podczas przetwarzania transakcji. Spróbuj ponownie.', 'error_details' => $e->getMessage()], 500);
        }

        $portfel->refresh();

        return response()->json([
            'message' => sprintf('Zakupiono %.8f %s za %.2f PLN (cena jednostkowa: %.2f PLN).', $iloscKryptoDoKupienia, $symbolKrypto, $kwotaPLN, $cenaJednostkowaKrypto),
            'portfel' => new PortfelResource($portfel) // Używam PortfelResource, jeśli go masz
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/portfel",
     *     summary="Pobiera aktualny portfel kryptowalut zalogowanego użytkownika",
     *     tags={"Inwestycje"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Portfel użytkownika",
     *         @OA\JsonContent(ref="#/components/schemas/PortfelResource")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Portfel nie znaleziony (użytkownik jeszcze nie inwestował)",
     *         @OA\JsonContent(@OA\Property(property="message", type="string", example="Nie znaleziono portfela dla tego użytkownika."))
     *     )
     * )
     */
    public function pobierzMojPortfel()
    {
        $uzytkownik = Auth::user();
        $portfel = Portfel::firstOrCreate(
            ['id_uzytkownika' => $uzytkownik->id],
            ['saldo_bitcoin' => 0.00000000, 'saldo_ethereum' => 0.00000000]
        );

        // W tym miejscu nie musimy już sprawdzać czy portfel istnieje, bo firstOrCreate go utworzy
        // if (!$portfel) {
        //     return response()->json(['message' => 'Nie znaleziono portfela dla tego użytkownika.'], 404);
        // }

        return new PortfelResource($portfel);
    }
}
