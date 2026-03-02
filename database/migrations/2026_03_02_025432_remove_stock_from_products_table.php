<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First migrate existing stock to product_locations
        $products = DB::table('products')->get();
        foreach ($products as $product) {
            DB::table('product_locations')->insert([
                'product_id' => $product->id,
                'location' => 'LA PAZ',
                'stock' => $product->stock,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('inventory_movements')->insert([
                'product_id' => $product->id,
                'location' => 'LA PAZ',
                'type' => 'in',
                'quantity' => $product->stock,
                'description' => 'Migración inicial de stock',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->bigInteger('stock')->default(0);
        });
    }
};
