<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('agent_min_order_value', 10, 2)->nullable()->after('min_order_value');
        });

        DB::table('products')->update([
            'agent_min_order_value' => DB::raw('min_order_value'),
        ]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('agent_min_order_value');
        });
    }
};
