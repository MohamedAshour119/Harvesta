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
        Schema::table('product_image_embeddings', function (Blueprint $table) {
            $table->longText('text_embedding')->nullable()->after('embedding');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_image_embeddings', function (Blueprint $table) {
            $table->dropColumn('text_embedding');
        });
    }
};
