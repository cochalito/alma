<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReservationPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'amount',
        'payment_method',
        'description',
    ];

    public function reservation()
    {
        return $this->belongsTo(Reservation::class);
    }
}
