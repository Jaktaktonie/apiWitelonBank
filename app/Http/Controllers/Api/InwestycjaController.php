<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PortfelResource;
use App\Models\Konto;
use App\Models\Portfel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
    // Symulowane ceny kryptowalut (w PLN za jednostkę)
    private $cenyKryptowalut = [
        'BTC' => 150000.00,
        'ETH' => 10000.00,
    ];

    // Dostępne symbole kryptowalut
    private $dostepneKrypto = ['BTC', 'ETH'];

    /**
     * @OA\Get(
     *     path="/api/kryptowaluty/ceny",
     *     summary="Pobiera listę dostępnych kryptowalut i ich aktualne (symulowane) ceny",
     *     tags={"Inwestycje"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista kryptowalut z cenami",
     *         @OA\JsonContent(
     *             type="object",
     *             example={"BTC": 150000.00, "ETH": 10000.00}
     *         )
     *     )
     * )
     */
    public function pobierzCeny()
    {
        return response()->json($this->cenyKryptowalut);
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
     *     @OA\Response(response=404, description="Konto PLN lub portfel nie znalezione")
     * )
     */
    public function kup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_konta_pln' => 'required|integer|exists:konta,id',
            'symbol_krypto' => ['required', 'string', Rule::in($this->dostepneKrypto)],
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

        // 2. Pobierz cenę kryptowaluty
        $cenaJednostkowaKrypto = $this->cenyKryptowalut[$symbolKrypto] ?? null;
        if (!$cenaJednostkowaKrypto || $cenaJednostkowaKrypto <= 0) {
            return response()->json(['message' => 'Nie można ustalić ceny dla wybranej kryptowaluty.'], 422);
        }

        // 3. Oblicz ilość kryptowaluty
        $iloscKryptoDoKupienia = $kwotaPLN / $cenaJednostkowaKrypto;

        // 4. Znajdź lub utwórz portfel użytkownika
        $portfel = Portfel::firstOrCreate(
            ['id_uzytkownika' => $uzytkownik->id],
            // Domyślne wartości, jeśli portfel jest tworzony
            ['saldo_bitcoin' => 0, 'saldo_ethereum' => 0]
        );

        // 5. Wykonaj transakcję w bazie danych
        try {
            DB::transaction(function () use ($kontoPLN, $portfel, $symbolKrypto, $iloscKryptoDoKupienia, $kwotaPLN) {
                // Zmniejsz saldo PLN
                $kontoPLN->saldo -= $kwotaPLN;
                $kontoPLN->save();

                // Zwiększ saldo krypto w portfelu
                // Zwiększ saldo krypto w portfelu
                // POPRAWKA: Używamy mapowania symbolu na pełną nazwę kolumny
                $mapowanieSymbolNaKolumne = [
                    'BTC' => 'saldo_bitcoin',
                    'ETH' => 'saldo_ethereum',
                    // Dodaj inne mapowania, jeśli masz więcej kryptowalut
                ];

                if (!isset($mapowanieSymbolNaKolumne[$symbolKrypto])) {
                    throw new \Exception("Nieobsługiwany symbol kryptowaluty: $symbolKrypto");
                }

                $kolumnaSaldoKrypto = $mapowanieSymbolNaKolumne[$symbolKrypto];

                // Bezpośredni dostęp i aktualizacja atrybutu Eloquent
                // Rzutowanie na float jest ważne dla poprawnej operacji arytmetycznej
                $biezaceSaldoKrypto = (float) $portfel->{$kolumnaSaldoKrypto};
                $portfel->{$kolumnaSaldoKrypto} = $biezaceSaldoKrypto + $iloscKryptoDoKupienia;
                $portfel->save();
            });
        } catch (\Exception $e) {
            // Log::error("Błąd podczas zakupu krypto: " . $e->getMessage());
            return response()->json(['message' => 'Wystąpił błąd podczas przetwarzania transakcji. Spróbuj ponownie.', 'error_details' => $e->getMessage()], 500);
        }

        // Załaduj zaktualizowany portfel do odpowiedzi
        $portfel->refresh();

        // Utwórz PortfelResource jeśli go masz, inaczej zwróć surowy model
        // return new PortfelResource($portfel); // Jeśli masz PortfelResource
        return response()->json([
            'message' => sprintf('Zakupiono %.8f %s za %.2f PLN.', $iloscKryptoDoKupienia, $symbolKrypto, $kwotaPLN),
            'portfel' => $portfel // Lub new PortfelResource($portfel)
        ]);
    }

    /**
     * @OA\Tag(
     *     name="Inwestycje",
     *     description="Operacje związane z inwestowaniem w kryptowaluty oraz zarządzaniem portfelem" // Zaktualizowany opis tagu
     * )
     */

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

        // Znajdź portfel użytkownika.
        // Można użyć firstOrCreate, aby automatycznie stworzyć pusty portfel, jeśli go nie ma,
        // lub first() jeśli zakładamy, że portfel jest tworzony tylko przy pierwszej inwestycji.
        // Dla spójności z metodą kup(), użyjemy firstOrCreate.
        $portfel = Portfel::firstOrCreate(
            ['id_uzytkownika' => $uzytkownik->id],
            // Domyślne wartości, jeśli portfel jest tworzony (chociaż w tym miejscu raczej powinien już istnieć)
            ['saldo_bitcoin' => 0.00000000, 'saldo_ethereum' => 0.00000000]
        );

        if (!$portfel) {
            // Ten warunek jest mniej prawdopodobny przy użyciu firstOrCreate, ale zostawiam dla bezpieczeństwa
            return response()->json(['message' => 'Nie znaleziono portfela dla tego użytkownika.'], 404);
        }

        return new PortfelResource($portfel);
    }
}

