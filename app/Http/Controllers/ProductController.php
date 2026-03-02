<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Departament;
use App\Models\InventoryMovement;
use App\Models\ProductLocation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with('locations');

        // Filtering
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sort = $request->input('sort', 'updated_at');
        $direction = $request->input('direction', 'desc');

        $allowedSorts = ['id', 'name', 'category', 'price', 'is_active', 'updated_at'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('updated_at', 'desc');
        }

        $products = $query->paginate(10)->withQueryString();

        $products->getCollection()->transform(function ($product) {
            $data = $product->toArray();
            $data['total_stock'] = $product->totalStock();
            return $data;
        });

        $locations = Departament::select('location')->distinct()->pluck('location');

        return Inertia::render('Admin/Products/Index', [
            'products' => $products,
            'filters' => $request->only(['search', 'sort', 'direction']),
            'locations' => $locations
        ]);
    }

    public function create()
    {
        $locations = Departament::select('location')->distinct()->pluck('location');
        return Inertia::render('Admin/Products/Create', [
            'locations' => $locations
        ]);
    }

    public function store(Request $request)
    {
        $locations = Departament::select('location')->distinct()->pluck('location')->toArray();
        $stockValidation = [];
        foreach ($locations as $loc) {
            $stockValidation['stock_' . $loc] = 'required|integer|min:0';
        }

        $validated = $request->validate(array_merge([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:1,2,3,4,beverages,snacks,toiletries,other',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ], $stockValidation));

        DB::transaction(function () use ($validated, $locations, $request) {
            $product = Product::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'category' => $validated['category'],
                'price' => $validated['price'],
                'is_active' => isset($validated['is_active']) ? $validated['is_active'] : true,
            ]);

            foreach ($locations as $loc) {
                $stock = $validated['stock_' . $loc];
                ProductLocation::create([
                    'product_id' => $product->id,
                    'location' => $loc,
                    'stock' => $stock
                ]);

                if ($stock > 0) {
                    InventoryMovement::create([
                        'product_id' => $product->id,
                        'location' => $loc,
                        'type' => 'in',
                        'quantity' => $stock,
                        'user_id' => auth()->id() ?? 1,
                        'description' => 'Stock inicial',
                    ]);
                }
            }
        });

        return redirect()->route('products.index')->with('success', 'Producto creado exitosamente.');
    }

    public function edit(Product $product)
    {
        $locations = Departament::select('location')->distinct()->pluck('location');
        $product->load('locations');
        return Inertia::render('Admin/Products/Edit', [
            'product' => $product,
            'locations' => $locations
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $locations = Departament::select('location')->distinct()->pluck('location')->toArray();
        $stockValidation = [];
        foreach ($locations as $loc) {
            $stockValidation['stock_' . $loc] = 'required|integer|min:0';
        }

        $validated = $request->validate(array_merge([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:1,2,3,4,beverages,snacks,toiletries,other',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ], $stockValidation));

        DB::transaction(function () use ($validated, $locations, $product) {
            $product->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'category' => $validated['category'],
                'price' => $validated['price'],
                'is_active' => isset($validated['is_active']) ? $validated['is_active'] : true,
            ]);

            foreach ($locations as $loc) {
                $newStock = $validated['stock_' . $loc];
                $locationRecord = ProductLocation::where('product_id', $product->id)->where('location', $loc)->first();

                $oldStock = $locationRecord ? $locationRecord->stock : 0;

                if (!$locationRecord) {
                    ProductLocation::create([
                        'product_id' => $product->id,
                        'location' => $loc,
                        'stock' => $newStock
                    ]);
                } else {
                    $locationRecord->update(['stock' => $newStock]);
                }

                $diff = $newStock - $oldStock;
                if ($diff != 0) {
                    InventoryMovement::create([
                        'product_id' => $product->id,
                        'location' => $loc,
                        'type' => $diff > 0 ? 'in' : 'out',
                        'quantity' => abs($diff),
                        'user_id' => auth()->id() ?? 1,
                        'description' => 'Ajuste manual de stock',
                    ]);
                }
            }
        });

        return redirect()->route('products.index')->with('success', 'Producto actualizado exitosamente.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Producto eliminado exitosamente.');
    }
}
