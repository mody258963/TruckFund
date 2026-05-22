<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('communication_logs', function (Blueprint $table) {
            $table->uuid('comm_id')->primary();
            $table->foreignUuid('lead_id')->nullable()->constrained('leads', 'lead_id')->cascadeOnDelete();
            $table->foreignUuid('app_id')->nullable()->constrained('finance_applications', 'app_id')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->unsignedTinyInteger('type');
            $table->unsignedTinyInteger('platform')->nullable();
            $table->text('content');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_logs');
    }
};
