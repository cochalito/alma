<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\ExternalSale;
use App\Models\InventoryMovement;
use App\Models\Product;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $movements = DB::table('inventory_movements')
            ->where('description', 'Venta Directa Externa')
            ->get();

        foreach ($movements as $m) {
            $product = DB::table('products')->where('id', $m->product_id)->first();
            if ($product) {
                DB::table('external_sales')->insert([
                    'product_id' => $m->product_id,
                    'user_id' => $m->user_id,
                    'location' => $m->location,
                    'quantity' => $m->quantity,
                    'unit_price' => $product->price,
                    'total_price' => $product->price * $m->quantity,
                    'payment_method' => 'EFECTIVO', // Default for old sales
                    'created_at' => $m->created_at,
                    'updated_at' => $m->updated_at,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down needed, external_sales will be dropped by its own migration down
    }
};
