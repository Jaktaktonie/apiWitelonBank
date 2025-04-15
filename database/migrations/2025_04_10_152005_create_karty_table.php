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
            $table->id(); // INT, PK, AUTO_INCREMENT
            $table->foreignId('id_konta')->constrained('konta')->onDelete('cascade'); // INT, FK -> konta.id
            $table->string('nr_karty')->unique(); // Numer karty powinien być unikalny
            $table->string('cvc_hash');
            $table->date('data_waznosci');
            $table->boolean('zablokowane')->default(false);
            $table->decimal('limit_dzienny', 15, 2)->default(0.00); // DECIMAL(precyzja, skala)
            $table->timestamps(); // Dodaje created_at i updated_at
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
