<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_documents', function (Blueprint $table) {
            $table->uuid('app_doc_id')->primary();
            $table->foreignUuid('app_id')->constrained('finance_applications', 'app_id')->cascadeOnDelete();
            $table->unsignedTinyInteger('doc_type');
            $table->string('file_url', 500);
            $table->timestamp('uploaded_at')->useCurrent();
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users', 'user_id')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_documents');
    }
};
