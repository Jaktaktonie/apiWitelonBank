<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
    public function uzytkownik()
    {
        return $this->belongsTo(Uzytkownik::class, 'id_uzytkownika');
    }
}
