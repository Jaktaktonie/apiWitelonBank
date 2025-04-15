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
            $table->id(); // INT, PK, AUTO_INCREMENT
            $table->foreignId('id_uzytkownika')->constrained('uzytkownicy')->onDelete('cascade'); // Kto utworzył
            // Dodałem konto źródłowe, bo zlecenie musi z jakiegoś wyjść
            $table->foreignId('id_konta_zrodlowego')->constrained('konta')->onDelete('cascade'); // Z jakiego konta
            $table->string('nr_konta_docelowego');
            $table->enum('czestotliwosc', ['dziennie', 'tygodniowo', 'miesiecznie', 'rocznie']); // Dodano 'dziennie', 'rocznie'
            $table->date('data_startu');
            $table->decimal('kwota', 15, 2);
            $table->boolean('aktywne')->default(true); // Opcjonalnie: status zlecenia
            $table->date('ostatnie_wykonanie')->nullable(); // Opcjonalnie: data ostatniego wykonania
            $table->timestamps(); // Dodaje created_at i updated_at
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
