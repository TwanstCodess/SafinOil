<?php
// database/migrations/2024_01_01_000019_add_reported_sold_to_quick_sales_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('quick_sales', function (Blueprint $table) {
            // زیادکردنی ستوونی reported_sold
            $table->json('reported_sold')->nullable()->after('sold_data');

            // دڵنیابوون لە بوونی ستوونی created_by (ئەگەر نییە)
            if (!Schema::hasColumn('quick_sales', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            }

            // دڵنیابوون لە بوونی ستوونی closed_by (ئەگەر نییە)
            if (!Schema::hasColumn('quick_sales', 'closed_by')) {
                $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            }
        });
    }

    public function down()
    {
        Schema::table('quick_sales', function (Blueprint $table) {
            $table->dropColumn('reported_sold');
        });
    }
};
