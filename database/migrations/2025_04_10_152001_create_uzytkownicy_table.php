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
        Schema::create('uzytkownicy', function (Blueprint $table) {
            $table->id(); // INT, PK, AUTO_INCREMENT
            $table->string('imie');
            $table->string('nazwisko');
            $table->string('email')->unique();
            $table->string('telefon')->nullable(); // Zakładam, że telefon może być opcjonalny
            $table->boolean('weryfikacja')->default(false);
            $table->boolean('administrator')->default(false);
            $table->string('haslo_hash'); // W Laravel często nazywa się to 'password'
            $table->rememberToken(); // Opcjonalnie, standardowa kolumna Laravel
            $table->timestamps(); // Dodaje created_at i updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uzytkownicy');
    }
};
