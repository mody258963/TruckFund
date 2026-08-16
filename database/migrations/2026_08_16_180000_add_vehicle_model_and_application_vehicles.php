<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auto_products', function (Blueprint $table) {
            $table->string('model', 120)->nullable()->after('brand');
        });

        // Preserve existing catalog entries by copying the current display name.
        DB::table('auto_products')->update(['model' => DB::raw('name')]);

        Schema::create('finance_application_auto_product', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('app_id')
                ->constrained('finance_applications', 'app_id')
                ->cascadeOnDelete();
            $table->foreignUuid('auto_product_id')
                ->constrained('auto_products', 'id')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['app_id', 'auto_product_id'], 'fa_auto_product_unique');
            $table->index(['app_id', 'sort_order']);
        });

        $legacy = DB::table('finance_applications')
            ->whereNotNull('auto_product_id')
            ->select(['app_id', 'auto_product_id', 'created_at', 'updated_at'])
            ->get();

        foreach ($legacy as $row) {
            DB::table('finance_application_auto_product')->insert([
                'app_id' => $row->app_id,
                'auto_product_id' => $row->auto_product_id,
                'sort_order' => 0,
                'created_at' => $row->created_at,
                'updated_at' => $row->updated_at,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_application_auto_product');

        Schema::table('auto_products', function (Blueprint $table) {
            $table->dropColumn('model');
        });
    }
};
