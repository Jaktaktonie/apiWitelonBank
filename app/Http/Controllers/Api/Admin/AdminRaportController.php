<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Przelew;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

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
     *     summary="Generuje raport finansowy dotyczący przelewów w formacie PDF",
     *     tags={"Admin-Raporty"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="data_od", in="query", @OA\Schema(type="string", format="date", example="2023-01-01"), description="Data początkowa raportu (YYYY-MM-DD)"),
     *     @OA\Parameter(name="data_do", in="query", @OA\Schema(type="string", format="date", example="2023-01-31"), description="Data końcowa raportu (YYYY-MM-DD)"),
     *     @OA\Parameter(name="waluta", in="query", @OA\Schema(type="string", example="PLN"), description="Filtruj wg waluty (opcjonalnie)"),
     *     @OA\Response(
     *          response=200,
     *          description="Raport finansowy w formacie PDF",
     *          @OA\MediaType(
     *              mediaType="application/pdf",
     *              @OA\Schema(
     *                  type="string",
     *                  format="binary"
     *              )
     *          )
     *     ),
     *     @OA\Response(response=403, description="Brak uprawnień")
     * )
     */
    public function financialTransfersReport(Request $request)
    {
        $dataOd = $request->input('data_od') ? Carbon::parse($request->input('data_od'))->startOfDay() : null;
        $dataDo = $request->input('data_do') ? Carbon::parse($request->input('data_do'))->endOfDay() : null;

        $query = Przelew::query();

        if ($dataOd && $dataDo) {
            $query->whereBetween('data_zlecenia', [$dataOd, $dataDo]);
        } elseif ($dataOd) {
            $query->where('data_zlecenia', '>=', $dataOd);
        } elseif ($dataDo) {
            $query->where('data_zlecenia', '<=', $dataDo);
        }

        if ($request->filled('waluta')) {
            $query->where('waluta_przelewu', $request->input('waluta'));
        }

        $wszystkiePrzelewy = (clone $query)->get();
        $zrealizowanePrzelewy = $wszystkiePrzelewy->where('status', 'zrealizowany');
        $oczekujacePrzelewy = $wszystkiePrzelewy->where('status', 'oczekujacy');

        $sumujPoWalucie = function ($collection) {
            return $collection->groupBy('waluta_przelewu')
                ->map(function ($group) {
                    return $group->sum('kwota');
                });
        };

        $dataForPdf = [
            'okres_od' => $dataOd ? $dataOd->toDateString() : 'Nie podano',
            'okres_do' => $dataDo ? $dataDo->toDateString() : 'Nie podano',
            'liczba_wszystkich_przelewow' => $wszystkiePrzelewy->count(),
            'suma_wszystkich_przelewow' => $sumujPoWalucie($wszystkiePrzelewy),
            'liczba_przelewów_zrealizowanych' => $zrealizowanePrzelewy->count(),
            'suma_przelewów_zrealizowanych' => $sumujPoWalucie($zrealizowanePrzelewy),
            'liczba_przelewów_oczekujących' => $oczekujacePrzelewy->count(),
            'suma_przelewów_oczekujących' => $sumujPoWalucie($oczekujacePrzelewy),
        ];

        $nazwaPliku = 'raport_finansowy_przelewow_' . now()->format('Y-m-d') . '.pdf';

        $pdf = Pdf::loadView('reports.financial_transfers', $dataForPdf);

        return $pdf->download($nazwaPliku);
    }
}
