<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="Karta",
 *     title="Karta Płatnicza",
 *     description="Model karty płatniczej",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID karty"
 *     ),
 *     @OA\Property(
 *         property="id_konta",
 *         type="integer",
 *         format="int64",
 *         description="ID konta, do którego przypisana jest karta"
 *     ),
 *     @OA\Property(
 *         property="nr_karty",
 *         type="string",
 *         description="Unikalny numer karty (częściowo zamaskowany dla bezpieczeństwa w odpowiedziach)"
 *     ),
 *     @OA\Property(
 *         property="data_waznosci",
 *         type="string",
 *         format="date",
 *         description="Data ważności karty (YYYY-MM-DD)"
 *     ),
 *     @OA\Property(
 *         property="zablokowana",
 *         type="boolean",
 *         description="Czy karta jest zablokowana"
 *     ),
 *     @OA\Property(
 *         property="limit_dzienny",
 *         type="number",
 *         format="float",
 *         nullable=true,
 *         description="Opcjonalny dzienny limit transakcji dla karty"
 *     ),
 *     @OA\Property(
 *         property="typ_karty",
 *         type="string",
 *         nullable=true,
 *         description="Typ karty (np. Visa Debit, Mastercard Credit)"
 *     ),
 *      @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Data utworzenia"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Data ostatniej modyfikacji"
 *     )
 * )
 */
class Karta extends Model
{
    use HasFactory;

    protected $table = 'karty'; // Nazwa tabeli w bazie danych

    protected $fillable = [
        'id_konta',
        'nr_karty',         // Pamiętaj, że w migracji jest unikalny. Generowany przez system.
        'cvc_hash',         // Hash CVC, nie powinien być nigdy zwracany w API.
        'data_waznosci',
        'zablokowana',
        'limit_dzienny',
        'typ_karty',
        // płatności zbliżeniowe/internetowe - jeśli są w migracji i potrzebne do zarządzania
        'platnosci_internetowe_aktywne', // Dodane na podstawie migracji
        'platnosci_zblizeniowe_aktywne', // Dodane na podstawie migracji
    ];

    protected $hidden = [
        'cvc_hash', // Ukryj CVC hash
    ];

    protected $casts = [
        'data_waznosci' => 'date:Y-m-d',
        'zablokowana' => 'boolean',
        'limit_dzienny' => 'decimal:2',
        'platnosci_internetowe_aktywne' => 'boolean',
        'platnosci_zblizeniowe_aktywne' => 'boolean',
    ];

    /**
     * Relacja: Karta należy do Konta.
     */
    public function konto(): BelongsTo
    {
        return $this->belongsTo(Konto::class, 'id_konta');
    }

    // Możesz dodać metody pomocnicze, np.:
    public function zablokuj(): bool
    {
        return $this->update(['zablokowana' => true]);
    }

    public function odblokuj(): bool
    {
        return $this->update(['zablokowana' => false]);
    }

    public function zmienLimit(float $nowyLimit): bool
    {
        return $this->update(['limit_dzienny' => $nowyLimit]);
    }
}
