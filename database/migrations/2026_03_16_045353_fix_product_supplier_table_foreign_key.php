<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('product_supplier', function (Blueprint $table) {
                if (Schema::hasColumn('product_supplier', 'product_id')) {
                    $table->renameColumn('product_id', 'product_variant_id');
                }
            });

            return;
        }

        // Drop the old foreign key first
        Schema::table('product_supplier', function (Blueprint $table) {
            // Check if foreign key exists before dropping
            $foreignKeys = DB::select(
                "SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'product_supplier' 
                 AND COLUMN_NAME = 'product_id' AND REFERENCED_TABLE_NAME IS NOT NULL",
                [config('database.connections.mysql.database')]
            );

            if (!empty($foreignKeys)) {
                $table->dropForeign('product_supplier_product_id_foreign');
            }
        });

        // Rename the column and point it to product_variants.id
        Schema::table('product_supplier', function (Blueprint $table) {
            $table->renameColumn('product_id', 'product_variant_id');
        });

        Schema::table('product_supplier', function (Blueprint $table) {
            $table->foreign('product_variant_id')
                ->references('id')
                ->on('product_variants')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('product_supplier', function (Blueprint $table) {
                if (Schema::hasColumn('product_supplier', 'product_variant_id')) {
                    $table->renameColumn('product_variant_id', 'product_id');
                }
            });

            return;
        }

        Schema::table('product_supplier', function (Blueprint $table) {
            $table->dropForeign(['product_variant_id']);
        });

        Schema::table('product_supplier', function (Blueprint $table) {
            $table->renameColumn('product_variant_id', 'product_id');
        });

        Schema::table('product_supplier', function (Blueprint $table) {
            $table->foreign('product_id')
                ->references('id')
                ->on('products')
                ->cascadeOnDelete();
        });
    }
};
