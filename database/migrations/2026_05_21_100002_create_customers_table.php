<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('customer_id')->primary();
            $table->foreignUuid('lead_id')->nullable()->constrained('leads', 'lead_id')->nullOnDelete();
            $table->string('display_name', 150);
            $table->string('mobile_number', 20)->index();
            $table->string('email', 255)->nullable();
            $table->unsignedTinyInteger('source')->nullable();
            $table->string('nationality', 100)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->unsignedTinyInteger('gender')->nullable();
            $table->unsignedTinyInteger('marital_status')->nullable();
            $table->unsignedTinyInteger('job_status')->nullable();
            $table->string('occupation', 150)->nullable();
            $table->string('organization_name', 200)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('area', 100)->nullable();
            $table->text('address')->nullable();
            $table->unsignedTinyInteger('onboarding_step')->default(0);
            $table->boolean('profile_completed')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
