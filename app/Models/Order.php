<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Order extends Model
{
    use SoftDeletes;

    protected $table = 'orders';

    protected $primaryKey = 'order_item_id';

    protected $fillable = [
        'order_id',
        'token_number',
        'user_id',
        'store_id',
        'table_no',
        'print_status',
        'delivery_address',
        'address_lat',
        'address_long',
        'distance',
        'delivery_charges',
        'service_charge',
        'selectedDate',
        'time',
        'transaction_id',
        'ip_address',
        'store_name',
        'order_status',
        'order_type',
        'order_category',
        'total_quantity',
        'total_price',
        'total_tax',
        'discount',
        'tip',
        'payment_method',
        'order_comments',
        'pre_order_status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

}
