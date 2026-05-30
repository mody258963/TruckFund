<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_transfer_requests', function (Blueprint $table) {
            $table->uuid('transfer_id')->primary();
            $table->foreignUuid('lead_id')->constrained('leads', 'lead_id')->cascadeOnDelete();
            $table->foreignUuid('requested_by_user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignUuid('from_user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->foreignUuid('to_user_id')->constrained('users', 'user_id')->cascadeOnDelete();
            $table->foreignUuid('reviewed_by_user_id')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->unsignedTinyInteger('status')->default(0)->index();
            $table->text('reason')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_transfer_requests');
    }
};
