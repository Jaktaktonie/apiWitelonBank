<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @OA\Schema(
 *     schema="ZapisanyOdbiorca",
 *     title="Zapisany Odbiorca",
 *     @OA\Property(property="id", type="integer"),
 *     @OA\Property(property="id_uzytkownika", type="integer"),
 *     @OA\Property(property="nazwa_odbiorcy_zdefiniowana", type="string", description="Nazwa odbiorcy nadana przez użytkownika"),
 *     @OA\Property(property="nr_konta_odbiorcy", type="string", description="Numer konta odbiorcy"),
 *     @OA\Property(property="rzeczywista_nazwa_odbiorcy", type="string", nullable=true, description="Rzeczywista nazwa odbiorcy (jeśli znana)"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class ZapisanyOdbiorca extends Model
{
    use HasFactory;

    protected $table = 'zapisani_odbiorcy';
    protected $fillable = [
        'id_uzytkownika',
        'nazwa_odbiorcy_zdefiniowana',
        'nr_konta_odbiorcy',
        'rzeczywista_nazwa_odbiorcy',
        'adres_odbiorcy_linia1',
        'adres_odbiorcy_linia2',
    ];

    public function uzytkownik(): BelongsTo
    {
        return $this->belongsTo(Uzytkownik::class, 'id_uzytkownika');
    }
}
