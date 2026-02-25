<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReservationController extends Controller
{
    public function index()
    {
        $reservations = Reservation::with(['user', 'room'])->paginate(10);
        return Inertia::render('Admin/Reservations/Index', [
            'reservations' => $reservations
        ]);
    }

    public function create()
    {
        $rooms = Room::all();
        $users = User::all();

        return Inertia::render('Admin/Reservations/Create', [
            'rooms' => $rooms,
            'users' => $users
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:rooms,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'status' => 'required|in:pending,active,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $room = Room::findOrFail($validated['room_id']);

        $checkIn = \Carbon\Carbon::parse($validated['check_in']);
        $checkOut = \Carbon\Carbon::parse($validated['check_out']);
        $totalNights = $checkIn->diffInDays($checkOut);

        $validated['total_nights'] = $totalNights;
        $validated['total_amount'] = $totalNights * $room->price_per_night;

        Reservation::create($validated);

        return redirect()->route('reservations.index')->with('success', 'Reservación creada exitosamente.');
    }

    public function show(Reservation $reservation)
    {
        $reservation->load(['user', 'room', 'products']);
        return Inertia::render('Admin/Reservations/Show', [
            'reservation' => $reservation
        ]);
    }

    public function edit(Reservation $reservation)
    {
        $rooms = Room::all();
        $users = User::all();
        $reservation->load(['user', 'room', 'products']);

        return Inertia::render('Admin/Reservations/Edit', [
            'reservation' => $reservation,
            'rooms' => $rooms,
            'users' => $users
        ]);
    }

    public function update(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'room_id' => 'required|exists:rooms,id',
            'check_in' => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'status' => 'required|in:pending,active,completed,cancelled',
            'notes' => 'nullable|string',
        ]);

        $room = Room::findOrFail($validated['room_id']);

        $checkIn = \Carbon\Carbon::parse($validated['check_in']);
        $checkOut = \Carbon\Carbon::parse($validated['check_out']);
        $totalNights = $checkIn->diffInDays($checkOut);

        $validated['total_nights'] = $totalNights;
        $validated['total_amount'] = $totalNights * $room->price_per_night;

        $reservation->update($validated);

        return redirect()->route('reservations.index')->with('success', 'Reservación actualizada exitosamente.');
    }

    public function destroy(Reservation $reservation)
    {
        $reservation->delete();

        return redirect()->route('reservations.index')->with('success', 'Reservación eliminada exitosamente.');
    }
}
