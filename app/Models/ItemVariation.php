<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemVariation extends Model
{
    protected $fillable = [
        'menu_item_id',
        'variation_name',
        'price'
    ];

}
