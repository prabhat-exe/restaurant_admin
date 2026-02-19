<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxClass extends Model
{
    protected $fillable = [
        'restaurant_id',
        'tax_class_name',
        'type'
    ];

}
