<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\KontoResource; // Użyj swojego istniejącego KontoResource
use App\Models\Konto;
use App\Models\Uzytkownik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="Admin-Konta",
 *     description="Operacje administracyjne na kontach użytkowników (WBK-02)"
 * )
 */
class AdminKontoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/konta",
     *     summary="Pobiera listę wszystkich kont użytkowników",
     *     tags={"Admin-Konta"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer", default=1), description="Numer strony"),
     *     @OA\Response(response=200, description="Lista kont", @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/KontoResource"))),
     *     @OA\Response(response=403, description="Brak uprawnień")
     * )
     */
    public function index()
    {
        $konta = Konto::with('uzytkownik')->paginate(15); // Paginacja dla wydajności
        return $konta;
    }

    /**
     * @OA\Get(
     *     path="/api/admin/konta/{idKonta}",
     *     summary="Pobiera szczegóły konkretnego konta użytkownika",
     *     tags={"Admin-Konta"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="idKonta", in="path", required=true, description="ID konta", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Szczegóły konta", @OA\JsonContent(ref="#/components/schemas/KontoResource")),
     *     @OA\Response(response=404, description="Konto nie znalezione"),
     *     @OA\Response(response=403, description="Brak uprawnień")
     * )
     */
    public function show(Konto $konto) // Route model binding
    {
        return $konto->load('uzytkownik');
    }

    /**
     * @OA\Get(
     *     path="/api/admin/uzytkownik/{idUzytkownika}",
     *     summary="Pobiera szczegóły konkretnego użytkownika",
     *     tags={"Admin-Konta"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="idUzytkownika", in="path", required=true, description="ID konta", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Szczegóły konta", @OA\JsonContent(ref="#/components/schemas/KontoResource")),
     *     @OA\Response(response=404, description="Konto nie znalezione"),
     *     @OA\Response(response=403, description="Brak uprawnień")
     * )
     */
    public function uzytkownik(Uzytkownik $uzytkownik) // Route model binding
    {
        return $uzytkownik->load("portfel")->load("konta");
    }

    /**
     * @OA\Patch(
     *     path="/api/admin/konta/{idKonta}/block",
     *     summary="Blokuje konto użytkownika",
     *     tags={"Admin-Konta"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="idKonta", in="path", required=true, description="ID konta do zablokowania", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Konto zablokowane", @OA\JsonContent(ref="#/components/schemas/KontoResource")),
     *     @OA\Response(response=404, description="Konto nie znalezione")
     * )
     */
    public function blockAccount(Konto $konto)
    {
        $konto->zablokowane = true;
        $konto->save();
        return new KontoResource($konto->load('uzytkownik'));
    }

    /**
     * @OA\Patch(
     *     path="/api/admin/konta/{idKonta}/unblock",
     *     summary="Odblokowuje konto użytkownika",
     *     tags={"Admin-Konta"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="idKonta", in="path", required=true, description="ID konta do odblokowania", @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Konto odblokowane", @OA\JsonContent(ref="#/components/schemas/KontoResource")),
     *     @OA\Response(response=404, description="Konto nie znalezione")
     * )
     */
    public function unblockAccount(Konto $konto)
    {
        $konto->zablokowane = false;
        $konto->save();
        return new KontoResource($konto->load('uzytkownik'));
    }

    /**
     * @OA\Patch(
     *     path="/api/admin/konta/{idKonta}/limit",
     *     summary="Aktualizuje limit przelewu dla konta użytkownika",
     *     tags={"Admin-Konta"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="idKonta", in="path", required=true, description="ID konta", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"limit_przelewu"},
     *             @OA\Property(property="limit_przelewu", type="number", format="float", example=5000.00, description="Nowy limit przelewu")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Limit zaktualizowany", @OA\JsonContent(ref="#/components/schemas/KontoResource")),
     *     @OA\Response(response=404, description="Konto nie znalezione"),
     *     @OA\Response(response=422, description="Błąd walidacji")
     * )
     */
    public function updateLimit(Request $request, Konto $konto)
    {
        $validator = Validator::make($request->all(), [
            'limit_przelewu' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $konto->limit_przelewu = $request->input('limit_przelewu');
        $konto->save();
        return new KontoResource($konto->load('uzytkownik'));
    }
}
