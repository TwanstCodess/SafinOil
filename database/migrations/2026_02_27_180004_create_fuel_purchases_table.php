<?php
// database/migrations/2024_01_01_000003_create_fuel_purchases_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fuel_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained();
            $table->decimal('liters', 10, 2);
            $table->decimal('price_per_liter', 10, 2);
            $table->decimal('total_price', 15, 2);
            $table->date('purchase_date');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('fuel_purchases');
    }
};
