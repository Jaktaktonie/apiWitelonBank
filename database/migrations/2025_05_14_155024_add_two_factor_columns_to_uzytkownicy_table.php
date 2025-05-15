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
        Schema::table('uzytkownicy', function (Blueprint $table) {
            $table->string('dwuetapowy_kod')->nullable()->after('haslo_hash');
            $table->timestamp('dwuetapowy_kod_wygasa_o')->nullable()->after('dwuetapowy_kod');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('uzytkownicy', function (Blueprint $table) {
            $table->dropColumn(['dwuetapowy_kod', 'dwuetapowy_kod_wygasa_o']);
        });
    }
};
