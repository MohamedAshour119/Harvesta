<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->decimal('agent_price', 10, 2)->nullable()->after('price_after_discount');
        });

        DB::table('product_variants')->update([
            'agent_price' => DB::raw('unit_price_usd'),
        ]);
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('agent_price');
        });
    }
};
