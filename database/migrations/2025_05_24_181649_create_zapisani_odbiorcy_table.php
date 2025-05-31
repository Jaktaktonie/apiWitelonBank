<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_zapisani_odbiorcy_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zapisani_odbiorcy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_uzytkownika')->constrained('uzytkownicy')->onDelete('cascade'); // Kto zapisał tego odbiorcę
            $table->string('nazwa_odbiorcy_zdefiniowana'); // Nazwa nadana przez użytkownika, np. "Czynsz", "Mama"
            $table->string('nr_konta_odbiorcy'); // Numer konta zapisywanego odbiorcy
            $table->string('rzeczywista_nazwa_odbiorcy')->nullable(); // Opcjonalnie, jeśli chcemy przechowywać rzeczywistą nazwę (np. z danych przelewu)
            $table->string('adres_odbiorcy_linia1')->nullable(); // Opcjonalnie
            $table->string('adres_odbiorcy_linia2')->nullable(); // Opcjonalnie
            $table->timestamps();

            // Unikalność: użytkownik nie powinien móc dodać tego samego numeru konta pod tą samą zdefiniowaną nazwą
            // lub nawet ten sam numer konta wielokrotnie dla siebie, to zależy od logiki biznesowej
            $table->unique(['id_uzytkownika', 'nr_konta_odbiorcy', 'nazwa_odbiorcy_zdefiniowana'], 'uzytk_nrkonta_nazwa_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zapisani_odbiorcy');
    }
};
