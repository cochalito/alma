<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'document_type',
        'document_number',
        'firstname',
        'lastname',
        'email',
        'cellphone',
        'status',
    ];
}
