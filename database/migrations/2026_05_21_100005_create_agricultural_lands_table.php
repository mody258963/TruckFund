<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agricultural_lands', function (Blueprint $table) {
            $table->uuid('land_id')->primary();
            $table->foreignUuid('customer_id')->constrained('customers', 'customer_id')->cascadeOnDelete();
            $table->decimal('land_area_acres', 10, 3)->nullable();
            $table->string('location', 200)->nullable();
            $table->string('governorate', 100)->nullable();
            $table->unsignedTinyInteger('ownership_type')->nullable();
            $table->string('contract_url', 500)->nullable();
            $table->text('notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agricultural_lands');
    }
};
