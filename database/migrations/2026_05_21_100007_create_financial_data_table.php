<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_data', function (Blueprint $table) {
            $table->uuid('fin_id')->primary();
            $table->foreignUuid('customer_id')->constrained('customers', 'customer_id')->cascadeOnDelete();
            $table->boolean('has_income_proof')->default(false);
            $table->decimal('annual_sales_1yr', 18, 2)->nullable();
            $table->decimal('annual_sales_2yr', 18, 2)->nullable();
            $table->string('org_name', 200)->nullable();
            $table->unsignedTinyInteger('commercial_reg_type')->nullable();
            $table->string('commercial_reg_num', 100)->nullable();
            $table->date('reg_start_date')->nullable();
            $table->date('reg_expiry_date')->nullable();
            $table->decimal('paid_in_capital', 18, 2)->nullable();
            $table->string('org_city', 100)->nullable();
            $table->text('org_address')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_data');
    }
};
