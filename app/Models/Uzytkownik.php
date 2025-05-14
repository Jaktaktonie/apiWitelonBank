<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Uzytkownik extends Authenticatable
{
    use HasFactory;

    protected $table = 'uzytkownicy';

    protected $fillable = [
        'imie',
        'nazwisko',
        'email',
        'telefon',
        'weryfikacja',
        'administrator',
        'haslo_hash',
    ];

    protected $hidden = [
        'haslo_hash',
        'remember_token',
    ];

    public $timestamps = true;
}
