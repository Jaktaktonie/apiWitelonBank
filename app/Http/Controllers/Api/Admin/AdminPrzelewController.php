<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PrzelewResource; // Użyj swojego istniejącego PrzelewResource
use App\Models\Konto;
use App\Models\Przelew;
use App\Models\Uzytkownik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 *     name="Admin-Przelewy",
 *     description="Monitorowanie transakcji (przelewów) przez administratora (WBK-03)"
 * )
 */
class AdminPrzelewController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/przelewy",
     *     summary="Pobiera listę wszystkich przelewów w systemie",
     *     tags={"Admin-Przelewy"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1), description="Numer strony"),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15), description="Ilość na stronę"),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"oczekujacy", "zrealizowany", "odrzucony"}), description="Filtruj po statusie"),
     *     @OA\Parameter(name="id_konta_nadawcy", in="query", @OA\Schema(type="integer"), description="Filtruj po ID konta nadawcy"),
     *     @OA\Response(response=200, description="Lista przelewów", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/PrzelewResource"))),
     *     @OA\Response(response=403, description="Brak uprawnień")
     * )
     */
    public function index(Request $request)
    {
        $query = Przelew::query()->with(['kontoNadawcy.uzytkownik']); // Załaduj konto nadawcy i jego użytkownika

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('id_konta_nadawcy')) {
            $query->where('id_konta_nadawcy', $request->input('id_konta_nadawcy'));
        }
        // Możesz dodać więcej filtrów (np. po dacie, kwocie, odbiorcy)

        $przelewy = $query->orderBy('data_zlecenia', 'desc')->paginate($request->input('per_page', 15));
        return PrzelewResource::collection($przelewy);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/przelewy/{idKonta}",
     *     summary="Pobiera historię przelewów dla danego konta",
     *     tags={"Admin-Przelewy"},
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
    public function przelewy_konta(Request $request, $idKonta)
    {
        $konto = Konto::find($idKonta);
        if (!$konto) {
            return response()->json(['message' => 'Konto nie znalezione'], 404);
        }


        // 3. Przygotuj zapytanie
        $query = Przelew::query();

        // 4. Filtrowanie
        $typ = $request->query('typ');

        if ($typ === 'wychodzace') {
            $query->where('id_konta_nadawcy', $idKonta);
        } elseif ($typ === 'przychodzace') {
            // Upewnij się, że model Konto ma pole 'numer_rachunku' lub odpowiednik
            if (empty($konto->nr_konta)) {
                // Sytuacja awaryjna - konto nie ma numeru rachunku do porównania
                // Możesz zwrócić pustą listę lub błąd, zależnie od logiki biznesowej
                return PrzelewResource::collection(collect()); // Pusta kolekcja
            }
            $query->where('nr_konta_odbiorcy', $konto->nr_konta);
        } else {
            // Jeśli typ nie jest podany lub jest niepoprawny (wg specyfikacji, brak typu oznacza wszystkie)
            // Pokaż przelewy, gdzie konto jest nadawcą LUB odbiorcą
            // Upewnij się, że model Konto ma pole 'numer_rachunku'
            if (empty($konto->nr_konta)) {
                // Jeśli nie ma numeru rachunku, możemy pokazać tylko wychodzące
                $query->where('id_konta_nadawcy', $idKonta);
            } else {
                $query->where(function ($subQuery) use ($idKonta, $konto) {
                    $subQuery->where('id_konta_nadawcy', $idKonta)
                        ->orWhere('nr_konta_odbiorcy', $konto->nr_konta);
                });
            }
        }

        // Domyślne sortowanie - najnowsze najpierw
        $query->orderBy('data_zlecenia', 'desc');

        // 5. Paginacja
        $naStrone = $request->query('na_strone', 15);
        $przelewyPaginator = $query->paginate($naStrone, ['*'], 'page', $request->query('strona', 1));

        // **NOWA CZĘŚĆ: Dodanie pola określającego typ przelewu z perspektywy konta**
        $numerRachunkuKontekstowegoKonta = $konto->numer_rachunku; // Upewnij się, że to pole istnieje i jest poprawne

        // Modyfikujemy kolekcję wewnątrz paginatora
        // $przelewyPaginator->getCollection() zwraca Illuminate\Support\Collection
        $przelewyPaginator->getCollection()->transform(function ($przelew) use ($idKonta, $numerRachunkuKontekstowegoKonta,$query) {
            if ($przelew->id_konta_nadawcy == $idKonta) {
                $przelew->typ_dla_konta_kontekstowego = 'wychodzacy';
            } elseif ($numerRachunkuKontekstowegoKonta && $przelew->nr_konta_odbiorcy == $numerRachunkuKontekstowegoKonta) {
                $przelew->typ_dla_konta_kontekstowego = 'przychodzacy';
            } else {
                // Ta sytuacja nie powinna wystąpić, jeśli filtry są poprawne,
                // ale można ustawić wartość domyślną lub null.
                $przelew->typ_dla_konta_kontekstowego = 'przychodzacy'; // lub 'nieokreslony'
            }
            $przelew->nazwa_nadawcy = Uzytkownik::query()->where('id',Konto::query()->where('id', $przelew->id_konta_nadawcy)->value('id_uzytkownika'))->value('imie');
            $przelew->nr_konta_nadawcy = Konto::query()->where('id', $przelew->id_konta_nadawcy)->value('nr_konta');
            return $przelew; // transform oczekuje zwróconego (zmodyfikowanego) elementu
        });

        return PrzelewResource::collection($przelewyPaginator);
    }
}
