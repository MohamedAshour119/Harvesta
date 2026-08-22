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
        Schema::create('shipping_destinations', function (Blueprint $table) {
            $table->id();
            $table->json('city');
            $table->decimal('rate_per_kg', 8, 2);
            $table->decimal('rate_per_volume_unit', 8, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_destinations');
    }
};
