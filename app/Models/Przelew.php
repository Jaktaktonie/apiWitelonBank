<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Przelew extends Model
{
    use HasFactory;

    protected $table = 'przelewy'; // Upewnij się, że jest to poprawne
    protected $casts = [
        'kwota' => 'decimal:2', // Dokładniejsze dla pieniędzy
        'data_zlecenia' => 'datetime',
        'data_realizacji' => 'datetime',
    ];

    protected $fillable = [
        'id_konta_nadawcy',
        'nr_konta_odbiorcy',
        'nazwa_odbiorcy',
        'nazwa_nadawcy',
        'adres_odbiorcy_linia1',
        'adres_odbiorcy_linia2',
        'tytul',
        'kwota',
        'waluta_przelewu',
        'status',
        'data_zlecenia',
        'data_realizacji',
        'informacja_zwrotna',
    ];

    public function kontoNadawcy()
    {
        return $this->belongsTo(Konto::class, 'id_konta_nadawcy');
    }

    public function kontoOdbiorcy()
    {

    }
}
