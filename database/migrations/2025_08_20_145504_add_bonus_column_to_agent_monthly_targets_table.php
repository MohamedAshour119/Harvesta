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
        Schema::table('agent_monthly_targets', function (Blueprint $table) {
            $table->decimal('bonus', 10, 2)->default(0)->after('achieved_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_monthly_targets', function (Blueprint $table) {
            $table->dropColumn('bonus');
        });
    }
};
