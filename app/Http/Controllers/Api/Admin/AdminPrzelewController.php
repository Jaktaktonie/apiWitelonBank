<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PrzelewResource; // Użyj swojego istniejącego PrzelewResource
use App\Models\Przelew;
use Illuminate\Http\Request;

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
}
