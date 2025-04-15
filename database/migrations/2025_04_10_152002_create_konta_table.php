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
            $table->id(); // INT, PK, AUTO_INCREMENT
            $table->string('nr_konta')->unique();
            $table->foreignId('id_uzytkownika')->constrained('uzytkownicy')->onDelete('cascade'); // INT, FK -> uzytkownicy.id
            $table->decimal('saldo_pln', 15, 2)->default(0.00); // DECIMAL(precyzja, skala)
            $table->boolean('zablokowane')->default(false);
            $table->decimal('limit_przelewu', 15, 2)->nullable(); // Zakładam, że limit może być opcjonalny
            $table->timestamps(); // Dodaje created_at i updated_at
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
