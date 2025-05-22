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
        Schema::create('karty', function (Blueprint $table) {
            $table->id(); // Klucz główny

            $table->foreignId('id_konta')
                ->constrained('konta') // Klucz obcy wskazujący na tabelę 'konta'
                ->onDelete('cascade'); // Jeśli konto zostanie usunięte, karty powiązane też

            $table->string('nr_karty')->unique(); // Unikalny numer karty
            $table->string('cvc_hash');          // Zahashowany kod CVC/CVV
            $table->date('data_waznosci');       // Data ważności karty (tylko rok i miesiąc są istotne, ale date jest ok)

            $table->boolean('zablokowana')->default(false); // Czy karta jest zablokowana
            $table->boolean('platnosci_internetowe_aktywne')->default(false); // Czy karta jest zablokowana
            $table->boolean('platnosci_zblizeniowe_aktywne')->default(false); // Czy karta jest zablokowana

            $table->decimal('limit_dzienny', 15, 2)->nullable(); // Opcjonalny dzienny limit transakcji
            $table->string('typ_karty', 50)->nullable(); // Np. 'Visa Debit', 'Mastercard Credit', opcjonalne

            // Możesz dodać więcej pól specyficznych dla kart, np.:
            // $table->string('pin_hash')->nullable(); // Jeśli przechowujesz zahashowany PIN (ostrożnie z tym)
            // $table->boolean('platnosci_internetowe_aktywne')->default(true);
            // $table->boolean('platnosci_zblizeniowe_aktywne')->default(true);

            $table->timestamps(); // created_at i updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('karty');
    }
};
