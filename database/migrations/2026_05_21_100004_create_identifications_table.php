<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('identifications', function (Blueprint $table) {
            $table->uuid('id_doc_id')->primary();
            $table->foreignUuid('customer_id')->constrained('customers', 'customer_id')->cascadeOnDelete();
            $table->unsignedTinyInteger('id_type');
            $table->string('id_number', 50)->unique();
            $table->string('name_en', 150)->nullable();
            $table->string('name_ar', 150)->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('id_front_url', 500)->nullable();
            $table->string('id_back_url', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('identifications');
    }
};
