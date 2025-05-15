<?php
namespace App\Http\Controllers\Api; // Dostosuj namespace

use App\Http\Controllers\Controller;
use App\Models\Konto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
/**
 * @OA\Schema(
 *     schema="KontoResource",
 *     title="Konto Resource",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="numer_konta", type="string", example="PL12345678901234567890123456"),
 *     @OA\Property(property="aktualne_saldo", type="number", format="float", example=1500.75),
 *     @OA\Property(property="waluta", type="string", example="PLN"),
 *     @OA\Property(property="limit_przelewu_dzienny", type="number", format="float", example=1000.00, nullable=true),
 *     @OA\Property(property="czy_zablokowane", type="boolean", example=false),
 *     @OA\Property(property="utworzono", type="string", format="date-time", example="2023-05-15T10:00:00Z")
 * )
 */
class KontoController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/konta",
     *     summary="Pobiera listę kont zalogowanego użytkownika wraz z saldami",
     *     tags={"Konta"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista kont użytkownika",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="nr_konta", type="string", example="PL12345678901234567890123456"),
     *                 @OA\Property(property="saldo", type="number", format="float", example=1500.75),
     *                 @OA\Property(property="waluta", type="string", example="PLN")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Nieautoryzowany")
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user(); // lub Auth::user();
        $konta = $user->konta()->select(['id', 'nr_konta', 'saldo'])->get(); // Wybierz tylko potrzebne kolumny

        // Możesz chcieć dodać walutę lub inne informacje
        // np. jeśli masz pole waluta w tabeli konta:
        // $konta = $user->konta()->select(['id', 'nr_konta', 'saldo', 'waluta'])->get();

        return response()->json($konta);
    }

    /**
     * @OA\Get(
     *     path="/api/konta/{idKonta}",
     *     summary="Pobiera szczegóły konkretnego konta użytkownika",
     *     tags={"Konta"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="idKonta",
     *         in="path",
     *         required=true,
     *         description="ID konta",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Szczegóły konta",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="nr_konta", type="string", example="PL12345678901234567890123456"),
     *             @OA\Property(property="saldo", type="number", format="float", example=1500.75),
     *             @OA\Property(property="limit_przelewu", type="number", format="float", example=1000.00),
     *             @OA\Property(property="zablokowane", type="boolean", example=false)
     *
     *         )
     *     ),
     *     @OA\Response(response=401, description="Nieautoryzowany"),
     *     @OA\Response(response=403, description="Brak uprawnień (konto nie należy do użytkownika)"),
     *     @OA\Response(response=404, description="Konto nie znalezione")
     * )
     */
    public function show(Request $request, Konto $konto) // Route Model Binding
    {
        // Sprawdzenie, czy zalogowany użytkownik jest właścicielem tego konta
        if ($request->user()->id !== $konto->id_uzytkownika) {
            return response()->json(['message' => 'Brak uprawnień do tego konta.'], 403);
        }

        // Zwróć tylko potrzebne dane, np. używając $konto->only([...]) lub Resource API
        return response()->json($konto);
    }
}
