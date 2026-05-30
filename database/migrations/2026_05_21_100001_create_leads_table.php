<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->uuid('lead_id')->primary();
            $table->string('lead_number', 50)->unique();
            $table->string('customer_name', 150)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('car_brand', 100)->nullable();
            $table->string('car_model', 100)->nullable();
            $table->integer('manufacture_year')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->integer('down_payment_pct')->nullable();
            $table->unsignedTinyInteger('value')->nullable()->index();
            $table->unsignedInteger('ai_score')->nullable();
            $table->boolean('is_priority')->default(false);
            $table->foreignUuid('assigned_user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->foreignUuid('created_by_user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->uuid('customer_id')->nullable()->index();
            $table->unsignedTinyInteger('status')->default(0)->index();
            $table->unsignedTinyInteger('source')->default(0)->index();
            $table->foreignUuid('freelancer_id')->nullable()->constrained('freelancers', 'freelancer_id')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
