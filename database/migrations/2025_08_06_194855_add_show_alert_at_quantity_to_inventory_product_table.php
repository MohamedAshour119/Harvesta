<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('inventory_product', function (Blueprint $table) {
            $table->unsignedInteger('show_alert_at_quantity')->nullable()->after('inventory_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_product', function (Blueprint $table) {
            $table->dropColumn('show_alert_at_quantity');
        });
    }
};
