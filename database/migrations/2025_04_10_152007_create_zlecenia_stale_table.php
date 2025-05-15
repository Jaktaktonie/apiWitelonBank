<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('zlecenia_stale', function (Blueprint $table) {
            $table->id(); // Klucz główny

            $table->foreignId('id_uzytkownika')
                ->constrained('uzytkownicy') // Klucz obcy do tabeli użytkowników
                ->onDelete('cascade');

            $table->foreignId('id_konta_zrodlowego')
                ->constrained('konta') // Klucz obcy do tabeli kont (konto, z którego idą środki)
                ->onDelete('cascade');

            $table->string('nr_konta_docelowego'); // Numer konta, na które mają być wysyłane środki
            $table->string('nazwa_odbiorcy');       // <--- BRAKUJĄCA KOLUMNA
            $table->string('tytul_przelewu');

            $table->decimal('kwota', 15, 2); // Kwota przelewu

            $table->string('czestotliwosc'); // Np. 'codziennie', 'tygodniowo', 'miesiecznie', 'rocznie'
            // Można też rozważyć enum, jeśli baza danych go wspiera i chcesz ograniczyć wartości

            $table->date('data_startu'); // Data, od której zlecenie ma być aktywne
            $table->date('data_nastepnego_wykonania')->nullable(); // Data następnego planowanego przelewu (może być null, jeśli np. ostatnie wykonanie)
            $table->date('data_zakonczenia')->nullable(); // Opcjonalna data, do kiedy zlecenie ma być aktywne

            $table->boolean('aktywne')->default(true); // Czy zlecenie jest obecnie aktywne

            // Możesz dodać licznik wykonanych przelewów lub datę ostatniego wykonania
            // $table->integer('liczba_wykonanych')->default(0);
            // $table->timestamp('ostatnio_wykonano_o')->nullable();

            $table->timestamps(); // created_at i updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zlecenia_stale');
    }
};
