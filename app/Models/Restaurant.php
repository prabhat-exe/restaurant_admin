<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Crypt;
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
    ];

    public function posSystems()
    {
        return $this->hasMany(PosSystem::class);
    }

    public function getClientIdAttribute($value): ?string
    {
        return $this->decryptPosCredential($value);
    }

    public function setClientIdAttribute($value): void
    {
        $this->setEncryptedPosCredential('client_id', $value);
    }

    public function getPublicKeyAttribute($value): ?string
    {
        return $this->decryptPosCredential($value);
    }

    public function setPublicKeyAttribute($value): void
    {
        $this->setEncryptedPosCredential('public_key', $value);
    }

    public function getSecretKeyAttribute($value): ?string
    {
        return $this->decryptPosCredential($value);
    }

    public function setSecretKeyAttribute($value): void
    {
        $this->setEncryptedPosCredential('secret_key', $value);
    }

    private function decryptPosCredential(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }
    }

    private function setEncryptedPosCredential(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value === null || $value === ''
            ? $value
            : Crypt::encryptString((string) $value);
    }
}
