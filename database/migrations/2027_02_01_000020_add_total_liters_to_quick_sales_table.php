<?php
// database/migrations/xxxx_xx_xx_xxxxxx_add_total_liters_to_quick_sales_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('quick_sales', function (Blueprint $table) {
            $table->decimal('total_liters', 15, 2)->default(0)->after('total_amount');
        });
    }

    public function down()
    {
        Schema::table('quick_sales', function (Blueprint $table) {
            $table->dropColumn('total_liters');
        });
    }
};
