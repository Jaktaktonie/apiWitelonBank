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
        Schema::create('sesje', function (Blueprint $table) {
            $table->id(); // INT, PK, AUTO_INCREMENT
            $table->foreignId('id_uzytkownika')->constrained('uzytkownicy')->onDelete('cascade'); // INT, FK -> uzytkownicy.id
            $table->string('token', 100)->unique(); // Długość tokena można dostosować
            $table->timestamp('data_wygasniecia')->nullable(); // DATETIME, używam timestamp dla lepszej obsługi stref czasowych
            $table->timestamps(); // Dodaje created_at i updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sesje');
    }
};
