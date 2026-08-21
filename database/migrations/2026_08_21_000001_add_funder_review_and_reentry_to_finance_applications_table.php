<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_applications', function (Blueprint $table) {
            $table->unsignedTinyInteger('funder_status')->nullable()->after('status');
            $table->text('funder_feedback')->nullable()->after('funder_status');
            $table->timestamp('funder_reviewed_at')->nullable()->after('funder_feedback');
            $table->uuid('funder_reviewed_by')->nullable()->after('funder_reviewed_at');
            $table->date('reentry_due_at')->nullable()->after('booking_effective_date');

            $table->foreign('funder_reviewed_by')
                ->references('user_id')
                ->on('users')
                ->nullOnDelete();

            $table->index('funder_status');
            $table->index('reentry_due_at');
        });
    }

    public function down(): void
    {
        Schema::table('finance_applications', function (Blueprint $table) {
            $table->dropForeign(['funder_reviewed_by']);
            $table->dropIndex(['funder_status']);
            $table->dropIndex(['reentry_due_at']);
            $table->dropColumn([
                'funder_status',
                'funder_feedback',
                'funder_reviewed_at',
                'funder_reviewed_by',
                'reentry_due_at',
            ]);
        });
    }
};
