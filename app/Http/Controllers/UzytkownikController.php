<?php

namespace App\Http\Controllers;

use App\Models\Uzytkownik;
use Illuminate\Http\Request;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="API Dokumentacja - Użytkownicy",
 *      description="Dokumentacja API dla zarządzania użytkownikami",
 *      @OA\Contact(
 *          email="support@witelonbank.com"
 *      )
 * )
 */
class UzytkownikController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/uzytkownicy",
     *     summary="Pobiera listę wszystkich użytkowników",
     *     tags={"Użytkownicy"},
     *     @OA\Response(
     *         response=200,
     *         description="Lista użytkowników",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="imie", type="string"),
     *                 @OA\Property(property="nazwisko", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="telefon", type="string"),
     *                 @OA\Property(property="weryfikacja", type="boolean"),
     *                 @OA\Property(property="administrator", type="boolean"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     )
     * )
     */
    public function index()
    {
        $uzytkownicy = Uzytkownik::all();
        return response()->json($uzytkownicy);
    }
}
