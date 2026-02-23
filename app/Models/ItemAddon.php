<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemAddon extends Model
{
    protected $fillable = [
        'item_id',
        'addon_item_id',
        'user_id',
        'pos_price',
        'web_price',
        'mobile_price'
    ];


    // Main Item
    public function item()
    {
        return $this->belongsTo(MenuItem::class, 'item_id');
    }

    // Addon Item
    public function addonItem()
    {
        return $this->belongsTo(MenuItem::class, 'addon_item_id');
    }
}