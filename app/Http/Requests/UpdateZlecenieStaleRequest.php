<?php

namespace App\Http\Requests;

use App\Models\Konto;
use App\Models\ZlecenieStale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class UpdateZlecenieStaleRequest extends FormRequest
{
    /**
     * @OA\Schema(
     *     schema="UpdateZlecenieStaleRequest",
     *     title="Update Zlecenie Stałe Request",
     *     description="Pola do aktualizacji zlecenia stałego. Przekaż tylko te, które chcesz zmienić.",
     *     @OA\Property(property="nr_konta_docelowego", type="string", example="PL12345678901234567890123456", description="Numer konta docelowego (IBAN)"),
     *     @OA\Property(property="nazwa_odbiorcy", type="string", example="Jan Kowalski", description="Nazwa odbiorcy"),
     *     @OA\Property(property="tytul_przelewu", type="string", example="Czynsz za mieszkanie", description="Tytuł przelewu"),
     *     @OA\Property(property="kwota", type="number", format="float", example=550.00, description="Kwota przelewu"),
     *     @OA\Property(property="czestotliwosc", type="string", example="miesiecznie", enum={"codziennie", "tygodniowo", "miesiecznie", "rocznie"}, description="Częstotliwość wykonywania"),
     *     @OA\Property(property="data_startu", type="string", format="date", example="2024-07-01", description="Data rozpoczęcia zlecenia (YYYY-MM-DD)"),
     *     @OA\Property(property="data_zakonczenia", type="string", format="date", nullable=true, example="2026-06-01", description="Opcjonalna data zakończenia zlecenia (YYYY-MM-DD, późniejsza niż data startu)"),
     *     @OA\Property(property="aktywne", type="boolean", example=false, description="Czy zlecenie ma być aktywne")
     * )
     */
    public function authorize(): bool
    {
        $zlecenie = $this->route('zlecenie_stale'); // Pobieranie modelu z route model binding
        return $zlecenie && $zlecenie->id_uzytkownika === Auth::id();
    }

    public function rules(): array
    {
        return [
            // Nie pozwalamy na zmianę konta źródłowego ani użytkownika w update
            'nr_konta_docelowego' => ['sometimes', 'required', 'string', 'regex:/^[A-Z]{2}[0-9]{2}[A-Z0-9]{1,30}$/', 'max:34'],
            'nazwa_odbiorcy' => 'sometimes|required|string|max:255',
            'tytul_przelewu' => 'sometimes|required|string|max:255',
            'kwota' => 'sometimes|required|numeric|min:0.01|max:9999999.99',
            'czestotliwosc' => ['sometimes', 'required', 'string', Rule::in(ZlecenieStale::$dostepneCzestotliwosci)],
            'data_startu' => 'required|date_format:Y-m-d|after_or_equal:today',
            'data_zakonczenia' => 'nullable|date_format:Y-m-d|after:data_startu',
            'aktywne' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nr_konta_docelowego.regex' => 'Format numeru konta docelowego jest nieprawidłowy.',
            'data_zakonczenia.after' => 'Data zakończenia musi być późniejsza niż data startu.',
        ];
    }
}
