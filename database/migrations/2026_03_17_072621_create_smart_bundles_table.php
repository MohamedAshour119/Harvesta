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
        Schema::create('smart_bundles', function (Blueprint $table) {
            $table->id();
            $table->json('name'); // اسم العرض مدعوم بتعدد اللغات
            $table->integer('target_quantity'); // الكمية المطلوبة لتطبيق الخصم
            $table->decimal('discount_percentage', 5, 2); // نسبة الخصم
            $table->foreignId('product_id')->nullable()->constrained('products')->cascadeOnDelete(); // تخصيص العرض لمنتج معين
            $table->boolean('is_active')->default(true); // حالة تفعيل العرض
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smart_bundles');
    }
};
