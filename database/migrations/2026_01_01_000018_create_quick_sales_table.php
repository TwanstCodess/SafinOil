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

            $table->date('sale_date');
            // ✅ هەر ڕۆژ دەتوانێت ٢ ریکۆرد هەبێت: بەیانی + ئێوارە
            $table->enum('shift', ['morning', 'evening'])->default('morning');
            $table->unique(['sale_date', 'shift']);

            $table->enum('status', ['open', 'closed'])->default('open');

            $table->json('categories_data')->nullable();
            $table->json('initial_readings')->nullable();
            $table->json('final_readings')->nullable();

            // فرۆشراوی حسابکراو لە readings
            $table->json('sold_data')->nullable();

            // فرۆشراوی تۆمارکراو لە کارمەند
            $table->json('reported_sold')->nullable();

            // جیاوازی نێوان sold_data و reported_sold
            $table->json('differences')->nullable();

            // ✅ هەردووکیان پاش ÷ 2 تۆمار دەکرێن — هەرگیز دوو جار نابێت
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('total_liters', 15, 2)->default(0);

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
