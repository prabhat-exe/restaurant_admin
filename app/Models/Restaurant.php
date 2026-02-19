<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Restaurant extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'address',
        'city',
        'state',
        'pincode',
        'country',
        'logo',
        'status',
        'razorpay_account_id',
        'store_type',
        'store_url',
        'short_description',
        'description',
        'latitude',
        'longitude',
        'country_currency',
        'postal_code',
        'cook_time',
        'country_id',
        'rating',
        'rating_count',
    ];

    protected $hidden = [
        'password',
    ];
}
