<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductLocation extends Model
{
    protected $fillable = [
        'product_id',
        'location',
        'stock',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
