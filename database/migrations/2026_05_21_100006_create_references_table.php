<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('references', function (Blueprint $table) {
            $table->uuid('ref_id')->primary();
            $table->foreignUuid('customer_id')->constrained('customers', 'customer_id')->cascadeOnDelete();
            $table->string('full_name', 150);
            $table->string('mobile', 20);
            $table->unsignedTinyInteger('relation')->nullable();
            $table->boolean('same_address')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('references');
    }
};
