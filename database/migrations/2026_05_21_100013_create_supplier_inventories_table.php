<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_inventories', function (Blueprint $table) {
            $table->uuid('supplier_product_id')->primary();
            $table->foreignUuid('supplier_id')->constrained('suppliers', 'supplier_id')->cascadeOnDelete();
            $table->string('brand');
            $table->string('model');
            $table->unsignedTinyInteger('type');
            $table->string('chassis');
            $table->decimal('kilometers', 12, 2)->nullable();
            $table->unsignedTinyInteger('condition');
            $table->decimal('price', 15, 2);
            $table->integer('manufacture_year');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_inventories');
    }
};
