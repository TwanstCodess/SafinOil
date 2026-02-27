<?php
// database/migrations/2024_01_01_000007_create_penalties_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('penalties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained();
            $table->decimal('amount', 10, 2);
            $table->date('penalty_date');
            $table->string('reason');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('penalties');
    }
};
