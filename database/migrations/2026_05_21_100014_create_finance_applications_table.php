<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_applications', function (Blueprint $table) {
            $table->uuid('app_id')->primary();
            $table->string('app_number', 50)->unique();
            $table->foreignUuid('customer_id')->constrained('customers', 'customer_id')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->unsignedTinyInteger('source')->nullable();
            $table->foreignUuid('financial_merchant_id')->nullable()->constrained('merchants', 'merchant_id')->nullOnDelete();
            $table->foreignUuid('fin_merchant_agent_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->unsignedTinyInteger('automotive_status')->nullable();
            $table->foreignUuid('auto_product_id')->nullable()->constrained('auto_products', 'id')->nullOnDelete();
            $table->foreignUuid('financial_product_id')->nullable()->constrained('financial_products', 'product_id')->nullOnDelete();
            $table->unsignedTinyInteger('status')->default(0)->index();
            $table->date('booking_effective_date')->nullable();
            $table->decimal('down_payment', 15, 2);
            $table->decimal('total_loan_amount', 15, 2);
            $table->decimal('total_truck_price', 15, 2);
            $table->decimal('monthly_income', 15, 2)->nullable();
            $table->text('customer_comm_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_applications');
    }
};
