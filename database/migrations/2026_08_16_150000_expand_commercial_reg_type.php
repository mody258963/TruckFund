<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_data', function (Blueprint $table) {
            $table->string('commercial_reg_type', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        DB::table('financial_data')
            ->where('commercial_reg_type', '>', 255)
            ->update(['commercial_reg_type' => null]);

        Schema::table('financial_data', function (Blueprint $table) {
            $table->unsignedTinyInteger('commercial_reg_type')->nullable()->change();
        });
    }
};
