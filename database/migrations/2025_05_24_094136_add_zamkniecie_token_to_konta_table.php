<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_zamkniecie_token_to_konta_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('konta', function (Blueprint $table) {
            $table->string('token_zamkniecia')->nullable()->unique()->after('waluta'); // Token do potwierdzenia zamknięcia
            $table->timestamp('token_zamkniecia_wygasa_o')->nullable()->after('token_zamkniecia'); // Data ważności tokenu
            // Możesz też dodać flagę, że konto jest w procesie zamykania
            // $table->boolean('oczekuje_na_zamkniecie')->default(false)->after('zablokowane');
        });
    }

    public function down(): void
    {
        Schema::table('konta', function (Blueprint $table) {
            $table->dropColumn(['token_zamkniecia', 'token_zamkniecia_wygasa_o'/*, 'oczekuje_na_zamkniecie'*/]);
        });
    }
};
