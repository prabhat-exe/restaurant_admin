<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemVariation extends Model
{
    protected $fillable = [
        'item_id',
        'user_id',
        'variation_id',
        'variation_price',
        'web_price',
        'mobile_price',
    ];

    public function variation()
    {
        return $this->belongsTo(Variation::class);
    }

}
