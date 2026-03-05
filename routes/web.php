<?php

use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\CharterController;
use App\Http\Controllers\UserController;
use App\Models\Reservation;
use App\Models\Departament;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }
    return redirect('/login');
})->name('home');

Route::get('dashboard', function () {
    $today = Carbon::today();
    $user = auth()->user();
    $allowedLocation = null;
    if ($user) {
        if (str_ends_with($user->role, '_LA_PAZ')) {
            $allowedLocation = 'LP';
        } elseif (str_ends_with($user->role, '_UYUNI')) {
            $allowedLocation = 'UYUNI';
        }
    }

    $checkInsToday = [
        'LP' => Reservation::whereDate('check_in', $today)->where('location', 'LP')->count(),
        'UYUNI' => Reservation::whereDate('check_in', $today)->where('location', 'UYUNI')->count(),
    ];

    $checkOutsToday = [
        'LP' => Reservation::whereDate('check_out', $today)->where('location', 'LP')->count(),
        'UYUNI' => Reservation::whereDate('check_out', $today)->where('location', 'UYUNI')->count(),
    ];

    $occupiedDepartmentsToday = [
        'LP' => Reservation::whereDate('check_in', '<=', $today)
            ->whereDate('check_out', '>', $today)
            ->whereIn('status', ['1', '2'])
            ->where('location', 'LP')
            ->distinct('departament_id')
            ->count('departament_id'),
        'UYUNI' => Reservation::whereDate('check_in', '<=', $today)
            ->whereDate('check_out', '>', $today)
            ->whereIn('status', ['1', '2'])
            ->where('location', 'UYUNI')
            ->distinct('departament_id')
            ->count('departament_id'),
    ];

    $totalActiveDepartmentsLP = Departament::where('status', '1')->where('location', 'LP')->count();
    $totalActiveDepartmentsUyuni = Departament::where('status', '1')->where('location', 'UYUNI')->count();

    $freeDepartmentsToday = [
        'LP' => max(0, $totalActiveDepartmentsLP - $occupiedDepartmentsToday['LP']),
        'UYUNI' => max(0, $totalActiveDepartmentsUyuni - $occupiedDepartmentsToday['UYUNI']),
    ];

    $recentResQuery = Reservation::with(['departament', 'customer'])
        ->orderBy('updated_at', 'desc')
        ->limit(10);

    if ($allowedLocation) {
        $recentResQuery->where('location', $allowedLocation);
    }

    $recentReservations = $recentResQuery
        ->get()
        ->map(function ($res) {
            return [
                'id' => $res->id,
                'location' => $res->location,
                'departament_code' => $res->departament->code ?? 'N/A',
                'customer_name' => $res->customer ? ($res->customer->firstname . ' ' . $res->customer->lastname) : 'N/A',
                'check_in' => $res->check_in,
                'check_out' => $res->check_out,
                'total_cost' => $res->total_stay_cost + $res->total_extra_cost,
                'status' => $res->status,
                'updated_at' => $res->updated_at->toIso8601String(),
            ];
        });

    $startDate = $today->copy()->subDays(9)->format('Y-m-d');
    $endDate = $today->format('Y-m-d');

    $allCheckIns = Reservation::whereDate('check_in', '>=', $startDate)
        ->whereDate('check_in', '<=', $endDate)
        ->get()
        ->groupBy(function ($res) {
            return Carbon::parse($res->check_in)->format('Y-m-d');
        });

    $allCheckOuts = Reservation::whereDate('check_out', '>=', $startDate)
        ->whereDate('check_out', '<=', $endDate)
        ->get()
        ->groupBy(function ($res) {
            return Carbon::parse($res->check_out)->format('Y-m-d');
        });

    $chartDates = collect(range(9, 0))->map(function ($days) use ($today) {
        return $today->copy()->subDays($days)->format('Y-m-d');
    });

    $baseSeries = [
        [
            'name' => 'Check-In LP',
            'location' => 'LP',
            'data' => $chartDates->map(fn($date) => isset($allCheckIns[$date]) ? $allCheckIns[$date]->where('location', 'LP')->count() : 0)->toArray()
        ],
        [
            'name' => 'Check-Out LP',
            'location' => 'LP',
            'data' => $chartDates->map(fn($date) => isset($allCheckOuts[$date]) ? $allCheckOuts[$date]->where('location', 'LP')->count() : 0)->toArray()
        ],
        [
            'name' => 'Check-In UYUNI',
            'location' => 'UYUNI',
            'data' => $chartDates->map(fn($date) => isset($allCheckIns[$date]) ? $allCheckIns[$date]->where('location', 'UYUNI')->count() : 0)->toArray()
        ],
        [
            'name' => 'Check-Out UYUNI',
            'location' => 'UYUNI',
            'data' => $chartDates->map(fn($date) => isset($allCheckOuts[$date]) ? $allCheckOuts[$date]->where('location', 'UYUNI')->count() : 0)->toArray()
        ]
    ];

    if ($allowedLocation) {
        $baseSeries = array_filter($baseSeries, fn($s) => $s['location'] === $allowedLocation);
        // Remove location key
        $baseSeries = array_map(function($s) { unset($s['location']); return $s; }, array_values($baseSeries));

        // Remove other location from stats
        $otherLoc = $allowedLocation === 'LP' ? 'UYUNI' : 'LP';
        unset($checkInsToday[$otherLoc]);
        unset($checkOutsToday[$otherLoc]);
        unset($occupiedDepartmentsToday[$otherLoc]);
        unset($freeDepartmentsToday[$otherLoc]);
    } else {
        $baseSeries = array_map(function($s) { unset($s['location']); return $s; }, $baseSeries);
    }

    $chartData = [
        'categories' => $chartDates->map(fn($date) => Carbon::parse($date)->format('d M'))->toArray(),
        'series' => $baseSeries
    ];

    return Inertia::render('Dashboard', [
        'stats' => [
            'checkInsToday' => $checkInsToday,
            'checkOutsToday' => $checkOutsToday,
            'occupiedDepartmentsToday' => $occupiedDepartmentsToday,
            'freeDepartmentsToday' => $freeDepartmentsToday,
        ],
        'recentReservations' => $recentReservations,
        'chartData' => $chartData
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified', \App\Http\Middleware\CheckRole::class])->prefix('admin')->group(function () {
    Route::resource('users', UserController::class);
    Route::resource('departaments', \App\Http\Controllers\DepartamentController::class);
    Route::post('customers/quick', [CustomerController::class, 'quickStore'])->name('customers.quick');
    Route::resource('customers', CustomerController::class);
    Route::post('products/stock-adjustment', [ProductController::class, 'stockAdjustment'])->name('products.stock-adjustment');
    Route::resource('products', ProductController::class);
    Route::resource('reservations', ReservationController::class);
    Route::get('charter', [CharterController::class, 'index'])->name('charter.index');
    Route::get('reports/reservations', [\App\Http\Controllers\ReportController::class, 'reservations'])->name('reports.reservations');
    Route::get('reports/kardex', [\App\Http\Controllers\ReportController::class, 'kardex'])->name('reports.kardex');
    Route::get('reports/activity', [\App\Http\Controllers\ReportController::class, 'activity'])->name('reports.activity');
});

require __DIR__ . '/settings.php';
