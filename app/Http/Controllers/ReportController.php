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

    public function kardex(Request $request)
    {
        $productId = $request->query('product_id');
        $location = $request->query('location');
        $period = $request->query('period', 'month');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $products = \App\Models\Product::orderBy('name')->get();
        $locations = Departament::select('location')->distinct()->pluck('location');

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
