<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Uzytkownik extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'uzytkownicy';

    protected $fillable = [
        'imie',
        'nazwisko',
        'email',
        'telefon',
        'haslo_hash',
        'dwuetapowy_kod', // Dodane
        'dwuetapowy_kod_wygasa_o', // Dodane
        // 'weryfikacja' i 'administrator' mogą być zarządzane inaczej,
        // np. przez bezpośrednie przypisanie lub metody w modelu
    ];

    protected $hidden = [
        'haslo_hash',
        'remember_token',
        'dwuetapowy_kod', // Ukryj kod 2FA z odpowiedzi API
    ];

    protected $casts = [
        'weryfikacja' => 'boolean',
        'administrator' => 'boolean',
        'dwuetapowy_kod_wygasa_o' => 'datetime', // Dodane
    ];

    public function getAuthPassword()
    {
        return $this->haslo_hash;
    }

    /**
     * Generuje i zapisuje kod 2FA dla użytkownika.
     */
    public function generujKodDwuetapowy(): void
    {
        $this->timestamps = false; // Tymczasowo wyłącz timestamps, aby nie aktualizować updated_at
        $this->dwuetapowy_kod = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT); // 6-cyfrowy kod
        $this->dwuetapowy_kod_wygasa_o = now()->addMinutes(10); // Kod ważny przez 10 minut
        $this->save();
        $this->timestamps = true; // Włącz z powrotem timestamps
    }

    /**
     * Resetuje kod 2FA dla użytkownika.
     */
    public function resetujKodDwuetapowy(): void
    {
        $this->timestamps = false;
        $this->dwuetapowy_kod = null;
        $this->dwuetapowy_kod_wygasa_o = null;
        $this->save();
        $this->timestamps = true;
    }
    public function konta()
    {
        return $this->hasMany(Konto::class, 'id_uzytkownika'); // 'id_uzytkownika' to klucz obcy w tabeli 'konta'
    }
}
