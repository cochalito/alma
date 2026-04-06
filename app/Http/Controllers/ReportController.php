<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\Departament;
use Carbon\Carbon;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function reservations(Request $request)
    {
        $location = $request->query('location', 'AMBOS'); // AMBOS, LP, UYUNI

        $today = Carbon::today();

        // --- 1) Occupancy Report (Last 10 days) ---
        $chartDates = collect(range(9, 0))->map(function ($days) use ($today) {
            return $today->copy()->subDays($days);
        });

        $occupancyData = [];
        foreach ($chartDates as $date) {
            $query = Reservation::whereDate('check_in', '<=', $date)
                ->whereDate('check_out', '>', $date)
                ->whereIn('status', ['1', '2', '3']); // Usually 1=Confirmada, 2=CheckIn, 3=CheckOut (maybe occupied that night before checkout)

            if ($location !== 'AMBOS') {
                $query->where('location', $location);
            }

            $occupiedCount = $query->distinct('departament_id')->count('departament_id');
            $occupancyData[] = $occupiedCount;
        }

        $occupancyChart = [
            'categories' => $chartDates->map(fn($date) => $date->format('d M'))->toArray(),
            'series' => [
                [
                    'name' => 'Departamentos Ocupados',
                    'data' => $occupancyData
                ]
            ]
        ];

        // --- 2) Financial Report (Last 8 months) ---
        $startMonth = $today->copy()->startOfMonth()->subMonths(7);

        $monthsKeys = collect(range(7, 0))->map(function ($months) use ($today) {
            return $today->copy()->startOfMonth()->subMonths($months);
        });

        // Get reservations matching timeframe
        $revenueQuery = Reservation::where('status', '3') // Only Check Out
            ->whereDate('check_out', '>=', $startMonth);

        if ($location !== 'AMBOS') {
            $revenueQuery->where('location', $location);
        }

        $revenues = $revenueQuery->get();

        $financialData = [];
        foreach ($monthsKeys as $monthDate) {
            // Filter revenues that fall roughly into this month based on check_out
            $monthlyRevs = $revenues->filter(function ($r) use ($monthDate) {
                return Carbon::parse($r->check_out)->format('Y-m') === $monthDate->format('Y-m');
            });

            $sum = $monthlyRevs->sum(function ($r) {
                return $r->total_stay_cost + $r->total_extra_cost;
            });

            $financialData[] = round($sum, 2);
        }

        $financialChart = [
            'categories' => $monthsKeys->map(fn($m) => $m->translatedFormat('M Y'))->toArray(),
            'series' => [
                [
                    'name' => 'Ingresos (Bs.)',
                    'data' => $financialData
                ]
            ]
        ];

        // --- 3) Comparative Report (Current vs Previous Year) ---
        $compareMonthsKeys = collect(range(11, 0))->map(function ($months) use ($today) {
            return $today->copy()->startOfMonth()->subMonths($months);
        });

        $currentYearStart = $today->copy()->startOfMonth()->subMonths(11);
        $previousYearStart = $currentYearStart->copy()->subYear();

        $currentResQuery = Reservation::where('status', '!=', '4')
            ->whereDate('check_in', '>=', $currentYearStart);

        $prevResQuery = Reservation::where('status', '!=', '4')
            ->whereDate('check_in', '>=', $previousYearStart)
            ->whereDate('check_in', '<', $currentYearStart);

        if ($location !== 'AMBOS') {
            $currentResQuery->where('location', $location);
            $prevResQuery->where('location', $location);
        }

        $currentRes = $currentResQuery->get();
        $prevRes = $prevResQuery->get();

        $currentYearData = [];
        $previousYearData = [];

        foreach ($compareMonthsKeys as $monthDate) {
            $cyKey = $monthDate->format('Y-m');
            $pyKey = $monthDate->copy()->subYear()->format('Y-m');

            $cyCount = $currentRes->filter(function ($r) use ($cyKey) {
                return Carbon::parse($r->check_in)->format('Y-m') === $cyKey;
            })->count();

            $pyCount = $prevRes->filter(function ($r) use ($pyKey) {
                return Carbon::parse($r->check_in)->format('Y-m') === $pyKey;
            })->count();

            $currentYearData[] = $cyCount;
            $previousYearData[] = $pyCount;
        }

        $comparisonChart = [
            'categories' => $compareMonthsKeys->map(fn($m) => $m->translatedFormat('M y'))->toArray(),
            'series' => [
                [
                    'name' => 'Este Año',
                    'data' => $currentYearData
                ],
                [
                    'name' => 'Año Pasado',
                    'data' => $previousYearData
                ]
            ]
        ];

        return Inertia::render('Admin/Reports/Reservations', [
            'currentLocation' => $location,
            'occupancyChart' => $occupancyChart,
            'financialChart' => $financialChart,
            'comparisonChart' => $comparisonChart,
        ]);
    }

    public function sales(Request $request)
    {
        $location = $request->query('location');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        // Role-based Location Filtering
        $locationsQuery = Departament::select('location')->distinct();
        $user = auth()->user();
        if ($user) {
            if (str_ends_with($user->role, '_LA_PAZ')) {
                $locationsQuery->where('location', 'LP');
                $location = 'LP';
            } elseif (str_ends_with($user->role, '_UYUNI')) {
                $locationsQuery->where('location', 'UYUNI');
                $location = 'UYUNI';
            }
        }
        $locations = $locationsQuery->pluck('location');

        $query1 = \Illuminate\Support\Facades\DB::table('reservation_product')
            ->join('reservations', 'reservation_product.reservation_id', '=', 'reservations.id')
            ->join('products', 'reservation_product.product_id', '=', 'products.id')
            ->leftJoin('customers', 'reservations.customer_id', '=', 'customers.id')
            ->select(
                'reservations.id as reservation_id',
                'reservations.location',
                'reservations.created_at as sale_date',
                'customers.firstname',
                'customers.lastname',
                'products.name as product_name',
                'reservation_product.quantity',
                'reservation_product.unit_price',
                'reservation_product.subtotal'
            )
            ->where('reservations.status', '!=', '4');

        $query2 = \Illuminate\Support\Facades\DB::table('inventory_movements')
            ->join('products', 'inventory_movements.product_id', '=', 'products.id')
            ->select(
                \Illuminate\Support\Facades\DB::raw('NULL as reservation_id'),
                'inventory_movements.location',
                'inventory_movements.created_at as sale_date',
                \Illuminate\Support\Facades\DB::raw("'Venta' as firstname"),
                \Illuminate\Support\Facades\DB::raw("'Externa' as lastname"),
                'products.name as product_name',
                'inventory_movements.quantity',
                'products.price as unit_price',
                \Illuminate\Support\Facades\DB::raw('(inventory_movements.quantity * products.price) as subtotal')
            )
            ->where('inventory_movements.type', 'out')
            ->where('inventory_movements.description', 'Venta Directa Externa');

        if ($location) {
            $query1->where('reservations.location', $location);
            $query2->where('inventory_movements.location', $location);
        }

        if ($dateFrom) {
            $query1->whereDate('reservations.created_at', '>=', $dateFrom);
            $query2->whereDate('inventory_movements.created_at', '>=', $dateFrom);
        } else {
            // Default to start of current month
            $startOfMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
            $query1->whereDate('reservations.created_at', '>=', $startOfMonth);
            $query2->whereDate('inventory_movements.created_at', '>=', $startOfMonth);
            $dateFrom = $startOfMonth;
        }

        if ($dateTo) {
            $query1->whereDate('reservations.created_at', '<=', $dateTo);
            $query2->whereDate('inventory_movements.created_at', '<=', $dateTo);
        } else {
            // Default to end of current month
            $endOfMonth = Carbon::now()->endOfMonth()->format('Y-m-d');
            $query1->whereDate('reservations.created_at', '<=', $endOfMonth);
            $query2->whereDate('inventory_movements.created_at', '<=', $endOfMonth);
            $dateTo = $endOfMonth;
        }

        $query1->unionAll($query2);
        
        $salesList = $query1->get()->sortByDesc('sale_date')->values();

        $sales = $salesList->map(function ($item) {
            return [
                'reservation_id' => $item->reservation_id,
                'location' => $item->location,
                'sale_date' => Carbon::parse($item->sale_date)->format('Y-m-d H:i:s'),
                'customer_name' => trim($item->firstname . ' ' . $item->lastname),
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'subtotal' => $item->subtotal,
            ];
        });

        // Calculamos totales
        $totalItems = $sales->sum('quantity');
        $totalRevenue = $sales->sum('subtotal');

        return Inertia::render('Admin/Reports/Sales', [
            'sales' => $sales,
            'locations' => $locations,
            'selectedLocation' => $location,
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'summary' => [
                'total_items' => $totalItems,
                'total_revenue' => $totalRevenue,
            ],
        ]);
    }

    public function kardex(Request $request)
    {
        $productId = $request->query('product_id');
        $location = $request->query('location');
        $period = $request->query('period', 'month');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $products = \App\Models\Product::orderBy('name')->get();
        $locationsQuery = Departament::select('location')->distinct();
        $user = auth()->user();
        if ($user) {
            if (str_ends_with($user->role, '_LA_PAZ')) {
                $locationsQuery->where('location', 'LP');
                $location = 'LP';
            } elseif (str_ends_with($user->role, '_UYUNI')) {
                $locationsQuery->where('location', 'UYUNI');
                $location = 'UYUNI';
            }
        }
        $locations = $locationsQuery->pluck('location');

        $movements = null;
        $product = null;
        $initialBalance = 0;
        $currentStock = 0;

        $filters = [
            'product_id' => $productId,
            'location' => $location,
            'period' => $period,
            'date_from' => $dateFrom,
            'date_to' => $dateTo
        ];

        if ($productId && $location) {
            $product = \App\Models\Product::find($productId);

            $startDate = null;
            $endDate = null;

            if ($period === 'month') {
                $startDate = Carbon::now()->startOfMonth();
                $endDate = Carbon::now()->endOfMonth();
            } elseif ($period === 'year') {
                $startDate = Carbon::now()->startOfYear();
                $endDate = Carbon::now()->endOfYear();
            } elseif ($period === 'custom' && $dateFrom && $dateTo) {
                $startDate = Carbon::parse($dateFrom)->startOfDay();
                $endDate = Carbon::parse($dateTo)->endOfDay();
            }

            // Calculate Initial Balance (Movements before startDate)
            if ($startDate) {
                $prevIn = \App\Models\InventoryMovement::where('product_id', $productId)
                    ->where('location', $location)
                    ->where('type', 'in')
                    ->where('created_at', '<', $startDate)
                    ->sum('quantity');

                $prevOut = \App\Models\InventoryMovement::where('product_id', $productId)
                    ->where('location', $location)
                    ->where('type', 'out')
                    ->where('created_at', '<', $startDate)
                    ->sum('quantity');

                $initialBalance = $prevIn - $prevOut;

                $query = \App\Models\InventoryMovement::with(['user', 'reservation.customer'])
                    ->where('product_id', $productId)
                    ->where('location', $location)
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->orderBy('created_at', 'asc');
            } else {
                $query = \App\Models\InventoryMovement::with(['user', 'reservation.customer'])
                    ->where('product_id', $productId)
                    ->where('location', $location)
                    ->orderBy('created_at', 'asc');
            }

            $movements = $query->get()->map(function ($move) {
                return [
                    'id' => $move->id,
                    'type' => $move->type,
                    'quantity' => $move->quantity,
                    'description' => $move->description,
                    'date' => $move->created_at->format('Y-m-d H:i:s'),
                    'user' => $move->user ? $move->user->name : 'Sistema',
                    'reservation' => $move->reservation ? 'Res: #' . $move->reservation->id . ' - ' . ($move->reservation->customer->firstname ?? '') : null,
                ];
            });

            $locationData = \App\Models\ProductLocation::where('product_id', $productId)->where('location', $location)->first();
            $currentStock = $locationData ? $locationData->stock : 0;
        }

        return Inertia::render('Admin/Reports/Kardex', [
            'products' => $products,
            'locations' => $locations,
            'movements' => $movements,
            'selectedProduct' => $product,
            'selectedLocation' => $location,
            'initialBalance' => (int) $initialBalance,
            'currentStock' => (int) $currentStock,
            'filters' => $filters
        ]);
    }

    public function activity(Request $request)
    {
        $userId = $request->query('user_id');
        $reservationId = $request->query('reservation_id');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        // Prepare filter data
        $users = \App\Models\User::orderBy('name')->get();
        // Since reservations can be many, maybe just get recent or allow searching by ID directly in the frontend.
        // We'll pass an empty array for reservations dropdown, or maybe pass the last 100 for a combobox.
        $recentReservations = \App\Models\Reservation::orderBy('id', 'desc')->limit(200)->get()->map(function ($r) {
            return [
                'id' => $r->id,
                'label' => "Rev #" . $r->id . ($r->customer ? " - " . $r->customer->firstname : "")
            ];
        });

        $query = \App\Models\ReservationHistory::with(['user', 'reservation.customer'])
            ->orderBy('created_at', 'desc');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($reservationId) {
            $query->where('reservation_id', $reservationId);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $histories = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/Reports/Activity', [
            'histories' => $histories,
            'users' => $users,
            'recentReservations' => $recentReservations,
            'filters' => [
                'user_id' => $userId,
                'reservation_id' => $reservationId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ]
        ]);
    }
}
