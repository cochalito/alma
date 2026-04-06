<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductLocation;
use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class ExternalSaleController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        
        $allowedLocation = null;
        if ($user) {
            if (str_ends_with($user->role, '_LA_PAZ')) {
                $allowedLocation = 'LP';
            } elseif (str_ends_with($user->role, '_UYUNI')) {
                $allowedLocation = 'UYUNI';
            }
        }

        $query = Product::where('is_active', true)->with('locations')->orderBy('name', 'asc');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $products = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/ExternalSales/Index', [
            'products' => $products,
            'filters' => $request->only(['search']),
            'allowedLocation' => $allowedLocation
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        // Enforce location if restricted by role
        if ($user) {
            if (str_ends_with($user->role, '_LA_PAZ')) {
                $request->merge(['location' => 'LP']);
            } elseif (str_ends_with($user->role, '_UYUNI')) {
                $request->merge(['location' => 'UYUNI']);
            }
        }

        $validated = $request->validate([
            'location' => 'required|string|in:LP,UYUNI',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($validated, $user) {
            $product_id = $validated['product_id'];
            $location = $validated['location'];
            $quantity = $validated['quantity'];

            // Reduce stock
            $locationRecord = ProductLocation::where('product_id', $product_id)
                ->where('location', $location)
                ->first();

            if ($locationRecord) {
                $locationRecord->decrement('stock', $quantity);
            } else {
                ProductLocation::create([
                    'product_id' => $product_id,
                    'location' => $location,
                    'stock' => -$quantity
                ]);
            }

            // Create InventoryMovement for Venta Externa
            InventoryMovement::create([
                'product_id' => $product_id,
                'location' => $location,
                'type' => 'out',
                'quantity' => $quantity,
                'user_id' => $user->id ?? 1,
                'description' => 'Venta Directa Externa',
            ]);
        });

        return back()->with('success', 'Venta externa procesada exitosamente.');
    }
}
