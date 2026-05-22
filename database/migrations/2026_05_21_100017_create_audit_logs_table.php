<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('audit_id')->primary();
            $table->string('entity_type', 100);
            $table->uuid('entity_id');
            $table->foreignUuid('changed_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->timestamp('changed_at')->useCurrent();
            $table->index(['entity_type', 'entity_id']);
            $table->index('changed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
