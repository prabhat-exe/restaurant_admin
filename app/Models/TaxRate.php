<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    protected $fillable = [
        'tax_class_id',
        'tax_name',
        'tax_amount'
    ];

}
