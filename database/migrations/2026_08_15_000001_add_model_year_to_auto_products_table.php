<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auto_products', function (Blueprint $table) {
            $table->unsignedSmallInteger('model_year')->nullable()->after('brand');
            $table->index(['brand', 'name', 'model_year']);
        });
    }

    public function down(): void
    {
        Schema::table('auto_products', function (Blueprint $table) {
            $table->dropIndex(['brand', 'name', 'model_year']);
            $table->dropColumn('model_year');
        });
    }
};
