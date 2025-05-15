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
        Schema::create('konta', function (Blueprint $table) {
            $table->id(); // Domyślnie: BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY

            $table->foreignId('id_uzytkownika')
                ->constrained('uzytkownicy') // Zakłada, że tabela użytkowników nazywa się 'uzytkownicy'
                ->onDelete('cascade'); // Co zrobić, gdy użytkownik zostanie usunięty

            $table->string('nr_konta')->unique(); // Numer konta, powinien być unikalny

            $table->decimal('saldo', 15, 2)->default(0.00); // Saldo, np. do 15 cyfr łącznie, 2 po przecinku

            $table->decimal('limit_przelewu', 15, 2)->nullable(); // Opcjonalny limit przelewu

            $table->boolean('zablokowane')->default(false); // Czy konto jest zablokowane

            $table->string('waluta', 3)->default('PLN'); // Kolumna na walutę, np. 'PLN', 'EUR', 'USD'

            $table->timestamps(); // Tworzy kolumny `created_at` i `updated_at`
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('konta');
    }
};
