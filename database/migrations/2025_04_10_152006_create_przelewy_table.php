<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_przelewy_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('przelewy', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_konta_nadawcy')->constrained('konta')->onDelete('cascade');
            $table->string('nr_konta_odbiorcy'); // Numer konta odbiorcy jako string
            $table->string('nazwa_odbiorcy')->nullable();
            $table->string('adres_odbiorcy_linia1')->nullable();
            $table->string('adres_odbiorcy_linia2')->nullable();
            $table->string('tytul')->nullable();
            $table->decimal('kwota', 15, 2); // Dla pieniędzy
            $table->string('waluta_przelewu')->default('PLN');
            $table->enum('status', ['oczekujacy', 'zrealizowany', 'odrzucony'])->default('oczekujacy');
            $table->timestamp('data_zlecenia')->useCurrent();
            $table->timestamp('data_realizacji')->nullable();
            $table->text('informacja_zwrotna')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('przelewy');
    }
};
