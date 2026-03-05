<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\ProductLocation;
use App\Models\InventoryMovement;
use App\Models\Departament;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Clear existing data
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('reservation_product')->truncate();
        DB::table('inventory_movements')->truncate();
        DB::table('product_locations')->truncate();
        DB::table('products')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 2. Fetch unique locations from departed
        $locations = Departament::select('location')->distinct()->pluck('location')->toArray();
        if (empty($locations)) {
            // fallback in case no departments exist yet
            $locations = ['LP', 'UYUNI', 'CUSCO'];
        }

        // 3. Define products
        $products = [
            // Bebidas -> beverages
            ['name' => 'Agua 500 ml', 'category' => 'beverages', 'price' => 10, 'qty' => 11],
            ['name' => 'Agua 2 lt', 'category' => 'beverages', 'price' => 20, 'qty' => 11],
            ['name' => 'Coca Cola 500 ml', 'category' => 'beverages', 'price' => 10, 'qty' => 14],
            ['name' => 'Coca Cola 2 lt', 'category' => 'beverages', 'price' => 25, 'qty' => 8],
            ['name' => 'Fanta 500 ml', 'category' => 'beverages', 'price' => 10, 'qty' => 7],
            ['name' => 'Fanta 2 lt', 'category' => 'beverages', 'price' => 25, 'qty' => 0],
            ['name' => 'Sprite 500 ml', 'category' => 'beverages', 'price' => 10, 'qty' => 0],
            ['name' => 'Sprite 2 lt', 'category' => 'beverages', 'price' => 25, 'qty' => 0],
            ['name' => 'Powerade Único', 'category' => 'beverages', 'price' => 17, 'qty' => 12],
            ['name' => 'Huari (Cerveza) Lata', 'category' => 'beverages', 'price' => 20, 'qty' => 8],
            ['name' => 'Huari (Cerveza) Botella', 'category' => 'beverages', 'price' => 30, 'qty' => 5],
            ['name' => 'Vino (Tinto/Blanco)', 'category' => 'beverages', 'price' => 80, 'qty' => 0],
            ['name' => 'Sidra', 'category' => 'beverages', 'price' => 45, 'qty' => 6],

            // Snacks -> snacks
            ['name' => 'Maruchan Sopa instantánea', 'category' => 'snacks', 'price' => 25, 'qty' => 40],

            // Toiletries -> toiletries
            ['name' => 'Pasta Dental Crema dental', 'category' => 'toiletries', 'price' => 15, 'qty' => 5],
            ['name' => 'Cepillo Dental', 'category' => 'toiletries', 'price' => 15, 'qty' => 9],
            ['name' => 'Crema de manos Manos y cuerpo', 'category' => 'toiletries', 'price' => 25, 'qty' => 9],
            ['name' => 'Rasuradora Ultra barba', 'category' => 'toiletries', 'price' => 18, 'qty' => 29],
            ['name' => 'Kleenex / Elite Pañuelos', 'category' => 'toiletries', 'price' => 5, 'qty' => 3],
            ['name' => 'Jabón Bolívar', 'category' => 'toiletries', 'price' => 0, 'qty' => 4],
            ['name' => 'Total Sunblock Bloqueador', 'category' => 'toiletries', 'price' => 0, 'qty' => 4],

            // Otros -> other
            ['name' => 'Cortaúñas Gde/Med/Peq', 'category' => 'other', 'price' => 10, 'qty' => 14],
            ['name' => 'Pinza Grande', 'category' => 'other', 'price' => 12, 'qty' => 21],
            ['name' => 'Pinza Pequeña', 'category' => 'other', 'price' => 0, 'qty' => 26],
            ['name' => 'Peine Pequeño', 'category' => 'other', 'price' => 5, 'qty' => 37],
            ['name' => 'Encendedores', 'category' => 'other', 'price' => 0, 'qty' => 23],
            ['name' => 'Labial', 'category' => 'other', 'price' => 0, 'qty' => 2],
        ];

        foreach ($products as $pData) {
            $product = Product::create([
                'name' => $pData['name'],
                'description' => null,
                'category' => $pData['category'],
                'price' => $pData['price'],
                'is_active' => true,
            ]);

            foreach ($locations as $loc) {
                // Stock only for UYUNI location
                $qty = ($loc === 'UYUNI') ? $pData['qty'] : 0;

                ProductLocation::create([
                    'product_id' => $product->id,
                    'location' => $loc,
                    'stock' => $qty,
                ]);

                if ($qty > 0) {
                    InventoryMovement::create([
                        'product_id' => $product->id,
                        'location' => $loc,
                        'type' => 'in',
                        'quantity' => $qty,
                        'description' => 'Stock inicial Uyuni',
                    ]);
                }
            }
        }
    }
}
