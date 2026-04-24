<?php

namespace App\Models;

use Carbon\Carbon;
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
        'is_meal_plan',
        'plan_type',
        'plan_start_date',
        'plan_end_date',
        'days_per_week',
        'plan_total_days',
        'meal_plan_summary_json',
    ];

    protected $casts = [
        'is_meal_plan' => 'boolean',
        'plan_start_date' => 'date',
        'plan_end_date' => 'date',
        'meal_plan_summary_json' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getScheduledAtAttribute(): ?Carbon
    {
        $selectedDate = trim((string) ($this->selectedDate ?? ''));
        $selectedTime = trim((string) ($this->time ?? ''));

        if ($selectedDate === '') {
            return null;
        }

        try {
            return $selectedTime !== ''
                ? Carbon::parse($selectedDate . ' ' . $selectedTime)
                : Carbon::parse($selectedDate)->endOfDay();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getDisplayOrderAtAttribute(): Carbon
    {
        return $this->scheduled_at ?? $this->created_at;
    }

    public function getIsFutureScheduledAttribute(): bool
    {
        return (int) ($this->pre_order_status ?? 0) === 1
            && $this->scheduled_at !== null
            && $this->scheduled_at->greaterThan(now());
    }

}
