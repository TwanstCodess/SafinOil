<?php
// database/migrations/2024_01_01_000006_create_salaries_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained();
            $table->decimal('amount', 10, 2);
            $table->decimal('deductions', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2);
            $table->date('payment_date');
            $table->string('month');
            $table->year('year');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('salaries');
    }
};
