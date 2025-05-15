<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Portfel extends Model
{
    use HasFactory;

    protected $table = 'portfele'; // Upewnij się, że nazwa tabeli jest poprawna

    protected $fillable = [
        'id_uzytkownika',
        'saldo_bitcoin',
        'saldo_ethereum',
        // Możesz dodać inne kryptowaluty w przyszłości
        // 'saldo_cardano',
        // 'saldo_solana',
    ];

    protected $casts = [
        'saldo_bitcoin' => 'decimal:8', // Precyzja dla kryptowalut, np. 8 miejsc po przecinku
        'saldo_ethereum' => 'decimal:8',
        // 'saldo_cardano' => 'decimal:8',
        // 'saldo_solana' => 'decimal:8',
    ];

    /**
     * Relacja do użytkownika.
     */
    public function uzytkownik()
    {
        return $this->belongsTo(Uzytkownik::class, 'id_uzytkownika');
    }
}
