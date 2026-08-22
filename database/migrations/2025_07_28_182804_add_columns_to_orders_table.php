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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('first_name')->after('user_id');
            $table->string('last_name')->after('first_name');
            $table->string('email')->after('last_name');
            $table->string('phone_number')->after('email');
            $table->string('address')->after('phone_number');
            $table->string('city')->after('address');
            $table->string('province')->after('city');
            $table->string('postal_code')->after('province');
            $table->enum('payment_status', ['pending', 'completed', 'failed'])->default('pending')->after('postal_code');
            $table->double('subtotal')->after('payment_status');
            $table->double('total')->after('subtotal');
            $table->string('tap_charge_id')->nullable()->after('agent_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('first_name');
            $table->dropColumn('last_name');
            $table->dropColumn('email');
            $table->dropColumn('phone_number');
            $table->dropColumn('address');
            $table->dropColumn('city');
            $table->dropColumn('province');
            $table->dropColumn('postal_code');
            $table->dropColumn('payment_status');
            $table->dropColumn('subtotal');
            $table->dropColumn('total');
            $table->dropColumn('tap_charge_id');
        });
    }
};
