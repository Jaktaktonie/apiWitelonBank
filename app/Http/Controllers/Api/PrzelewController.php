<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB; // Dla transakcji
use App\Models\Konto;
use App\Models\Przelew;
use App\Http\Resources\PrzelewResource; // Stworzymy go później
use OpenApi\Attributes as OA;

class PrzelewController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/przelewy",
     *     summary="Tworzy nowy przelew",
     *     tags={"Przelewy"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Dane przelewu",
     *         @OA\JsonContent(
     *             required={"id_konta_nadawcy", "nr_konta_odbiorcy", "nazwa_odbiorcy", "tytul", "kwota", "waluta_przelewu"},
     *             @OA\Property(property="id_konta_nadawcy", type="integer", example=1, description="ID konta, z którego wysyłany jest przelew"),
     *             @OA\Property(property="nr_konta_odbiorcy", type="string", example="PL98765432109876543210987654", description="Numer konta odbiorcy"),
     *             @OA\Property(property="nazwa_odbiorcy", type="string", example="Anna Nowak", description="Nazwa odbiorcy"),
     *             @OA\Property(property="adres_odbiorcy_linia1", type="string", example="ul. Słoneczna 10", nullable=true),
     *             @OA\Property(property="adres_odbiorcy_linia2", type="string", example="00-123 Warszawa", nullable=true),
     *             @OA\Property(property="tytul", type="string", example="Za zakupy"),
     *             @OA\Property(property="kwota", type="number", format="float", example=150.99),
     *             @OA\Property(property="waluta_przelewu", type="string", example="PLN", description="Waluta przelewu (np. PLN, EUR)")
     *         )
     *     ),
     *     @OA\Response(response=201, description="Przelew został pomyślnie utworzony (zlecony)", @OA\JsonContent(ref="#/components/schemas/PrzelewResource")),
     *     @OA\Response(response=400, description="Błąd danych (np. niewystarczające środki, konto zablokowane)"),
     *     @OA\Response(response=401, description="Nieautoryzowany"),
     *     @OA\Response(response=403, description="Brak uprawnień do konta nadawcy"),
     *     @OA\Response(response=404, description="Konto nadawcy nie znalezione"),
     *     @OA\Response(response=422, description="Błąd walidacji")
     * )
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_konta_nadawcy' => 'required|integer|exists:konta,id',
            'nr_konta_odbiorcy' => 'required|string|max:34', // IBAN może mieć do 34 znaków
            'nazwa_odbiorcy' => 'required|string|max:255',
            'adres_odbiorcy_linia1' => 'nullable|string|max:255',
            'adres_odbiorcy_linia2' => 'nullable|string|max:255',
            'tytul' => 'required|string|max:255',
            'kwota' => 'required|numeric|gt:0', // gt:0 - kwota musi być większa od zera
            'waluta_przelewu' => 'required|string|size:3', // np. PLN, EUR
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $uzytkownik = Auth::user();
        $kontoNadawcy = Konto::find($request->id_konta_nadawcy);

        if (!$kontoNadawcy) {
            return response()->json(['message' => 'Konto nadawcy nie zostało znalezione.'], 404);
        }

        if ($kontoNadawcy->id_uzytkownika !== $uzytkownik->id) {
            return response()->json(['message' => 'Brak uprawnień do wykonania przelewu z tego konta.'], 403);
        }

        if ($kontoNadawcy->zablokowane) {
            return response()->json(['message' => 'Konto nadawcy jest zablokowane.'], 400);
        }

        // --- Walidacja Salda i Waluty ---
        $kwotaPrzelewu = (float) $request->kwota;

        if ($kontoNadawcy->waluta !== $request->waluta_przelewu) {
            // TODO: Obsługa przewalutowania lub błąd, jeśli waluty się nie zgadzają
            // Na razie prosty błąd
            return response()->json(['message' => 'Waluta przelewu nie zgadza się z walutą konta nadawcy. Przewalutowanie nie jest jeszcze obsługiwane.'], 400);
        }

        if ($kontoNadawcy->saldo < $kwotaPrzelewu) {
            return response()->json(['message' => 'Niewystarczające środki na koncie.'], 400);
        }

        // --- Sprawdzenie limitu przelewu (jeśli istnieje) ---
        if ($kontoNadawcy->limit_przelewu && $kwotaPrzelewu > $kontoNadawcy->limit_przelewu) {
            return response()->json(['message' => "Kwota przelewu (" . $kwotaPrzelewu . ") przekracza dzienny limit przelewu (" . $kontoNadawcy->limit_przelewu . ") dla tego konta."], 400);
        }


        // --- Transakcja bazodanowa ---
        try {
            DB::beginTransaction();

            // 1. Zmniejsz saldo na koncie nadawcy
            $kontoNadawcy->saldo -= $kwotaPrzelewu;
            $kontoNadawcy->save();

            // 2. Zapisz przelew
            $przelew = Przelew::create([
                'id_konta_nadawcy' => $kontoNadawcy->id,
                'nr_konta_odbiorcy' => $request->nr_konta_odbiorcy,
                'nazwa_odbiorcy' => $request->nazwa_odbiorcy,
                'adres_odbiorcy_linia1' => $request->adres_odbiorcy_linia1,
                'adres_odbiorcy_linia2' => $request->adres_odbiorcy_linia2,
                'tytul' => $request->tytul,
                'kwota' => $kwotaPrzelewu,
                'waluta_przelewu' => $request->waluta_przelewu,
                'status' => 'zrealizowany', // Zakładamy natychmiastową realizację dla uproszczenia
                // W realnym systemie mógłby być 'oczekujacy', a potem proces wsadowy
                'data_zlecenia' => now(),
                'data_realizacji' => now(),
            ]);

            // 3. (Opcjonalnie) Zwiększ saldo na koncie odbiorcy, jeśli jest to konto w naszym banku
            $kontoOdbiorcy = Konto::where('nr_konta', $request->nr_konta_odbiorcy)->first();
            if ($kontoOdbiorcy) {
                if ($kontoOdbiorcy->waluta !== $request->waluta_przelewu) {
                    // TODO: Obsługa przewalutowania dla odbiorcy
                    // Na razie pomijamy księgowanie, jeśli waluty się nie zgadzają
                    Log::warning("Próba zaksięgowania przelewu przychodzącego o innej walucie. Przelew ID: {$przelew->id}, Konto odbiorcy ID: {$kontoOdbiorcy->id}");
                } else {
                    $kontoOdbiorcy->saldo += $kwotaPrzelewu;
                    $kontoOdbiorcy->save();
                }
            }

            DB::commit();

            return new PrzelewResource($przelew); // Zwracamy utworzony przelew

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Błąd podczas tworzenia przelewu: " . $e->getMessage(), ['request' => $request->all()]);
            return response()->json(['message' => 'Wystąpił błąd podczas przetwarzania przelewu. Spróbuj ponownie później.'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/konta/{idKonta}/przelewy",
     *     summary="Pobiera historię przelewów dla danego konta",
     *     tags={"Przelewy"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="idKonta", in="path", required=true, description="ID konta", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="typ", in="query", required=false, description="Filtruj wg typu: 'wychodzace', 'przychodzace'", @OA\Schema(type="string", enum={"wychodzace", "przychodzace"})),
     *     @OA\Parameter(name="strona", in="query", required=false, description="Numer strony dla paginacji", @OA\Schema(type="integer", default=1)),
     *     @OA\Parameter(name="na_strone", in="query", required=false, description="Liczba wyników na stronę", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Lista przelewów", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/PrzelewResource"))),
     *     @OA\Response(response=401, description="Nieautoryzowany"),
     *     @OA\Response(response=403, description="Brak uprawnień do tego konta"),
     *     @OA\Response(response=404, description="Konto nie znalezione")
     * )
     */
    public function index(Request $request, $idKonta)
    {
        $uzytkownik = Auth::user();
        $konto = Konto::find($idKonta);

        if (!$konto) {
            return response()->json(['message' => 'Konto nie zostało znalezione.'], 404);
        }

        if ($konto->id_uzytkownika !== $uzytkownik->id) {
            return response()->json(['message' => 'Brak uprawnień do przeglądania historii przelewów tego konta.'], 403);
        }

        $typ = $request->query('typ');
        $query = Przelew::query();

        if ($typ === 'wychodzace') {
            $query->where('id_konta_nadawcy', $konto->id);
        } elseif ($typ === 'przychodzace') {
            $query->where('nr_konta_odbiorcy', $konto->nr_konta);
        } else {
            // Domyślnie pokaż oba (wychodzące z tego konta lub przychodzące na numer tego konta)
            $query->where(function ($q) use ($konto) {
                $q->where('id_konta_nadawcy', $konto->id)
                    ->orWhere('nr_konta_odbiorcy', $konto->nr_konta);
            });
        }

        $przelewy = $query->orderBy('data_zlecenia', 'desc') // Najnowsze najpierw
        ->paginate($request->query('na_strone', 15));

        return PrzelewResource::collection($przelewy);
    }

    /**
     * @OA\Get(
     *     path="/api/przelewy/{idPrzelewu}",
     *     summary="Pobiera szczegóły konkretnego przelewu",
     *     tags={"Przelewy"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="idPrzelewu", in="path", required=true, description="ID przelewu", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Szczegóły przelewu", @OA\JsonContent(ref="#/components/schemas/PrzelewResource")),
     *     @OA\Response(response=401, description="Nieautoryzowany"),
     *     @OA\Response(response=403, description="Brak uprawnień do tego przelewu"),
     *     @OA\Response(response=404, description="Przelew nie znaleziony")
     * )
     */
    // Gdzieś w głównym pliku dokumentacji OpenAPI lub w adnotacjach kontrolera, gdzie definiujesz komponenty:
    /**
     * @OA\Schema(
     *     schema="PrzelewResource",
     *     title="Przelew Resource",
     *     description="Szczegółowe dane przelewu",
     *     @OA\Property(property="id", type="integer", example=1),
     *     @OA\Property(property="typ_transakcji", type="string", example="wychodzący", description="Kierunek transakcji z perspektywy przeglądanego konta (wychodzący/przychodzący)"),
     *     @OA\Property(property="id_konta_nadawcy", type="integer", example=1),
     *     @OA\Property(property="nr_konta_nadawcy", type="string", example="PL12345..."),
     *     @OA\Property(property="nr_konta_odbiorcy", type="string", example="PL98765..."),
     *     @OA\Property(property="nazwa_odbiorcy", type="string", example="Anna Nowak"),
     *     @OA\Property(property="adres_odbiorcy_linia1", type="string", nullable=true),
     *     @OA\Property(property="adres_odbiorcy_linia2", type="string", nullable=true),
     *     @OA\Property(property="tytul", type="string", example="Za zakupy"),
     *     @OA\Property(property="kwota", type="number", format="float", example=150.99),
     *     @OA\Property(property="waluta", type="string", example="PLN"),
     *     @OA\Property(property="status", type="string", example="zrealizowany"),
     *     @OA\Property(property="data_zlecenia", type="string", format="date-time", example="2023-05-15T10:00:00Z"),
     *     @OA\Property(property="data_realizacji", type="string", format="date-time", example="2023-05-15T10:01:00Z", nullable=true),
     *     @OA\Property(property="informacja_zwrotna", type="string", nullable=true),
     *     @OA\Property(property="utworzono", type="string", format="date-time", example="2023-05-15T09:59:00Z")
     * )
     */
    public function show($idPrzelewu)
    {
        $uzytkownik = Auth::user();
        $przelew = Przelew::find($idPrzelewu);

        if (!$przelew) {
            return response()->json(['message' => 'Przelew nie został znaleziony.'], 404);
        }

        // Sprawdź, czy użytkownik jest nadawcą LUB odbiorcą (jeśli konto odbiorcy jest w naszym systemie i należy do niego)
        $kontoNadawcy = $przelew->kontoNadawcy; // Zakładając relację w modelu Przelew
        $maDostep = false;

        if ($kontoNadawcy && $kontoNadawcy->id_uzytkownika === $uzytkownik->id) {
            $maDostep = true;
        } else {
            $kontoOdbiorcy = Konto::where('nr_konta', $przelew->nr_konta_odbiorcy)
                ->where('id_uzytkownika', $uzytkownik->id)
                ->first();
            if ($kontoOdbiorcy) {
                $maDostep = true;
            }
        }

        if (!$maDostep) {
            return response()->json(['message' => 'Brak uprawnień do przeglądania tego przelewu.'], 403);
        }

        return new PrzelewResource($przelew);
    }
}
