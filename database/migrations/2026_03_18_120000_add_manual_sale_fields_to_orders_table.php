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
            $table->string('source')->default('customer_checkout')->after('status');
            $table->string('manual_sale_status')->nullable()->after('source');
            $table->timestamp('sold_at')->nullable()->after('manual_sale_status');
            $table->timestamp('approved_at')->nullable()->after('sold_at');
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->text('review_notes')->nullable()->after('approved_by');
            $table->uuid('invoice_uuid')->nullable()->unique()->after('review_notes');
            $table->timestamp('invoice_sent_at')->nullable()->after('invoice_uuid');
            $table->json('special_sales_options')->nullable()->after('invoice_sent_at');

            $table->index(['source', 'manual_sale_status']);
            $table->index(['agent_code', 'source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropIndex(['source', 'manual_sale_status']);
            $table->dropIndex(['agent_code', 'source']);
            $table->dropUnique(['invoice_uuid']);
            $table->dropColumn([
                'source',
                'manual_sale_status',
                'sold_at',
                'approved_at',
                'review_notes',
                'invoice_uuid',
                'invoice_sent_at',
                'special_sales_options',
            ]);
        });
    }
};
