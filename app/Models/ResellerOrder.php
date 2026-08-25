<?php

namespace App\Models;

use Database\Factories\ResellerOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResellerOrder extends Model
{
    /** @use HasFactory<ResellerOrderFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_name',
        'mobile',
        'order_number',
        'status',
        'total_amount',
        'delivery_charge',
        'shipping_address',
        'payment_method',
        'payment_status',
        'notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ResellerOrderItem::class, 'reseller_order_id');
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class);
    }
}
