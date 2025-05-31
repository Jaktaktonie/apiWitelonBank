<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Konto;
use App\Models\ZapisanyOdbiorca;
use App\Http\Resources\ZapisanyOdbiorcaResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(name="Zapisani Odbiorcy", description="Zarządzanie listą zapisanych odbiorców przelewów")
 */
class ZapisanyOdbiorcaController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/zapisani-odbiorcy",
     *     summary="Pobiera listę zapisanych odbiorców zalogowanego użytkownika",
     *     tags={"Zapisani Odbiorcy"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Lista odbiorców", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/ZapisanyOdbiorca"))),
     *     @OA\Response(response=401, description="Nieautoryzowany")
     * )
     */
    public function index()
    {
        $odbiorcy = Auth::user()->zapisaniOdbiorcy()->orderBy('nazwa_odbiorcy_zdefiniowana')->get();
        return ZapisanyOdbiorcaResource::collection($odbiorcy);
    }

    /**
     * @OA\Post(
     *     path="/api/zapisani-odbiorcy",
     *     summary="Dodaje nowego odbiorcę do listy zapisanych",
     *     tags={"Zapisani Odbiorcy"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"nazwa_odbiorcy_zdefiniowana", "nr_konta_odbiorcy"},
     *             @OA\Property(property="nazwa_odbiorcy_zdefiniowana", type="string", example="Mieszkanie"),
     *             @OA\Property(property="nr_konta_odbiorcy", type="string", example="PL12345678901234567890123456"),
     *             @OA\Property(property="rzeczywista_nazwa_odbiorcy", type="string", nullable=true, example="Jan Kowalski Wynajem"),
     *             @OA\Property(property="adres_odbiorcy_linia1", type="string", nullable=true),
     *             @OA\Property(property="adres_odbiorcy_linia2", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Odbiorca dodany", @OA\JsonContent(ref="#/components/schemas/ZapisanyOdbiorca")),
     *     @OA\Response(response=422, description="Błąd walidacji")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nazwa_odbiorcy_zdefiniowana' => 'required|string|max:255',
            'nr_konta_odbiorcy' => [
                'required', 'string', 'regex:/^[A-Z]{2}[0-9]{2}[A-Z0-9]{1,30}$/', 'max:34',
                Rule::unique('zapisani_odbiorcy')->where(function ($query) use ($request) {
                    return $query->where('id_uzytkownika', Auth::id())
                        ->where('nr_konta_odbiorcy', $request->nr_konta_odbiorcy);
                }) // Opcja: użytkownik nie może dodać tego samego nr konta dwa raz
            ],
            'rzeczywista_nazwa_odbiorcy' => 'nullable|string|max:255',
            'adres_odbiorcy_linia1' => 'nullable|string|max:255',
            'adres_odbiorcy_linia2' => 'nullable|string|max:255',
        ], [
            'nr_konta_odbiorcy.unique' => 'Ten numer konta jest już na Twojej liście zapisanych odbiorców.'
        ]);

        $odbiorca = Auth::user()->zapisaniOdbiorcy()->create($validated);
        return new ZapisanyOdbiorcaResource($odbiorca);
    }

    /**
     * @OA\Get(
     *     path="/api/zapisani-odbiorcy/{zapisany_odbiorca}",
     *     summary="Pobiera szczegóły zapisanego odbiorcy",
     *     tags={"Zapisani Odbiorcy"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="zapisany_odbiorca", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Szczegóły odbiorcy", @OA\JsonContent(ref="#/components/schemas/ZapisanyOdbiorca")),
     *     @OA\Response(response=403, description="Brak uprawnień"),
     *     @OA\Response(response=404, description="Nie znaleziono")
     * )
     */
    public function show(ZapisanyOdbiorca $zapisany_odbiorca)
    {
        if (Auth::id() !== $zapisany_odbiorca->id_uzytkownika) {
            return response()->json(['message' => 'Brak uprawnień'], 403);
        }
        return new ZapisanyOdbiorcaResource($zapisany_odbiorca);
    }

    /**
     * @OA\Put(
     *     path="/api/zapisani-odbiorcy/{zapisany_odbiorca}",
     *     summary="Aktualizuje zapisanego odbiorcę",
     *     tags={"Zapisani Odbiorcy"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="zapisany_odbiorca", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="nazwa_odbiorcy_zdefiniowana", type="string"),
     *             @OA\Property(property="nr_konta_odbiorcy", type="string"),
     *             @OA\Property(property="rzeczywista_nazwa_odbiorcy", type="string", nullable=true),
     *             @OA\Property(property="adres_odbiorcy_linia1", type="string", nullable=true),
     *             @OA\Property(property="adres_odbiorcy_linia2", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Odbiorca zaktualizowany", @OA\JsonContent(ref="#/components/schemas/ZapisanyOdbiorca")),
     *     @OA\Response(response=403, description="Brak uprawnień"),
     *     @OA\Response(response=404, description="Nie znaleziono"),
     *     @OA\Response(response=422, description="Błąd walidacji")
     * )
     */
    public function update(Request $request, ZapisanyOdbiorca $zapisany_odbiorca)
    {
        if (Auth::id() !== $zapisany_odbiorca->id_uzytkownika) {
            return response()->json(['message' => 'Brak uprawnień'], 403);
        }
        $validated = $request->validate([
            'nazwa_odbiorcy_zdefiniowana' => 'sometimes|required|string|max:255',
            'nr_konta_odbiorcy' => [
                'sometimes','required', 'string', 'regex:/^[A-Z]{2}[0-9]{2}[A-Z0-9]{1,30}$/', 'max:34',
                Rule::unique('zapisani_odbiorcy')->where(function ($query) use ($request) {
                    return $query->where('id_uzytkownika', Auth::id())
                        ->where('nr_konta_odbiorcy', $request->nr_konta_odbiorcy);
                })->ignore($zapisany_odbiorca->id) // Ignoruj bieżący rekord przy sprawdzaniu unikalności
            ],
            'rzeczywista_nazwa_odbiorcy' => 'nullable|string|max:255',
            'adres_odbiorcy_linia1' => 'nullable|string|max:255',
            'adres_odbiorcy_linia2' => 'nullable|string|max:255',
        ]);
        $zapisany_odbiorca->update($validated);
        return new ZapisanyOdbiorcaResource($zapisany_odbiorca);
    }

    /**
     * @OA\Delete(
     *     path="/api/zapisani-odbiorcy/{zapisany_odbiorca}",
     *     summary="Usuwa zapisanego odbiorcę",
     *     tags={"Zapisani Odbiorcy"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="zapisany_odbiorca", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Odbiorca usunięty"),
     *     @OA\Response(response=403, description="Brak uprawnień"),
     *     @OA\Response(response=404, description="Nie znaleziono")
     * )
     */
    public function destroy(ZapisanyOdbiorca $zapisany_odbiorca)
    {
        if (Auth::id() !== $zapisany_odbiorca->id_uzytkownika) {
            return response()->json(['message' => 'Brak uprawnień'], 403);
        }
        $zapisany_odbiorca->delete();
        return response()->noContent();
    }
}
