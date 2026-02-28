<?php
// database/migrations/2026_02_28_121540_update_transactions_table_add_credit_payment.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SQLite پشتگیری گۆڕینی ENUM ناکات، بۆیە دەبێت تەیبڵەکە تازە بکەینەوە
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // سەرەتا داتاکان بەکاپ بکە
            $transactions = DB::table('transactions')->get();

            // تەیبڵی کاتی دروستبکە
            Schema::create('transactions_new', function (Blueprint $table) {
                $table->id();
                $table->string('transaction_number')->unique();
                $table->string('type')->comment('جۆری مامەڵە');
                $table->decimal('amount', 15, 2);
                $table->decimal('balance_before', 15, 2);
                $table->decimal('balance_after', 15, 2);
                $table->nullableMorphs('transactionable');
                $table->string('reference_number')->nullable();
                $table->text('description')->nullable();
                $table->date('transaction_date');
                $table->string('created_by')->nullable();
                $table->timestamps();

                $table->index(['type', 'transaction_date']);
                $table->index('transaction_number');
            });

            // داتاکان بگوازەرەوە بۆ تەیبڵی نوێ
            foreach ($transactions as $transaction) {
                DB::table('transactions_new')->insert([
                    'id' => $transaction->id,
                    'transaction_number' => $transaction->transaction_number,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'balance_before' => $transaction->balance_before,
                    'balance_after' => $transaction->balance_after,
                    'transactionable_type' => $transaction->transactionable_type,
                    'transactionable_id' => $transaction->transactionable_id,
                    'reference_number' => $transaction->reference_number,
                    'description' => $transaction->description,
                    'transaction_date' => $transaction->transaction_date,
                    'created_by' => $transaction->created_by,
                    'created_at' => $transaction->created_at,
                    'updated_at' => $transaction->updated_at,
                ]);
            }

            // تەیبڵە کۆنەکە بسڕەوە و تەیبڵی نوێ ناوی بگۆڕە
            Schema::dropIfExists('transactions');
            Schema::rename('transactions_new', 'transactions');

        } else {
            // بۆ MySQL و PostgreSQL - سەرەتا ئیندێکسەکان بسڕەوە
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropIndex(['type', 'transaction_date']);
                $table->dropIndex(['transaction_number']);
                $table->dropIndex(['transactionable_type', 'transactionable_id']);
            });

            // ستوونی type بگۆڕە
            DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('purchase', 'sale', 'expense', 'salary', 'penalty', 'cash_add', 'cash_withdraw', 'credit_payment', 'capital_add', 'capital_withdraw') DEFAULT 'cash_add'");

            // دیسانەوە ئیندێکسەکان دروستبکەوە
            Schema::table('transactions', function (Blueprint $table) {
                $table->index(['type', 'transaction_date']);
                $table->index('transaction_number');
                $table->index(['transactionable_type', 'transactionable_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            // بۆ SQLite هەمان کار بکە بەڵام بە شێوەی پێچەوانە
            $transactions = DB::table('transactions')->get();

            Schema::create('transactions_old', function (Blueprint $table) {
                $table->id();
                $table->string('transaction_number')->unique();
                $table->string('type');
                $table->decimal('amount', 15, 2);
                $table->decimal('balance_before', 15, 2);
                $table->decimal('balance_after', 15, 2);
                $table->nullableMorphs('transactionable');
                $table->string('reference_number')->nullable();
                $table->text('description')->nullable();
                $table->date('transaction_date');
                $table->string('created_by')->nullable();
                $table->timestamps();

                $table->index(['type', 'transaction_date']);
                $table->index('transaction_number');
            });

            foreach ($transactions as $transaction) {
                DB::table('transactions_old')->insert([
                    'id' => $transaction->id,
                    'transaction_number' => $transaction->transaction_number,
                    'type' => $transaction->type,
                    'amount' => $transaction->amount,
                    'balance_before' => $transaction->balance_before,
                    'balance_after' => $transaction->balance_after,
                    'transactionable_type' => $transaction->transactionable_type,
                    'transactionable_id' => $transaction->transactionable_id,
                    'reference_number' => $transaction->reference_number,
                    'description' => $transaction->description,
                    'transaction_date' => $transaction->transaction_date,
                    'created_by' => $transaction->created_by,
                    'created_at' => $transaction->created_at,
                    'updated_at' => $transaction->updated_at,
                ]);
            }

            Schema::dropIfExists('transactions');
            Schema::rename('transactions_old', 'transactions');

        } else {
            // بۆ MySQL - بگەڕێوە بۆ دۆخی پێشوو
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropIndex(['type', 'transaction_date']);
                $table->dropIndex(['transaction_number']);
                $table->dropIndex(['transactionable_type', 'transactionable_id']);
            });

            DB::statement("ALTER TABLE transactions MODIFY COLUMN type ENUM('purchase', 'sale', 'expense', 'salary', 'penalty', 'cash_add', 'cash_withdraw') DEFAULT 'cash_add'");

            Schema::table('transactions', function (Blueprint $table) {
                $table->index(['type', 'transaction_date']);
                $table->index('transaction_number');
                $table->index(['transactionable_type', 'transactionable_id']);
            });
        }
    }
};
