<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Departament;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReservationController extends Controller
{
    public function index(Request $request)
    {
        $query = Reservation::with(['employee', 'departament', 'customer', 'products']);

        // Filtering
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($q) use ($search) {
                        $q->where('firstname', 'like', "%{$search}%")
                            ->orWhere('lastname', 'like', "%{$search}%");
                    });
            });
        }

        if ($location = $request->input('location')) {
            $query->where('location', $location);
        }

        if ($departamentId = $request->input('departament_id')) {
            $query->where('departament_id', $departamentId);
        }

        if ($date = $request->input('date')) {
            $query->whereDate('check_in', '<=', $date)
                ->whereDate('check_out', '>=', $date);
        }

        // Sorting
        $sort = $request->input('sort', 'updated_at');
        $direction = $request->input('direction', 'desc');

        $allowedSorts = ['id', 'location', 'check_in', 'check_out', 'total_stay_cost', 'total_extra_cost', 'status', 'updated_at'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('updated_at', 'desc');
        }

        $reservations = $query->paginate(10)->withQueryString();

        return Inertia::render('Admin/Reservations/Index', [
            'reservations' => $reservations,
            'filters' => $request->only(['search', 'location', 'departament_id', 'date', 'sort', 'direction']),
            'employees' => User::all(),
            'departments' => Departament::all(),
            'customers' => Customer::all(),
            'products' => \App\Models\Product::where('is_active', true)->get(),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Reservations/Create', [
            'employees' => User::all(),
            'departaments' => Departament::all(),
            'customers' => Customer::all(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'departament_id' => 'required|exists:departament,id',
            'customer_id' => 'required|exists:customers,id',
            'location' => 'required|string|max:10',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'total_stay_cost' => 'required|numeric|min:0',
            'total_extra_cost' => 'required|numeric|min:0',
            'requests' => 'nullable|string',
            'comments' => 'nullable|string',
            'status' => 'required|in:1,2,3,4',
            'products' => 'nullable|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
            'products.*.subtotal' => 'required|numeric|min:0',
        ]);

        $reservation = Reservation::create($validated);

        if ($request->has('products')) {
            $syncData = [];
            foreach ($request->input('products', []) as $product) {
                $syncData[$product['product_id']] = [
                    'quantity' => $product['quantity'],
                    'unit_price' => $product['unit_price'],
                    'subtotal' => $product['subtotal'],
                ];
            }
            $reservation->products()->sync($syncData);
        }

        return back()->with('success', 'Reservación creada exitosamente.');
    }

    public function show(Reservation $reservation)
    {
        $reservation->load(['employee', 'departament', 'customer', 'products']);
        return Inertia::render('Admin/Reservations/Show', [
            'reservation' => $reservation
        ]);
    }

    public function edit(Reservation $reservation)
    {
        $reservation->load(['employee', 'departament', 'customer', 'products']);

        return Inertia::render('Admin/Reservations/Edit', [
            'reservation' => $reservation,
            'employees' => User::all(),
            'departaments' => Departament::all(), // Show all to allow editing even if currently unavailable
            'customers' => Customer::all(),
        ]);
    }

    public function update(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'departament_id' => 'required|exists:departament,id',
            'customer_id' => 'required|exists:customers,id',
            'location' => 'required|string|max:10',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'total_stay_cost' => 'required|numeric|min:0',
            'total_extra_cost' => 'required|numeric|min:0',
            'requests' => 'nullable|string',
            'comments' => 'nullable|string',
            'status' => 'required|in:1,2,3,4',
            'products' => 'nullable|array',
            'products.*.product_id' => 'required|exists:products,id',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.unit_price' => 'required|numeric|min:0',
            'products.*.subtotal' => 'required|numeric|min:0',
        ]);

        $reservation->update($validated);

        if ($request->has('products')) {
            $syncData = [];
            foreach ($request->input('products', []) as $product) {
                $syncData[$product['product_id']] = [
                    'quantity' => $product['quantity'],
                    'unit_price' => $product['unit_price'],
                    'subtotal' => $product['subtotal'],
                ];
            }
            $reservation->products()->sync($syncData);
        }

        return back()->with('success', 'Reservación actualizada exitosamente.');
    }

    public function destroy(Reservation $reservation)
    {
        $reservation->delete();

        return redirect()->route('reservations.index')->with('success', 'Reservación eliminada exitosamente.');
    }
}
