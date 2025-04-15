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
        Schema::create('portfele', function (Blueprint $table) {
            $table->id(); // INT, PK, AUTO_INCREMENT
            // Zakładam, że użytkownik ma jeden portfel, stąd unique()
            $table->foreignId('id_uzytkownika')->unique()->constrained('uzytkownicy')->onDelete('cascade'); // INT, FK -> uzytkownicy.id
            $table->decimal('saldo_bitcoin', 18, 8)->default(0.00000000); // Większa precyzja dla krypto
            $table->decimal('saldo_ethereum', 18, 8)->default(0.00000000); // Większa precyzja dla krypto
            $table->timestamps(); // Dodaje created_at i updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portfele');
    }
};
