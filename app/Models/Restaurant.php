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
        'delivery_radius_km',
        'country_currency',
        'postal_code',
        'cook_time',
        'country_id',
        'rating',
        'rating_count',
        'is_pos',
        'menu_url',
        'client_id',
        'public_key',
        'secret_key',
        'last_synced_at',
        'last_sync_status',
        'last_sync_error',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_pos' => 'boolean',
        'is_active' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'delivery_radius_km' => 'decimal:2',
        'last_synced_at' => 'datetime',
        'client_id' => 'encrypted',
        'public_key' => 'encrypted',
        'secret_key' => 'encrypted',
    ];

    public function posSystems()
    {
        return $this->hasMany(PosSystem::class);
    }
}
