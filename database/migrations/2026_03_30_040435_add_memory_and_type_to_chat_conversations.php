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
        Schema::table('chat_conversations', function (Blueprint $table) {
            // Feature 1: Memory & Context
            $table->text('summary')->nullable()->after('metadata');
            $table->json('topic_context')->nullable()->after('summary');

            // Feature 7: Admin Bot
            $table->enum('type', ['customer', 'admin'])->default('customer')->after('topic_context');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropColumn(['summary', 'topic_context', 'type']);
        });
    }
};
