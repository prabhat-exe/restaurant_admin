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
        'selected_variation_json',
        'addons_json',
        'is_meal',
        'is_meal_plan_item',
        'scheduled_date',
        'scheduled_time',
        'plan_day_number',
        'plan_week_number',
        'meal_slot',
    ];

    protected $casts = [
        'selected_variation_json' => 'array',
        'addons_json' => 'array',
        'is_meal_plan_item' => 'boolean',
        'scheduled_date' => 'date',
    ];
}
