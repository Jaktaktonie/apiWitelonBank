<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Konto extends Model
{
    protected $table = 'konta'; // <--- DODAJ TĘ LINIĘ
    public function przelewyWychodzace()
    {
        return $this->hasMany(Przelew::class, 'id_konta_nadawcy');
    }

// Opcjonalnie, jeśli chcesz łatwo znaleźć przelewy przychodzące na Twoje konta w systemie
// To jest bardziej złożone, bo przelewy są identyfikowane przez nr_konta_odbiorcy (string)
// Można by stworzyć metodę, która szuka przelewów po numerach kont użytkownika
    public function getPrzelewyPrzychodzaceAttribute()
    {
        return Przelew::where('nr_konta_odbiorcy', $this->nr_konta)->get();
    }
    protected $fillable = [
        'id_uzytkownika',
        'nr_konta',
        'saldo',
        'limit_przelewu', // Dodaj, jeśli brakuje
        'zablokowane',    // Dodaj, jeśli brakuje
        'waluta',
    ];

    protected $casts = [
        'saldo' => 'decimal:2',
        'limit_przelewu' => 'decimal:2', // Ważne!
        'zablokowane' => 'boolean',    // Ważne!
    ];

    public function uzytkownik()
    {
        return $this->belongsTo(Uzytkownik::class, 'id_uzytkownika');
    }
    public function karty()
    {
        return $this->hasMany(Karta::class, 'id_konta');
    }
    public function zleceniaStaleZrodlowe(): HasMany
    {
        return $this->hasMany(ZlecenieStale::class, 'id_konta_zrodlowego');
    }

}
