<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Przelew;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @OA\Tag(
 *     name="Admin-Raporty",
 *     description="Generowanie raportów przez administratora (WBK-04)"
 * )
 */
class AdminRaportController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/admin/raporty/przelewy",
     *     summary="Generuje raport finansowy dotyczący przelewów",
     *     tags={"Admin-Raporty"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="data_od", in="query", @OA\Schema(type="string", format="date", example="2023-01-01"), description="Data początkowa raportu (YYYY-MM-DD)"),
     *     @OA\Parameter(name="data_do", in="query", @OA\Schema(type="string", format="date", example="2023-01-31"), description="Data końcowa raportu (YYYY-MM-DD)"),
     *     @OA\Parameter(name="waluta", in="query", @OA\Schema(type="string", example="PLN"), description="Filtruj wg waluty (opcjonalnie)"),
     *     @OA\Response(
     *         response=200,
     *         description="Raport finansowy",
     *         @OA\JsonContent(
     *             @OA\Property(property="okres_od", type="string", format="date"),
     *             @OA\Property(property="okres_do", type="string", format="date"),
     *             @OA\Property(property="liczba_wszystkich_przelewow", type="integer"),
     *             @OA\Property(property="suma_wszystkich_przelewow", type="object",
     *                 @OA\AdditionalProperties(type="number", format="float", description="Suma kwot dla danej waluty")
     *             ),
     *             @OA\Property(property="liczba_przelewow_zrealizowanych", type="integer"),
     *             @OA\Property(property="suma_przelewow_zrealizowanych", type="object",
     *                 @OA\AdditionalProperties(type="number", format="float")
     *             ),
     *             @OA\Property(property="liczba_przelewow_oczekujacych", type="integer"),
     *             @OA\Property(property="suma_przelewow_oczekujacych", type="object",
     *                 @OA\AdditionalProperties(type="number", format="float")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=403, description="Brak uprawnień")
     * )
     */
    public function financialTransfersReport(Request $request)
    {
        $dataOd = $request->input('data_od') ? Carbon::parse($request->input('data_od'))->startOfDay() : Carbon::now()->subMonth()->startOfDay();
        $dataDo = $request->input('data_do') ? Carbon::parse($request->input('data_do'))->endOfDay() : Carbon::now()->endOfDay();

        $query = Przelew::whereBetween('data_zlecenia', [$dataOd, $dataDo]);

        if ($request->filled('waluta')) {
            $query->where('waluta_przelewu', $request->input('waluta'));
        }

        $wszystkiePrzelewy = (clone $query)->get();
        $zrealizowanePrzelewy = $wszystkiePrzelewy->where('status', 'zrealizowany');
        $oczekujacePrzelewy = $wszystkiePrzelewy->where('status', 'oczekujacy');

        $sumujPoWalucie = function ($collection) {
            return $collection->groupBy('waluta_przelewu')
                ->mapWithKeys(function ($group, $waluta) {
                    return [$waluta => round($group->sum('kwota'), 2)];
                });
        };

        return response()->json([
            'okres_od' => $dataOd->toDateString(),
            'okres_do' => $dataDo->toDateString(),
            'liczba_wszystkich_przelewow' => $wszystkiePrzelewy->count(),
            'suma_wszystkich_przelewow' => $sumujPoWalucie($wszystkiePrzelewy),
            'liczba_przelewow_zrealizowanych' => $zrealizowanePrzelewy->count(),
            'suma_przelewow_zrealizowanych' => $sumujPoWalucie($zrealizowanePrzelewy),
            'liczba_przelewow_oczekujacych' => $oczekujacePrzelewy->count(),
            'suma_przelewow_oczekujacych' => $sumujPoWalucie($oczekujacePrzelewy),
        ]);
    }
}
