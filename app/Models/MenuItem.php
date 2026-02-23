<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $fillable = [
        'restaurant_id',
        'category_id',
        'name',
        'description',
        'price',
        'is_available',
        'image',
    ];

    public function variations()
    {
        return $this->hasMany(\App\Models\ItemVariation::class, 'item_id');
    }

    public function addons()
    {
        return $this->hasMany(ItemAddon::class, 'item_id');
    }

    // Items where this item is used as addon
    public function usedAsAddon()
    {
        return $this->hasMany(ItemAddon::class, 'addon_item_id');
    }

}
