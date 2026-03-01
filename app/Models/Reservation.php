<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'employee_id',
        'departament_id',
        'customer_id',
        'location',
        'check_in',
        'check_out',
        'total_stay_cost',
        'total_extra_cost',
        'requests',
        'comments',
        'status',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function departament()
    {
        return $this->belongsTo(Departament::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'reservation_product')
            ->withPivot('quantity', 'unit_price', 'subtotal')
            ->withTimestamps();
    }
}
