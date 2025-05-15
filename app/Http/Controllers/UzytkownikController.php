<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Używane pośrednio przez Sanctum, ale nie bezpośrednio tutaj
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail; // Ważne dla wysyłania maili
use App\Models\Uzytkownik; // Twój model użytkownika
use App\Mail\DwuetapowyKodMail; // Twój Mailable
use OpenApi\Attributes as OA; // Dla Swaggera/OpenAPI
use Illuminate\Support\Facades\Log; // Do logowania błędów

class uzytkownikController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Logowanie użytkownika (Krok 1: Weryfikacja hasła)",
     *     description="Weryfikuje email i hasło. Jeśli poprawne, wysyła kod 2FA na email.",
     *     tags={"Autoryzacja"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Dane logowania",
     *         @OA\JsonContent(
     *             required={"email", "haslo"},
     *             @OA\Property(property="email", type="string", format="email", example="jan.kowalski@example.com"),
     *             @OA\Property(property="haslo", type="string", format="password", example="superTajemneHaslo123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Wymagana weryfikacja dwuetapowa. Kod wysłany na email.",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Wymagana weryfikacja dwuetapowa. Kod został wysłany na Twój adres email."),
     *             @OA\Property(property="email", type="string", format="email", example="jan.kowalski@example.com")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Nieautoryzowany (błędne dane lub konto niezweryfikowane)",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Nieprawidłowy email lub hasło.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Błąd walidacji",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Podane dane są nieprawidłowe."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Błąd serwera (np. problem z wysyłką maila)",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Nie udało się wysłać kodu weryfikacyjnego. Spróbuj ponownie później.")
     *         )
     *     )
     * )
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'haslo' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Podane dane są nieprawidłowe.',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Uzytkownik::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->haslo, $user->haslo_hash)) {
            return response()->json(['message' => 'Nieprawidłowy email lub hasło.'], 401);
        }

        if (!$user->weryfikacja) {
            // Możesz rozważyć inny kod błędu lub komunikat, jeśli 'weryfikacja' to weryfikacja emaila po rejestracji, a nie 2FA
            return response()->json(['message' => 'Konto nie zostało zweryfikowane.'], 401);
        }

        // Jeśli użytkownik ma już aktywny kod 2FA, który nie wygasł,
        // można rozważyć jego ponowne wysłanie lub poinformowanie, że kod już został wysłany.
        // Dla uproszczenia, generujemy nowy za każdym razem.
        $user->generujKodDwuetapowy(); // Ta metoda jest w modelu Uzytkownik

        try {
            Mail::to($user->email)->send(new DwuetapowyKodMail($user));
        } catch (\Exception $e) {
            Log::error('Błąd wysyłania maila 2FA dla użytkownika ' . $user->email . ': ' . $e->getMessage());
            // Możesz tu zresetować kod 2FA, aby nie pozostawić użytkownika w stanie "oczekiwania na kod", który nie dotarł
            // $user->resetujKodDwuetapowy();
            return response()->json(['message' => 'Nie udało się wysłać kodu weryfikacyjnego. Spróbuj ponownie później lub skontaktuj się z pomocą.'], 500);
        }

        return response()->json([
            'message' => 'Wymagana weryfikacja dwuetapowa. Kod został wysłany na Twój adres email.',
            'email' => $user->email // Zwracamy email, aby frontend wiedział, dla kogo weryfikować kod
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/verify-2fa",
     *     summary="Logowanie użytkownika (Krok 2: Weryfikacja kodu 2FA)",
     *     description="Weryfikuje kod 2FA i jeśli poprawny, loguje użytkownika i zwraca token API.",
     *     tags={"Autoryzacja"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Email użytkownika i kod 2FA",
     *         @OA\JsonContent(
     *             required={"email", "dwuetapowy_kod"},
     *             @OA\Property(property="email", type="string", format="email", example="jan.kowalski@example.com"),
     *             @OA\Property(property="dwuetapowy_kod", type="string", example="123456", description="6-cyfrowy kod z emaila")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Zalogowano pomyślnie po weryfikacji 2FA",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Zalogowano pomyślnie."),
     *             @OA\Property(property="token", type="string", example="1|abcdefghijklmnopqrstuvwxyz123456"),
     *             @OA\Property(property="user", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="imie", type="string", example="Jan"),
     *                 @OA\Property(property="nazwisko", type="string", example="Kowalski"),
     *                 @OA\Property(property="email", type="string", format="email", example="jan.kowalski@example.com"),
     *                 @OA\Property(property="administrator", type="boolean", example=false)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Nieautoryzowany (nieprawidłowy/wygasły kod lub błąd użytkownika)",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Nieprawidłowy lub wygasły kod weryfikacyjny.")
     *         )
     *     ),
     *      @OA\Response(
     *         response=404,
     *         description="Użytkownik nie znaleziony",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Użytkownik o podanym adresie email nie został znaleziony.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Błąd walidacji",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Podane dane są nieprawidłowe."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function verifyTwoFactor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'dwuetapowy_kod' => 'required|string|digits:6', // Upewnij się, że kod ma dokładnie 6 cyfr
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Podane dane są nieprawidłowe.',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Uzytkownik::where('email', $request->email)->first();

        if (!$user) {
            // Można też zwrócić 401 dla bezpieczeństwa (nie ujawniać, czy email istnieje)
            // ale 404 jest bardziej precyzyjne, jeśli to nie jest kwestia bezpieczeństwa
            return response()->json(['message' => 'Użytkownik o podanym adresie email nie został znaleziony.'], 404);
        }

        // Sprawdź, czy kod istnieje, czy się zgadza i czy nie wygasł
        if (is_null($user->dwuetapowy_kod) || // Sprawdzenie, czy kod w ogóle został wygenerowany
            $user->dwuetapowy_kod !== $request->dwuetapowy_kod ||
            is_null($user->dwuetapowy_kod_wygasa_o) || // Sprawdzenie, czy data wygaśnięcia jest ustawiona
            now()->gt($user->dwuetapowy_kod_wygasa_o)
        ) {
            // Nie resetuj kodu od razu, jeśli użytkownik mógł się pomylić.
            // Rozważ logikę blokady po X nieudanych próbach.
            // Jednak dla prostoty, jeśli kod jest niepoprawny lub wygasły, po prostu informujemy.
            // Jeśli chcesz, możesz zresetować kod po nieudanej próbie, aby zapobiec wielokrotnemu użyciu starego kodu:
            // $user->resetujKodDwuetapowy();
            return response()->json(['message' => 'Nieprawidłowy lub wygasły kod weryfikacyjny.'], 401);
        }

        // Kod jest poprawny, zresetuj go, aby nie można go było użyć ponownie
        $user->resetujKodDwuetapowy(); // Ta metoda jest w modelu Uzytkownik

        // Usuń stare tokeny (opcjonalnie, ale dobra praktyka dla bezpieczeństwa)
        // $user->tokens()->delete();

        // Wygeneruj nowy token API dla użytkownika
        $token = $user->createToken('api_token')->plainTextToken; // Możesz dać bardziej opisową nazwę tokena

        return response()->json([
            'message' => 'Zalogowano pomyślnie.',
            'token' => $token,
            'user' => [ // Zwracamy tylko potrzebne dane użytkownika
                'id' => $user->id,
                'imie' => $user->imie,
                'nazwisko' => $user->nazwisko,
                'email' => $user->email,
                'administrator' => (bool) $user->administrator,
            ]
        ], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Wylogowanie użytkownika",
     *     description="Unieważnia aktualny token API użytkownika.",
     *     tags={"Autoryzacja"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Wylogowano pomyślnie",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Wylogowano pomyślnie.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Nieautoryzowany (brak lub nieprawidłowy token)",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated.")
     *         )
     *     )
     * )
     */
    public function logout(Request $request)
    {
        // Upewnij się, że trasa /api/logout jest chroniona przez middleware 'auth:sanctum'
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Wylogowano pomyślnie.'], 200);
    }
}
