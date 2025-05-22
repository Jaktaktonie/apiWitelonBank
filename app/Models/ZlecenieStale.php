<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="ZlecenieStale",
 *     title="Zlecenie Stałe",
 *     description="Model zlecenia stałego (płatności cyklicznej)",
 *     @OA\Property(property="id", type="integer", format="int64", description="ID zlecenia"),
 *     @OA\Property(property="id_uzytkownika", type="integer", format="int64", description="ID użytkownika, który utworzył zlecenie"),
 *     @OA\Property(property="id_konta_zrodlowego", type="integer", format="int64", description="ID konta źródłowego"),
 *     @OA\Property(property="nr_konta_docelowego", type="string", description="Numer konta docelowego"),
 *     @OA\Property(property="nazwa_odbiorcy", type="string", description="Nazwa odbiorcy przelewu"),
 *     @OA\Property(property="tytul_przelewu", type="string", description="Tytuł przelewu"),
 *     @OA\Property(property="kwota", type="number", format="float", description="Kwota przelewu"),
 *     @OA\Property(property="czestotliwosc", type="string", description="Częstotliwość wykonywania (np. 'miesiecznie', 'tygodniowo')"),
 *     @OA\Property(property="data_startu", type="string", format="date", description="Data rozpoczęcia zlecenia"),
 *     @OA\Property(property="data_nastepnego_wykonania", type="string", format="date", nullable=true, description="Data następnego planowanego wykonania"),
 *     @OA\Property(property="data_zakonczenia", type="string", format="date", nullable=true, description="Data zakończenia zlecenia (opcjonalnie)"),
 *     @OA\Property(property="aktywne", type="boolean", description="Czy zlecenie jest aktywne"),
 *     @OA\Property(property="created_at", type="string", format="date-time", description="Data utworzenia"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", description="Data ostatniej modyfikacji")
 * )
 */
class ZlecenieStale extends Model
{
    use HasFactory;

    protected $table = 'zlecenia_stale';

    protected $fillable = [
        'id_uzytkownika',
        'id_konta_zrodlowego',
        'nr_konta_docelowego',
        'nazwa_odbiorcy',
        'tytul_przelewu',
        'kwota',
        'czestotliwosc',
        'data_startu',
        'data_nastepnego_wykonania',
        'data_zakonczenia',
        'aktywne',
    ];

    protected $casts = [
        'kwota' => 'decimal:2',
        'data_startu' => 'date:Y-m-d',
        'data_nastepnego_wykonania' => 'date:Y-m-d',
        'data_zakonczenia' => 'date:Y-m-d',
        'aktywne' => 'boolean',
    ];

    public const CZESTOTLIWOSC_CODZIENNIE = 'codziennie';
    public const CZESTOTLIWOSC_TYGODNIOWO = 'tygodniowo';
    public const CZESTOTLIWOSC_MIESIECZNIE = 'miesiecznie';
    public const CZESTOTLIWOSC_ROCZNIE = 'rocznie';

    public static array $dostepneCzestotliwosci = [
        self::CZESTOTLIWOSC_CODZIENNIE,
        self::CZESTOTLIWOSC_TYGODNIOWO,
        self::CZESTOTLIWOSC_MIESIECZNIE,
        self::CZESTOTLIWOSC_ROCZNIE,
    ];

    /**
     * Relacja: Zlecenie należy do Użytkownika.
     */
    public function uzytkownik(): BelongsTo
    {
        return $this->belongsTo(Uzytkownik::class, 'id_uzytkownika');
    }

    /**
     * Relacja: Zlecenie jest powiązane z Kontem źródłowym.
     */
    public function kontoZrodlowe(): BelongsTo
    {
        return $this->belongsTo(Konto::class, 'id_konta_zrodlowego');
    }

    // Metoda pomocnicza do obliczania następnej daty wykonania
    // To jest uproszczona logika, produkcyjna powinna być bardziej rozbudowana
    // i uwzględniać np. dni wolne, koniec miesiąca itp.
    public function obliczNastepneWykonanie(?string $odDaty = null): ?\Illuminate\Support\Carbon
    {
        if (!$this->aktywne) {
            return null;
        }

        $start = $odDaty ? \Illuminate\Support\Carbon::parse($odDaty) : \Illuminate\Support\Carbon::parse($this->data_nastepnego_wykonania ?? $this->data_startu);

        // Jeśli data_nastepnego_wykonania jest już ustawiona i jest w przyszłości, użyj jej
        // lub jeśli jest to data_startu to też.
        if ($start->isFuture() || $start->isToday()) {
            // Jeśli data startu jest dzisiaj lub w przyszłości, a nie ma daty następnego wykonania, to data startu jest pierwszą datą wykonania.
            if (is_null($this->data_nastepnego_wykonania) && (\Illuminate\Support\Carbon::parse($this->data_startu)->isFuture() || \Illuminate\Support\Carbon::parse($this->data_startu)->isToday())) {
                return \Illuminate\Support\Carbon::parse($this->data_startu);
            }
            // Jeśli data nastepnego wykonania już minęła, obliczamy nową
            if ($start->isPast() && !is_null($this->data_nastepnego_wykonania)){
                // celowo przechodzimy dalej do obliczenia nowej daty
            } else if (!is_null($this->data_nastepnego_wykonania)) {
                return $start;
            }
        }


        $nastepnaData = clone $start; // Klonujemy, aby nie modyfikować oryginalnej daty startowej lub ostatniego wykonania

        // Jeśli data startowa jest w przyszłości, to ona jest następną datą wykonania
        $dataStartuZlecenia = \Illuminate\Support\Carbon::parse($this->data_startu);
        if ($dataStartuZlecenia->isFuture()) {
            $nastepnaData = $dataStartuZlecenia;
        } else {
            // Jeśli data startowa minęła, obliczamy na podstawie częstotliwości
            // od ostatniej daty_nastepnego_wykonania lub data_startu
            $bazowaData = $this->data_nastepnego_wykonania ? \Illuminate\Support\Carbon::parse($this->data_nastepnego_wykonania) : $dataStartuZlecenia;

            // Pętla, aby upewnić się, że następna data jest w przyszłości
            do {
                switch ($this->czestotliwosc) {
                    case self::CZESTOTLIWOSC_CODZIENNIE:
                        $bazowaData->addDay();
                        break;
                    case self::CZESTOTLIWOSC_TYGODNIOWO:
                        $bazowaData->addWeek();
                        break;
                    case self::CZESTOTLIWOSC_MIESIECZNIE:
                        $bazowaData->addMonthNoOverflow(); // Unika przeskakiwania miesięcy przy krótkich miesiącach
                        break;
                    case self::CZESTOTLIWOSC_ROCZNIE:
                        $bazowaData->addYearNoOverflow();
                        break;
                    default:
                        return null; // Nieznana częstotliwość
                }
            } while ($bazowaData->isPast());
            $nastepnaData = $bazowaData;
        }


        if ($this->data_zakonczenia && $nastepnaData->gt(\Illuminate\Support\Carbon::parse($this->data_zakonczenia))) {
            return null; // Przekroczono datę zakończenia
        }

        return $nastepnaData;
    }
}
