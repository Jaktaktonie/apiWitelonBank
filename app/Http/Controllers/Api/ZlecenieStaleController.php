<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ZlecenieStale;
use App\Http\Resources\ZlecenieStaleResource;
use App\Http\Requests\StoreZlecenieStaleRequest;
use App\Http\Requests\UpdateZlecenieStaleRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

/**
 * @OA\Tag(
 *     name="Zlecenia Stałe",
 *     description="Operacje związane ze zleceniami stałymi (płatnościami cyklicznymi)"
 * )
 */
class ZlecenieStaleController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/zlecenia-stale",
     *     summary="Wyświetla listę zleceń stałych zalogowanego użytkownika",
     *     tags={"Zlecenia Stałe"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista zleceń stałych",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/ZlecenieStale"))
     *     ),
     *     @OA\Response(response=401, description="Nieautoryzowany")
     * )
     */
    public function index(): JsonResponse
    {
        $zlecenia = Auth::user()->zleceniaStale()->with(['kontoZrodlowe'])->latest()->get();
        return response()->json(ZlecenieStaleResource::collection($zlecenia));
    }

    /**
     * @OA\Post(
     *     path="/api/zlecenia-stale",
     *     summary="Tworzy nowe zlecenie stałe",
     *     tags={"Zlecenia Stałe"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Dane nowego zlecenia stałego",
     *         @OA\JsonContent(ref="#/components/schemas/StoreZlecenieStaleRequest")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Zlecenie stałe utworzone pomyślnie",
     *         @OA\JsonContent(ref="#/components/schemas/ZlecenieStale")
     *     ),
     *     @OA\Response(response=401, description="Nieautoryzowany"),
     *     @OA\Response(response=403, description="Brak uprawnień do konta źródłowego"),
     *     @OA\Response(response=422, description="Błąd walidacji")
     * )
     */
    public function store(StoreZlecenieStaleRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Ustawienie pierwszej daty następnego wykonania na datę startu
        $data['data_nastepnego_wykonania'] = $data['data_startu'];
        $data['id_uzytkownika'] = Auth::id();

        $zlecenie = ZlecenieStale::create($data);

        return response()->json(new ZlecenieStaleResource($zlecenie->load('kontoZrodlowe')), 201);
    }

    /**
     * @OA\Get(
     *     path="/api/zlecenia-stale/{zlecenie_stale}",
     *     summary="Wyświetla szczegóły konkretnego zlecenia stałego",
     *     tags={"Zlecenia Stałe"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="zlecenie_stale",
     *         in="path",
     *         required=true,
     *         description="ID zlecenia stałego",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Szczegóły zlecenia",
     *         @OA\JsonContent(ref="#/components/schemas/ZlecenieStale")
     *     ),
     *     @OA\Response(response=401, description="Nieautoryzowany"),
     *     @OA\Response(response=403, description="Brak uprawnień"),
     *     @OA\Response(response=404, description="Zlecenie nie znalezione")
     * )
     */
    public function show(ZlecenieStale $zlecenie_stale): JsonResponse
    {
        if ($zlecenie_stale->id_uzytkownika !== Auth::id()) {
            return response()->json(['message' => 'Brak uprawnień do tego zasobu.'], 403);
        }
        return response()->json(new ZlecenieStaleResource($zlecenie_stale->load(['kontoZrodlowe', 'uzytkownik'])));
    }

    /**
     * @OA\Put(
     *     path="/api/zlecenia-stale/{zlecenie_stale}",
     *     summary="Aktualizuje istniejące zlecenie stałe",
     *     tags={"Zlecenia Stałe"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="zlecenie_stale",
     *         in="path",
     *         required=true,
     *         description="ID zlecenia stałego",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Dane do aktualizacji zlecenia stałego",
     *         @OA\JsonContent(ref="#/components/schemas/UpdateZlecenieStaleRequest")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Zlecenie zaktualizowane pomyślnie",
     *         @OA\JsonContent(ref="#/components/schemas/ZlecenieStale")
     *     ),
     *     @OA\Response(response=401, description="Nieautoryzowany"),
     *     @OA\Response(response=403, description="Brak uprawnień"),
     *     @OA\Response(response=404, description="Zlecenie nie znalezione"),
     *     @OA\Response(response=422, description="Błąd walidacji")
     * )
     */
    public function update(UpdateZlecenieStaleRequest $request, ZlecenieStale $zlecenie_stale): JsonResponse
    {
        $data = $request->validated();

        // Jeśli zmieniono datę startu lub częstotliwość, przelicz data_nastepnego_wykonania
        // Ta logika może być bardziej skomplikowana w zależności od wymagań biznesowych
        // np. co jeśli zlecenie już było wykonane, a zmieniamy datę startu na późniejszą?
        if (isset($data['data_startu']) || isset($data['czestotliwosc'])) {
            // Tymczasowo tworzymy obiekt z nowymi danymi, aby móc użyć metody obliczającej
            $tempZlecenie = clone $zlecenie_stale;
            $tempZlecenie->fill($data); // Zastosuj nowe dane do tymczasowego obiektu
            $data['data_nastepnego_wykonania'] = $tempZlecenie->obliczNastepneWykonanie( $data['data_startu'] ?? $zlecenie_stale->data_startu->format('Y-m-d') );
        }

        $data['id_uzytkownika'] = Auth::id();

        $zlecenie_stale->update($data);
        return response()->json(new ZlecenieStaleResource($zlecenie_stale->fresh()->load(['kontoZrodlowe', 'uzytkownik'])));
    }

    /**
     * @OA\Delete(
     *     path="/api/zlecenia-stale/{zlecenie_stale}",
     *     summary="Usuwa (lub dezaktywuje) zlecenie stałe",
     *     tags={"Zlecenia Stałe"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="zlecenie_stale",
     *         in="path",
     *         required=true,
     *         description="ID zlecenia stałego",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Zlecenie usunięte/dezaktywowane pomyślnie",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Zlecenie stałe zostało pomyślnie dezaktywowane.")
     *          )
     *     ),
     *     @OA\Response(response=204, description="Zlecenie usunięte pomyślnie (jeśli usuwamy fizycznie)"),
     *     @OA\Response(response=401, description="Nieautoryzowany"),
     *     @OA\Response(response=403, description="Brak uprawnień"),
     *     @OA\Response(response=404, description="Zlecenie nie znalezione")
     * )
     */
    public function destroy(ZlecenieStale $zlecenie_stale): JsonResponse
    {
        if ($zlecenie_stale->id_uzytkownika !== Auth::id()) {
            return response()->json(['message' => 'Brak uprawnień do tego zasobu.'], 403);
        }

        // Zamiast fizycznego usuwania, lepiej dezaktywować
        $zlecenie_stale->update(['aktywne' => false]);
        // lub $zlecenie_stale->delete(); jeśli chcesz fizycznie usunąć

        return response()->json(['message' => 'Zlecenie stałe zostało pomyślnie dezaktywowane.']); // Lub 204 jeśli delete()
    }
}
