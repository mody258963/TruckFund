<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->uuid('supplier_id')->primary();
            $table->string('name');
            $table->string('phone');
            $table->string('address');
            $table->string('governorate');
            $table->unsignedTinyInteger('truck_type');
            $table->string('city');
            $table->text('comment')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
