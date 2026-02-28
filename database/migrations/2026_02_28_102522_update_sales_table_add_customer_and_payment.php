<?php
// database/migrations/2024_01_01_000014_update_sales_table_add_customer_and_payment.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('payment_type', ['cash', 'credit'])->default('cash')->comment('جۆری پارەدان');
            $table->enum('status', ['pending', 'paid', 'partial'])->default('pending')->comment('ڕەوشتی قەرز');
            $table->decimal('paid_amount', 15, 2)->default(0)->comment('بڕی پارەدان');
            $table->decimal('remaining_amount', 15, 2)->default(0)->comment('بڕی ماوە');
            $table->date('due_date')->nullable()->comment('بەرواری وەستان');
            $table->date('paid_date')->nullable()->comment('بەرواری دانەوە');
        });
    }

    public function down()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
            $table->dropColumn([
                'customer_id',
                'payment_type',
                'status',
                'paid_amount',
                'remaining_amount',
                'due_date',
                'paid_date'
            ]);
        });
    }
};
