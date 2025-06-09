<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\KartaResource;
use App\Models\Karta;
use App\Models\Konto;

// Potrzebne do pobierania kart dla konta
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

/**
 * @OA\Tag(
 *     name="Karty Płatnicze",
 *     description="Operacje związane z kartami płatniczymi użytkownika"
 * )
 */
class KartaController extends Controller
{

    /**
     * @OA\Get(
     *     path="/api/konta/{id_konta}/karty",
     *     summary="Wyświetla listę kart płatniczych dla danego konta użytkownika",
     *     tags={"Karty Płatnicze"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id_konta",
     *         in="path",
     *         required=true,
     *         description="ID konta",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista kart",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Karta"))
     *     ),
     *     @OA\Response(response=401, description="Nieautoryzowany"),
     *     @OA\Response(response=403, description="Brak uprawnień do tego konta"),
     *     @OA\Response(response=404, description="Konto nie znalezione")
     * )
     */
    public function index(Konto $konto)
    {
        // Sprawdzenie, czy zalogowany użytkownik jest właścicielem konta
        if (Auth::id() !== $konto->id_uzytkownika) {
            return response()->json(['message' => 'Brak uprawnień do wyświetlenia kart dla tego konta.'], 403);
        }
        $karty = $konto->karty()->get();
        return response()->json(KartaResource::collection($karty));
    }

    /**
     * @OA\Get(
     *     path="/api/karty/{karta}",
     *     summary="Wyświetla szczegóły konkretnej karty płatniczej",
     *     tags={"Karty Płatnicze"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="karta",
     *         in="path",
     *         required=true,
     *         description="ID karty",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Szczegóły karty",
     *         @OA\JsonContent(ref="#/components/schemas/Karta")
     *     ),
     *     @OA\Response(response=401, description="Nieautoryzowany"),
     *     @OA\Response(response=403, description="Brak uprawnień do tej karty"),
     *     @OA\Response(response=404, description="Karta nie znaleziona")
     * )
     */
    public function show(Karta $karta): JsonResponse
    {
        if (Auth::id() !== $karta->konto->id_uzytkownika) {
            return response()->json(['message' => 'Brak uprawnień do wyświetlenia tej karty.'], 403);
        }
        return response()->json(new KartaResource($karta->load('konto')));
    }


    /**
     * @OA\Patch(
     *     path="/api/karty/{karta}/zablokuj",
     *     summary="Blokuje kartę płatniczą",
     *     tags={"Karty Płatnicze"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="karta",
     *         in="path",
     *         required=true,
     *         description="ID karty do zablokowania",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Karta zablokowana pomyślnie",
     *         @OA\JsonContent(ref="#/components/schemas/Karta")
     *     ),
     *     @OA\Response(response=401, description="Nieautoryzowany"),
     *     @OA\Response(response=403, description="Brak uprawnień do tej karty"),
     *     @OA\Response(response=404, description="Karta nie znaleziona")
     * )
     */
    public function zablokujKarte(Karta $karta): JsonResponse
    {
        if (Auth::id() !== $karta->konto->id_uzytkownika) {
            return response()->json(['message' => 'Brak uprawnień do zarządzania tą kartą.'], 403);
        }

        $karta->zablokuj();
        return response()->json(['message' => 'Karta została pomyślnie zablokowana.', 'data' => new KartaResource($karta)]);
    }

    /**
     * @OA\Patch(
     *     path="/api/karty/{karta}/odblokuj",
     *     summary="Odblokowuje kartę płatniczą",
     *     tags={"Karty Płatnicze"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="karta",
     *         in="path",
     *         required=true,
     *         description="ID karty do odblokowania",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Karta odblokowana pomyślnie",
     *         @OA\JsonContent(ref="#/components/schemas/Karta")
     *     ),
     *     @OA\Response(response=401, description="Nieautoryzowany"),
     *     @OA\Response(response=403, description="Brak uprawnień do tej karty"),
     *     @OA\Response(response=404, description="Karta nie znaleziona")
     * )
     */
    public function odblokujKarte(Karta $karta): JsonResponse
    {
        if (Auth::id() !== $karta->konto->id_uzytkownika) {
            return response()->json(['message' => 'Brak uprawnień do zarządzania tą kartą.'], 403);
        }

        $karta->odblokuj();
        return response()->json(['message' => 'Karta została pomyślnie odblokowana.', 'data' => new KartaResource($karta)]);
    }

