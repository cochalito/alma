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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:beverages,snacks,toiletries,other',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $product = Product::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'price' => $validated['price'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        // Initialize empty locations if needed
        $locations = Departament::select('location')->distinct()->pluck('location');
        foreach ($locations as $loc) {
            ProductLocation::create([
                'product_id' => $product->id,
                'location' => $loc,
                'stock' => 0
            ]);
        }

        return redirect()->route('products.index')->with('success', 'Producto creado exitosamente.');
    }

    public function edit(Product $product)
    {
        return Inertia::render('Admin/Products/Edit', [
            'product' => $product,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|in:beverages,snacks,toiletries,other',
            'price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $product->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'price' => $validated['price'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('products.index')->with('success', 'Producto actualizado exitosamente.');
    }

    public function stockAdjustment(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'location' => 'required|string',
            'type' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1',
            'description' => 'nullable|string|required_if:type,out',
        ], [
            'description.required_if' => 'La descripción es obligatoria para las bajas (salidas).'
        ]);

        DB::transaction(function () use ($validated) {
            $locationRecord = ProductLocation::firstOrCreate(
                ['product_id' => $validated['product_id'], 'location' => $validated['location']],
                ['stock' => 0]
            );

            if ($validated['type'] === 'in') {
                $locationRecord->increment('stock', $validated['quantity']);
            } else {
                if ($locationRecord->stock < $validated['quantity']) {
                    throw new \Exception("Stock insuficiente en {$validated['location']}.");
                }
                $locationRecord->decrement('stock', $validated['quantity']);
            }

            InventoryMovement::create([
                'product_id' => $validated['product_id'],
                'location' => $validated['location'],
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'user_id' => auth()->id(),
                'description' => $validated['description'] ?? ($validated['type'] === 'in' ? 'Alta de stock' : 'Baja de stock'),
            ]);
        });

        return back()->with('success', 'Movimiento de stock registrado correctamente.');
    }

    public function destroy(Product $product)
    {
        // Deactivated as per requirements
        return back()->with('error', 'La eliminación de productos está deshabilitada.');
    }
}
