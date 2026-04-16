<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\ReservationPayment;
use Illuminate\Http\Request;

class ReservationPaymentController extends Controller
{
    public function store(Request $request, Reservation $reservation)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:EFECTIVO,QR,TARJETA',
            'description' => 'nullable|string|max:255',
        ]);

        // Calculate current balance
        $totalCost = $reservation->total_stay_cost + $reservation->total_extra_cost;
        $totalPaid = $reservation->payments()->sum('amount');
        $balance = $totalCost - $totalPaid;

        if ($validated['amount'] > ($balance + 0.01)) { // Allow 0.01 tolerance for rounding
            return back()->withErrors(['amount' => 'El monto del pago no puede exceder el saldo pendiente (Bs. ' . number_format($balance, 2) . ').']);
        }

        $reservation->payments()->create($validated);

        return back()->with('success', 'Pago registrado correctamente.');
    }

    public function destroy(ReservationPayment $payment)
    {
        $payment->delete();

        return back()->with('success', 'Pago eliminado correctamente.');
    }
}
