<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('identifications', function (Blueprint $table) {
            $table->string('id_number', 50)->nullable()->change();
        });

        // The old placeholder collided with the unique index as soon as a
        // second customer uploaded an ID photo before entering a number.
        DB::table('identifications')
            ->where('id_number', 'PENDING')
            ->update(['id_number' => null]);
    }

    public function down(): void
    {
        DB::statement(
            "UPDATE identifications SET id_number = CONCAT('PENDING-', id_doc_id) WHERE id_number IS NULL"
        );

        Schema::table('identifications', function (Blueprint $table) {
            $table->string('id_number', 50)->nullable(false)->change();
        });
    }
};
