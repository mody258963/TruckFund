<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_products', function (Blueprint $table) {
            $table->uuid('product_id')->primary();
            $table->string('name', 200);
            $table->string('product_code', 50)->unique();
            $table->decimal('percentage', 8, 2);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_products');
    }
};
