<?php

namespace App\Http\Requests;

use App\Models\Konto;
use App\Models\ZlecenieStale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

class StoreZlecenieStaleRequest extends FormRequest
{
    /**
     * @OA\Schema(
     *     schema="StoreZlecenieStaleRequest",
     *     title="Store Zlecenie Stałe Request",
     *     required={"id_konta_zrodlowego", "nr_konta_docelowego", "nazwa_odbiorcy", "tytul_przelewu", "kwota", "czestotliwosc", "data_startu"},
     *     @OA\Property(property="id_konta_zrodlowego", type="integer", example=1, description="ID konta źródłowego należącego do użytkownika"),
     *     @OA\Property(property="nr_konta_docelowego", type="string", example="PL12345678901234567890123456", description="Numer konta docelowego (IBAN)"),
     *     @OA\Property(property="nazwa_odbiorcy", type="string", example="Jan Kowalski", description="Nazwa odbiorcy"),
     *     @OA\Property(property="tytul_przelewu", type="string", example="Czynsz za mieszkanie", description="Tytuł przelewu"),
     *     @OA\Property(property="kwota", type="number", format="float", example=500.50, description="Kwota przelewu"),
     *     @OA\Property(property="czestotliwosc", type="string", example="miesiecznie", enum={"codziennie", "tygodniowo", "miesiecznie", "rocznie"}, description="Częstotliwość wykonywania"),
     *     @OA\Property(property="data_startu", type="string", format="date", example="2024-06-01", description="Data rozpoczęcia zlecenia (YYYY-MM-DD, nie wcześniejsza niż dzisiaj)"),
     *     @OA\Property(property="data_zakonczenia", type="string", format="date", nullable=true, example="2025-06-01", description="Opcjonalna data zakończenia zlecenia (YYYY-MM-DD, późniejsza niż data startu)"),
     *     @OA\Property(property="aktywne", type="boolean", example=true, description="Czy zlecenie ma być aktywne (domyślnie true)")
     * )
     */
    public function authorize(): bool
    {
        // Użytkownik musi być właścicielem konta źródłowego
        $kontoZrodlowe = Konto::find($this->input('id_konta_zrodlowego'));
        return $kontoZrodlowe && $kontoZrodlowe->id_uzytkownika === Auth::id();
    }

    public function rules(): array
    {
        return [
            'id_konta_zrodlowego' => [
                'required',
                'integer',
                Rule::exists('konta', 'id')->where(function ($query) {
                    $query->where('id_uzytkownika', Auth::id());
                }),
            ],
            'nr_konta_docelowego' => ['required', 'string', 'regex:/^[A-Z]{2}[0-9]{2}[A-Z0-9]{1,30}$/', 'max:34'],
            'nazwa_odbiorcy' => 'required|string|max:255',
            'tytul_przelewu' => 'required|string|max:255',
            'kwota' => 'required|numeric|min:0.01|max:9999999.99', // Dostosuj max
            'czestotliwosc' => ['required', 'string', Rule::in(ZlecenieStale::$dostepneCzestotliwosci)],
            'data_startu' => 'required|date_format:Y-m-d|after_or_equal:today',
            'data_zakonczenia' => 'nullable|date_format:Y-m-d|after:data_startu',
            'aktywne' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'id_konta_zrodlowego.exists' => 'Wybrane konto źródłowe nie istnieje lub nie należy do Ciebie.',
            'nr_konta_docelowego.regex' => 'Format numeru konta docelowego jest nieprawidłowy.',
            'data_startu.after_or_equal' => 'Data startu nie może być wcześniejsza niż dzisiaj.',
            'data_zakonczenia.after' => 'Data zakończenia musi być późniejsza niż data startu.',
        ];
    }

    protected function passedValidation()
    {
        $this->merge([
            'id_uzytkownika' => Auth::id(),
            // Domyślnie aktywne, chyba że przekazano inaczej
            'aktywne' => $this->input('aktywne', true),
        ]);
    }
}
