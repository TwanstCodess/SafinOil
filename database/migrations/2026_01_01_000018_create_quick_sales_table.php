<?php
// database/migrations/2024_01_01_000018_create_quick_sales_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('quick_sales', function (Blueprint $table) {
            $table->id();
            $table->date('sale_date')->unique();
            $table->enum('status', ['open', 'closed'])->default('open');

            // JSON بۆ هەڵگرتنی داتای کاتیگۆریەکان
            $table->json('categories_data')->nullable();

            // داتای سەرەتایی
            $table->json('initial_readings')->nullable();

            // داتای کۆتایی
            $table->json('final_readings')->nullable();

            // فرۆشراوەکان
            $table->json('sold_data')->nullable();

            // جیاوازیەکان
            $table->json('differences')->nullable();

            // کۆی گشتی
            $table->decimal('total_amount', 15, 2)->default(0);

            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('quick_sales');
    }
};
