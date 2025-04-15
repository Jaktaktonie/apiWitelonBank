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
        Schema::create('przelewy', function (Blueprint $table) {
            $table->id(); // INT, PK, AUTO_INCREMENT
            $table->foreignId('id_konta_nadawcy')->constrained('konta')->onDelete('cascade'); // FK -> konta.id
            $table->string('nr_konta_docelowego');
            $table->timestamp('data_przelewu')->useCurrent(); // DATETIME, używam timestamp, domyślnie czas wykonania migracji
            $table->decimal('kwota', 15, 2);
            $table->string('tytul')->nullable(); // Opcjonalnie: Tytuł przelewu
            $table->string('nazwa_odbiorcy')->nullable(); // Opcjonalnie: Nazwa odbiorcy
            $table->enum('status', ['oczekujacy', 'zrealizowany', 'odrzucony'])->default('oczekujacy'); // Opcjonalnie: status przelewu
            $table->timestamps(); // Dodaje created_at i updated_at (created_at będzie zbliżone do data_przelewu)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('przelewy');
    }
};
