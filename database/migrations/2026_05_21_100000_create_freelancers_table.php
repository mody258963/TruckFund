<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('freelancers', function (Blueprint $table) {
            $table->uuid('freelancer_id')->primary();
            $table->string('full_name', 150);
            $table->string('phone', 20);
            $table->string('national_id', 50)->nullable()->index();
            $table->string('id_card_url', 500)->nullable();
            $table->json('documents')->nullable();
            $table->boolean('is_locked')->default(true);
            $table->foreignUuid('created_by_user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('freelancers');
    }
};
