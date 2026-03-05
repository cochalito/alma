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

        // Role-based Location Filtering
        $user = auth()->user();
        if ($user) {
            if (str_ends_with($user->role, '_LA_PAZ')) {
                $query->where('location', 'LP');
            } elseif (str_ends_with($user->role, '_UYUNI')) {
                $query->where('location', 'UYUNI');
            }
        }

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

        $departmentsQuery = Departament::query();
        if ($user) {
            if (str_ends_with($user->role, '_LA_PAZ')) {
                $departmentsQuery->where('location', 'LP');
            } elseif (str_ends_with($user->role, '_UYUNI')) {
                $departmentsQuery->where('location', 'UYUNI');
            }
        }

        return Inertia::render('Admin/Reservations/Index', [
            'reservations' => $reservations,
            'filters' => $request->only(['search', 'location', 'departament_id', 'date', 'sort', 'direction']),
            'employees' => User::all(),
            'departments' => $departmentsQuery->get(),
            'customers' => Customer::all(),
            'products' => \App\Models\Product::where('is_active', true)->get(),
        ]);
    }

    public function create()
    {
        $departmentsQuery = Departament::query();
        $user = auth()->user();
        if ($user) {
            if (str_ends_with($user->role, '_LA_PAZ')) {
                $departmentsQuery->where('location', 'LP');
            } elseif (str_ends_with($user->role, '_UYUNI')) {
                $departmentsQuery->where('location', 'UYUNI');
            }
        }

        return Inertia::render('Admin/Reservations/Create', [
            'employees' => User::all(),
            'departaments' => $departmentsQuery->get(),
            'customers' => Customer::all(),
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        if ($user) {
            if (str_ends_with($user->role, '_LA_PAZ')) {
                $request->merge(['location' => 'LP']);
            } elseif (str_ends_with($user->role, '_UYUNI')) {
                $request->merge(['location' => 'UYUNI']);
            }
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
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

        $validator->after(function ($validator) use ($request) {
            $overlapping = Reservation::where('departament_id', $request->departament_id)
                ->where('status', '!=', '4')
                ->where(function ($query) use ($request) {
                    $query->where('check_in', '<', $request->check_out)
                        ->where('check_out', '>', $request->check_in);
                })
                ->exists();

            if ($overlapping) {
                $validator->errors()->add('departament_id', 'Este departamento ya cuenta con una reservación cruzada en estas fechas.');
            }
        });

        $validated = $validator->validate();

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $request, &$reservation) {
            $reservation = Reservation::create($validated);

            if ($request->has('products')) {
                $syncData = [];
                foreach ($request->input('products', []) as $product) {
                    $qty = $product['quantity'];
                    $syncData[$product['product_id']] = [
                        'quantity' => $qty,
                        'unit_price' => $product['unit_price'],
                        'subtotal' => $product['subtotal'],
                    ];

                    // Reduce Stock and Create Movement
                    $locationRecord = \App\Models\ProductLocation::where('product_id', $product['product_id'])
                        ->where('location', $reservation->location)
                        ->first();

                    if ($locationRecord) {
                        $locationRecord->decrement('stock', $qty);
                    } else {
                        \App\Models\ProductLocation::create([
                            'product_id' => $product['product_id'],
                            'location' => $reservation->location,
                            'stock' => -$qty
                        ]);
                    }

                    \App\Models\InventoryMovement::create([
                        'product_id' => $product['product_id'],
                        'location' => $reservation->location,
                        'type' => 'out',
                        'quantity' => $qty,
                        'user_id' => auth()->id() ?? 1,
                        'reservation_id' => $reservation->id,
                        'description' => 'Consumo en reserva',
                    ]);
                }
                $reservation->products()->sync($syncData);
            }

            // Post-creation state
            $refreshed = $reservation->fresh();
            $newDataToTrack = $refreshed->toArray();
            $newDataToTrack['products'] = $refreshed->products()->get()->map(function ($p) {
                return [
                    'product_id' => $p->id,
                    'name' => $p->name,
                    'quantity' => $p->pivot->quantity,
                    'unit_price' => $p->pivot->unit_price,
                    'subtotal' => $p->pivot->subtotal,
                ];
            })->toArray();

            $diff = [];
            $trackableFields = ['employee_id', 'departament_id', 'customer_id', 'location', 'check_in', 'check_out', 'total_stay_cost', 'total_extra_cost', 'requests', 'comments', 'status'];
            foreach ($trackableFields as $field) {
                $diff[$field] = [
                    'old' => null,
                    'new' => $newDataToTrack[$field],
                ];
            }
            if (count($newDataToTrack['products']) > 0) {
                $diff['products'] = [
                    'old' => [],
                    'new' => $newDataToTrack['products'],
                ];
            }

            \App\Models\ReservationHistory::create([
                'reservation_id' => $reservation->id,
                'user_id' => auth()->id() ?? 1,
                'action' => 'created',
                'changes' => $diff
            ]);
        });

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

        $departmentsQuery = Departament::query();
        $user = auth()->user();
        if ($user) {
            if (str_ends_with($user->role, '_LA_PAZ')) {
                $departmentsQuery->where('location', 'LP');
            } elseif (str_ends_with($user->role, '_UYUNI')) {
                $departmentsQuery->where('location', 'UYUNI');
            }
        }

        return Inertia::render('Admin/Reservations/Edit', [
            'reservation' => $reservation,
            'employees' => User::all(),
            'departaments' => $departmentsQuery->get(), // Show all to allow editing even if currently unavailable
            'customers' => Customer::all(),
        ]);
    }

    public function update(Request $request, Reservation $reservation)
    {
        // Prevent editing a reservation that has already been checked out
        if ($reservation->status === '3') {
            return back()->withErrors(['status' => 'Esta reserva ya ha realizado el Check Out y no puede ser editada.']);
        }

        $user = auth()->user();
        if ($user) {
            if (str_ends_with($user->role, '_LA_PAZ')) {
                $request->merge(['location' => 'LP']);
            } elseif (str_ends_with($user->role, '_UYUNI')) {
                $request->merge(['location' => 'UYUNI']);
            }
        }

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
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

        $validator->after(function ($validator) use ($request, $reservation) {
            $overlapping = Reservation::where('departament_id', $request->departament_id)
                ->where('id', '!=', $reservation->id)
                ->where('status', '!=', '4')
                ->where(function ($query) use ($request) {
                    $query->where('check_in', '<', $request->check_out)
                        ->where('check_out', '>', $request->check_in);
                })
                ->exists();

            if ($overlapping) {
                $validator->errors()->add('departament_id', 'Este departamento ya cuenta con una reservación cruzada para las mismas fechas editadas.');
            }
        });

        $validated = $validator->validate();

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $request, $reservation) {
            $oldLocation = $reservation->location;
            $oldProducts = $reservation->products()->get();

            // Track state for history
            $oldDataToTrack = $reservation->toArray();
            $oldDataToTrack['products'] = $oldProducts->map(function ($p) {
                return [
                    'product_id' => $p->id,
                    'name' => $p->name,
                    'quantity' => $p->pivot->quantity,
                    'unit_price' => $p->pivot->unit_price,
                    'subtotal' => $p->pivot->subtotal,
                ];
            })->toArray();

            $reservation->update($validated);
            $newLocation = $reservation->location;

            if ($request->has('products')) {
                $syncData = [];
                $newProductsList = $request->input('products', []);

                // We'll manage stock differences
                // First, return all old stock to the OLD location because the reservation could have changed location entirely
                foreach ($oldProducts as $oldP) {
                    $qty = $oldP->pivot->quantity;

                    $locRecord = \App\Models\ProductLocation::where('product_id', $oldP->id)->where('location', $oldLocation)->first();
                    if ($locRecord)
                        $locRecord->increment('stock', $qty);

                    \App\Models\InventoryMovement::create([
                        'product_id' => $oldP->id,
                        'location' => $oldLocation,
                        'type' => 'in',
                        'quantity' => $qty,
                        'user_id' => auth()->id() ?? 1,
                        'reservation_id' => $reservation->id,
                        'description' => 'Reversión por edición de reserva',
                    ]);
                }

                // Now, consume stock from the NEW location with the NEW quantities
                foreach ($newProductsList as $product) {
                    $qty = $product['quantity'];
                    $syncData[$product['product_id']] = [
                        'quantity' => $qty,
                        'unit_price' => $product['unit_price'],
                        'subtotal' => $product['subtotal'],
                    ];

                    $locRecord = \App\Models\ProductLocation::where('product_id', $product['product_id'])->where('location', $newLocation)->first();
                    if ($locRecord) {
                        $locRecord->decrement('stock', $qty);
                    } else {
                        \App\Models\ProductLocation::create([
                            'product_id' => $product['product_id'],
                            'location' => $newLocation,
                            'stock' => -$qty
                        ]);
                    }

                    \App\Models\InventoryMovement::create([
                        'product_id' => $product['product_id'],
                        'location' => $newLocation,
                        'type' => 'out',
                        'quantity' => $qty,
                        'user_id' => auth()->id() ?? 1,
                        'reservation_id' => $reservation->id,
                        'description' => 'Consumo en reserva (editada)',
                    ]);
                }

                $reservation->products()->sync($syncData);
            }

            // Post-update state
            $refreshed = $reservation->fresh();
            $newDataToTrack = $refreshed->toArray();
            $newDataToTrack['products'] = $refreshed->products()->get()->map(function ($p) {
                return [
                    'product_id' => $p->id,
                    'name' => $p->name,
                    'quantity' => $p->pivot->quantity,
                    'unit_price' => $p->pivot->unit_price,
                    'subtotal' => $p->pivot->subtotal,
                ];
            })->toArray();

            // Identify changes
            $diff = [];
            $trackableFields = ['employee_id', 'departament_id', 'customer_id', 'location', 'check_in', 'check_out', 'total_stay_cost', 'total_extra_cost', 'requests', 'comments', 'status'];
            foreach ($trackableFields as $field) {
                if ($oldDataToTrack[$field] != $newDataToTrack[$field]) {
                    $diff[$field] = [
                        'old' => $oldDataToTrack[$field],
                        'new' => $newDataToTrack[$field],
                    ];
                }
            }

            // Check if products changed
            if (json_encode($oldDataToTrack['products']) !== json_encode($newDataToTrack['products'])) {
                $diff['products'] = [
                    'old' => $oldDataToTrack['products'],
                    'new' => $newDataToTrack['products'],
                ];
            }

            // Save history if there are changes
            if (!empty($diff)) {
                \App\Models\ReservationHistory::create([
                    'reservation_id' => $reservation->id,
                    'user_id' => auth()->id() ?? 1,
                    'action' => 'updated',
                    'changes' => $diff
                ]);
            }
        });

        return back()->with('success', 'Reservación actualizada exitosamente.');
    }

    public function destroy(Reservation $reservation)
    {
        $reservation->delete();

        return redirect()->route('reservations.index')->with('success', 'Reservación eliminada exitosamente.');
    }
}
