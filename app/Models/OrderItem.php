<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = 'order_items';

    protected $fillable = [
        'order_id',
        'item_id',
        'user_id',
        'store_id',
        'attachment_id',
        'attachment_url',
        'item_type',
        'category_name',
        'menu_name',
        'item_name',
        'price',
        'total_price',
        'quantity',
        'short_description',
        'description',
        'status',
        'order_status',
        'notes',
        'customize_status',
        'addon_status',
        'is_meal',
    ];
}
