<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosSystem extends Model
{
    protected $fillable = [
        'restaurant_id',
        'name',
        'menu_url',
        'client_id',
        'public_key',
        'secret_key',
        'restaurant_email',
        'restaurant_password',
        'is_active',
        'last_synced_at',
        'last_sync_status',
        'last_sync_error',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
        'client_id' => 'encrypted',
        'public_key' => 'encrypted',
        'secret_key' => 'encrypted',
        'restaurant_email' => 'encrypted',
        'restaurant_password' => 'encrypted',
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }
}
