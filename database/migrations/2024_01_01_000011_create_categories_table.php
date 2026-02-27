<?php
// database/migrations/2024_01_01_000001_create_categories_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('type_id')->constrained('types')->onDelete('restrict');
            $table->decimal('current_price', 10, 2);
            $table->decimal('purchase_price', 10, 2);
            $table->integer('stock_liters')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('categories');
    }
};
