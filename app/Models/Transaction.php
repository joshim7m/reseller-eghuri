<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'user_id',
        'reseller_order_id',
        'paymentmethod_name',
        'transaction_name',
        'amount',
        'type',
        'status',
        'note',
        'action_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resellerOrder(): BelongsTo
    {
        return $this->belongsTo(ResellerOrder::class);
    }
}
