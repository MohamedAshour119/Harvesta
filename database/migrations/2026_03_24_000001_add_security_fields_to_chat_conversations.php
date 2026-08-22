<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('metadata');
            $table->string('ua_hash', 64)->nullable()->after('ip_address');
            $table->timestamp('expires_at')->nullable()->index()->after('ua_hash');
        });
    }

    public function down(): void
    {
        Schema::table('chat_conversations', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'ua_hash', 'expires_at']);
        });
    }
};
