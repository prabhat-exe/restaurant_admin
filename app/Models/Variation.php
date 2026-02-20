<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Variation extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'variation_name',
        'created_by_super_admin',
        'department_id',
    ];

    public function itemVariations()
    {
        return $this->hasMany(ItemVariation::class);
    }
}
