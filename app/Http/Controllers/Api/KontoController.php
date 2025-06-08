<?php
namespace App\Http\Controllers\Api; // Dostosuj namespace

use App\Http\Controllers\Controller;
use App\Mail\PotwierdzenieZamknieciaKontaMail;
use App\Models\Konto;
use App\Models\Uzytkownik;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon; // Dla daty ważności tokenu

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

        return response()->json($konto);
    }

    /**
     * @OA\Post(
     *     path="/api/konta/{konto}/zglos-zamkniecie",
     *     summary="Inicjuje proces zamykania konta przez użytkownika",
     *     tags={"Konta"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="konto",
     *         in="path",
     *         required=true,
     *         description="ID konta do zamknięcia",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Wysłano email z linkiem potwierdzającym"),
     *     @OA\Response(response=403, description="Brak uprawnień do tego konta"),
     *     @OA\Response(response=404, description="Konto nie znalezione"),
     *     @OA\Response(response=409, description="Konto już jest zablokowane lub ma niezerowe saldo (konflikt)"),
     *     @OA\Response(response=500, description="Błąd wysyłki emaila")
     * )
     */
    public function zglosZamkniecieKonta(Konto $konto): \Illuminate\Http\JsonResponse
    {
        $uzytkownik = Auth::user();

        if ($konto->id_uzytkownika !== $uzytkownik->id) {
            return response()->json(['message' => 'Brak uprawnień do zarządzania tym kontem.'], 403);
        }

        if ($konto->zablokowane) { // Używamy 'zablokowane' jako flagi "zamknięte"
            return response()->json(['message' => 'To konto jest już zamknięte/zablokowane.'], 409);
        }

        // WARUNEK BIZNESOWY: Można dodać sprawdzenie salda
        if ($konto->saldo > 0) {
            return response()->json(['message' => 'Nie można zamknąć konta z dodatnim saldem. Najpierw wypłać środki.'], 409);
        }
        // Można też sprawdzić, czy nie ma aktywnych zleceń stałych, kart itp.

        $token = Str::random(60);
        $konto->token_zamkniecia = $token;
        $konto->token_zamkniecia_wygasa_o = Carbon::now()->addHours(24);
        $konto->save();

        // Generowanie linku - upewnij się, że APP_URL w .env jest poprawnie ustawiony
        // oraz że frontend będzie w stanie obsłużyć ten link i przekierować na odpowiedni endpoint API
        // Tutaj tworzymy link bezpośrednio do endpointu API dla uproszczenia
        $linkPotwierdzajacy = url('/api/konta/zamknij-potwierdz/' . $token);

        try {
            Mail::to($uzytkownik->email)->send(new PotwierdzenieZamknieciaKontaMail($konto, $uzytkownik, $linkPotwierdzajacy));
            return response()->json(['message' => 'Wysłano email z linkiem potwierdzającym zamknięcie konta. Sprawdź swoją skrzynkę pocztową.']);
        } catch (\Exception $e) {
            Log::error("Błąd wysyłki maila potwierdzającego zamknięcie konta: " . $e->getMessage(), ['konto_id' => $konto->id]);
            // Można cofnąć zapis tokenu, jeśli email się nie udał, lub zostawić i pozwolić użytkownikowi spróbować ponownie
            $konto->token_zamkniecia = null;
            $konto->token_zamkniecia_wygasa_o = null;
            $konto->save();
            return response()->json(['message' => 'Nie udało się wysłać emaila potwierdzającego. Spróbuj ponownie później.'], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/konta/zamknij-potwierdz/{token}",
     *     summary="Potwierdza i wykonuje zamknięcie konta na podstawie tokenu z emaila",
     *     tags={"Konta"},
     *     @OA\Parameter(
     *         name="token",
     *         in="path",
     *         required=true,
     *         description="Token potwierdzający zamknięcie konta",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Konto zostało pomyślnie zamknięte"),
     *     @OA\Response(response=400, description="Nieprawidłowy lub przedawniony token"),
     *     @OA\Response(response=404, description="Konto nie znalezione dla tego tokenu"),
     *     @OA\Response(response=409, description="Nie można zamknąć konta (np. dodatnie saldo)")
     * )
     */
    public function potwierdzZamkniecieKonta(Request $request, string $token): \Illuminate\Http\JsonResponse // Request może być potrzebny, jeśli przekazujesz coś w query params
    {
        $konto = Konto::where('token_zamkniecia', $token)
            ->where('token_zamkniecia_wygasa_o', '>', Carbon::now())
            ->first();

        if (!$konto) {
            return response()->json(['message' => 'Nieprawidłowy lub przedawniony link potwierdzający.'], 400);
        }

        // Ponowne sprawdzenie warunków biznesowych (na wszelki wypadek, gdyby stan się zmienił od wysłania maila)
        if ($konto->zablokowane) {
            return response()->json(['message' => 'To konto jest już zamknięte/zablokowane.'], 409);
        }
        if ($konto->saldo > 0) {
            return response()->json(['message' => 'Nie można zamknąć konta z dodatnim saldem.'], 409);
        }

        // Faktyczne zamknięcie konta
        $konto->zablokowane = true; // Traktujemy 'zablokowane' jako 'zamknięte'
        $konto->nr_konta = $konto->nr_konta . ' _zamknięte';
        $konto->token_zamkniecia = null;
        $konto->token_zamkniecia_wygasa_o = null;
        // $konto->oczekuje_na_zamkniecie = false; // Jeśli używasz tej flagi
        $konto->save();

        Log::info("Konto ID: {$konto->id} zostało pomyślnie zamknięte przez użytkownika ID: {$konto->id_uzytkownika}.");
        return response()->json(['message' => 'Twoje konto zostało pomyślnie zamknięte.']);
    }

}
