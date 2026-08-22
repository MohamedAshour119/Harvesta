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
        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            // Assuming your product variants table is named 'product_variants'
            // Adjust if your variants table has a different name (e.g., 'variants').
            $table->foreignId('variant_id')->constrained('product_variants')->onDelete('cascade'); 
            $table->timestamps();

            // Unique constraint to prevent duplicate product_variant entries per user
            $table->unique(['user_id', 'product_id', 'variant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');
    }
};