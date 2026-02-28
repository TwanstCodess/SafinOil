<?php
// database/migrations/2024_01_01_000002_create_cash_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cash', function (Blueprint $table) {
            $table->id();
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('total_income', 15, 2)->default(0);
            $table->decimal('total_expense', 15, 2)->default(0);
            $table->decimal('capital', 15, 2)->default(0)->after('total_expense');
            $table->decimal('profit', 15, 2)->default(0)->after('capital');
            $table->date('last_update');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('cash');
    }
};
