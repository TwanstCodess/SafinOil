<?php
// database/migrations/2024_01_01_000020_add_shift_to_quick_sales_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('quick_sales', function (Blueprint $table) {
            $table->enum('shift', ['morning', 'evening'])->default('morning')->after('sale_date');
            // لابردنی یونیککە کۆنە و زیادکردنی یونیکێکی نوێ بۆ (sale_date + shift)
            $table->dropUnique(['sale_date']);
            $table->unique(['sale_date', 'shift']);
        });
    }

    public function down()
    {
        Schema::table('quick_sales', function (Blueprint $table) {
            $table->dropUnique(['sale_date', 'shift']);
            $table->unique('sale_date');
            $table->dropColumn('shift');
        });
    }
};