    /**
     * @OA\Patch(
     *     path="/api/karty/{karta}/limit",
     *     summary="Zmienia dzienny limit transakcji dla karty",
     *     tags={"Karty Płatnicze"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="karta",
     *         in="path",
     *         required=true,
     *         description="ID karty",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Nowy limit dzienny",
     *         @OA\JsonContent(
     *             required={"limit_dzienny"},
     *             @OA\Property(property="limit_dzienny", type="number", format="float", example=500.00, description="Nowy dzienny limit. Ustaw 0 lub null aby usunąć limit (jeśli dozwolone).")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Limit zmieniony pomyślnie",
     *         @OA\JsonContent(ref="#/components/schemas/Karta")
     *     ),
     *     @OA\Response(response=401, description="Nieautoryzowany"),
     *     @OA\Response(response=403, description="Brak uprawnień do tej karty"),
     *     @OA\Response(response=404, description="Karta nie znaleziona"),
     *     @OA\Response(response=422, description="Błąd walidacji")
     * )
     */
    public function zmienLimit(Request $request, Karta $karta): JsonResponse
    {
        if (Auth::id() !== $karta->konto->id_uzytkownika) {
            return response()->json(['message' => 'Brak uprawnień do zarządzania tą kartą.'], 403);
        }

        $validated = $request->validate([
            'limit_dzienny' => 'required|numeric|min:0|max:99999.99', // Dostosuj max jeśli trzeba
        ]);

        $karta->zmienLimit((float)$validated['limit_dzienny']);
        return response()->json(['message' => 'Limit dzienny karty został pomyślnie zmieniony.', 'data' => new KartaResource($karta)]);
    }

    /**
     * @OA\Patch(
     *     path="/api/karty/{karta}/ustawienia-platnosci",
     *     summary="Zmienia ustawienia płatności internetowych/zbliżeniowych dla karty",
     *     tags={"Karty Płatnicze"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="karta",
     *         in="path",
     *         required=true,
     *         description="ID karty",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         description="Nowe ustawienia płatności. Przekaż tylko te, które chcesz zmienić.",
     *         @OA\JsonContent(
     *             @OA\Property(property="platnosci_internetowe_aktywne", type="boolean", example=true, description="Status płatności internetowych"),
     *             @OA\Property(property="platnosci_zblizeniowe_aktywne", type="boolean", example=false, description="Status płatności zbliżeniowych")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Ustawienia płatności zmienione pomyślnie",
     *         @OA\JsonContent(ref="#/components/schemas/Karta")
     *     ),
     *     @OA\Response(response=401, description="Nieautoryzowany"),
     *     @OA\Response(response=403, description="Brak uprawnień do tej karty"),
     *     @OA\Response(response=404, description="Karta nie znaleziona"),
     *     @OA\Response(response=422, description="Błąd walidacji")
     * )
     */
    public function zmienUstawieniaPlatnosci(Request $request, Karta $karta): JsonResponse
    {
        if (Auth::id() !== $karta->konto->id_uzytkownika) {
            return response()->json(['message' => 'Brak uprawnień do zarządzania tą kartą.'], 403);
        }

        $validated = $request->validate([
            'platnosci_internetowe_aktywne' => 'sometimes|boolean',
            'platnosci_zblizeniowe_aktywne' => 'sometimes|boolean',
        ]);

        // Aktualizuj tylko jeśli dane zostały przesłane
        if ($request->has('platnosci_internetowe_aktywne')) {
            $karta->platnosci_internetowe_aktywne = $validated['platnosci_internetowe_aktywne'];
        }
        if ($request->has('platnosci_zblizeniowe_aktywne')) {
            $karta->platnosci_zblizeniowe_aktywne = $validated['platnosci_zblizeniowe_aktywne'];
        }

        $karta->save();

        return response()->json(['message' => 'Ustawienia płatności karty zostały pomyślnie zmienione.', 'data' => new KartaResource($karta)]);
    }
}
