<?php
// database/migrations/2024_01_01_000013_create_customers_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('identity_number')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->decimal('total_credit', 15, 2)->default(0); // کۆی قەرز
            $table->decimal('total_paid', 15, 2)->default(0); // کۆی پارەی دانراوە
            $table->decimal('current_debt', 15, 2)->default(0); // قەرزی ماوە
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['name', 'phone']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('customers');
    }
};
