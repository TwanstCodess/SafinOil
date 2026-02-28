<?php
// database/migrations/2024_01_01_000016_add_transaction_number_to_credit_payments.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('credit_payments', function (Blueprint $table) {
            $table->string('transaction_number')->nullable()->unique()->after('id');
            $table->index('transaction_number');
        });
    }

    public function down(): void
    {
        Schema::table('credit_payments', function (Blueprint $table) {
            $table->dropColumn('transaction_number');
            $table->dropIndex(['transaction_number']);
        });
    }
};
