<?php
// database/migrations/2024_01_01_000010_create_transactions_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique(); // ژمارەی مامەڵە
            $table->enum('type', [
                'purchase',      // کڕین
                'sale',          // فرۆشتن
                'expense',       // خەرجی (گشتی)
                'salary',        // مووچەی کارمەند
                'penalty',       // سزای کارمەند
                'cash_add',      // زیادکردنی پارە بۆ قاسە
                'cash_withdraw'  // کەمکردنەوەی پارە لە قاسە
            ])->comment('جۆری مامەڵە');

            $table->decimal('amount', 15, 2)->comment('بڕی پارە');
            $table->decimal('balance_before', 15, 2)->comment('ڕەوشتی قاسە پێش مامەڵە');
            $table->decimal('balance_after', 15, 2)->comment('ڕەوشتی قاسە دوای مامەڵە');

            // پەیوەندی بە مامەڵەکەوە (ئارادی)
            $table->nullableMorphs('transactionable');

            $table->string('reference_number')->nullable()->comment('ژمارەی سەرچاوە');
            $table->text('description')->nullable()->comment('وەسف');
            $table->date('transaction_date')->comment('ڕێکەوتی مامەڵە');
            $table->string('created_by')->nullable()->comment('دروستکراو لەلایەن');

            $table->timestamps();

            // ئیندێکس بۆ خێراکردن
            $table->index(['type', 'transaction_date']);
            $table->index('transaction_number');
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
};
