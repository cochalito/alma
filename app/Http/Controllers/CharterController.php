<?php

namespace App\Http\Controllers;

use App\Models\Departament;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CharterController extends Controller
{
    public function index(Request $request)
    {
        $location = $request->input('location', 'LP');

        $user = auth()->user();
        if ($user) {
            if (str_ends_with($user->role, '_LA_PAZ')) {
                $location = 'LP';
            } elseif (str_ends_with($user->role, '_UYUNI')) {
                $location = 'UYUNI';
            }
        }

        $date = $request->input('date', now()->toDateString());

        // Parse selected date and get the start and end of the week (Monday to Sunday)
        $selectedDate = Carbon::parse($date);
        $startOfWeek = $selectedDate->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $selectedDate->copy()->endOfWeek(Carbon::SUNDAY);

        // Get departments for the selected location
        $departments = Departament::where('location', $location)
            ->where('status', '1')
            ->orderBy('code')
            ->get();

        // Get reservations for those departments that overlap with this week
        $reservations = Reservation::with([
            'customer',
            'products' => function ($query) {
                $query->withPivot('quantity', 'unit_price', 'subtotal');
            }
        ])
            ->whereIn('departament_id', $departments->pluck('id'))
            ->where(function ($query) use ($startOfWeek, $endOfWeek) {
                $query->whereBetween('check_in', [$startOfWeek, $endOfWeek])
                    ->orWhereBetween('check_out', [$startOfWeek, $endOfWeek])
                    ->orWhere(function ($q2) use ($startOfWeek, $endOfWeek) {
                        $q2->where('check_in', '<=', $startOfWeek)
                            ->where('check_out', '>=', $endOfWeek);
                    });
            })
            ->get();

        // Generate the days of the week for the header
        $weekDays = [];
        for ($i = 0; $i < 7; $i++) {
            $day = $startOfWeek->copy()->addDays($i);
            $weekDays[] = [
                'date' => $day->toDateString(),
                'dayName' => $day->locale('es')->isoFormat('dddd'),
                'dayNumber' => $day->day,
                'isToday' => $day->isToday(),
            ];
        }

        return Inertia::render('Admin/Charter/Index', [
            'location' => $location,
            'date' => $date,
            'departments' => $departments,
            'reservations' => $reservations,
            'weekDays' => $weekDays,
            'startOfWeek' => $startOfWeek->toDateString(),
            'endOfWeek' => $endOfWeek->toDateString(),
            'employees' => \App\Models\User::all(),
            'products' => \App\Models\Product::where('is_active', true)->get(),
            'customers' => \App\Models\Customer::all(),
        ]);
    }
}
