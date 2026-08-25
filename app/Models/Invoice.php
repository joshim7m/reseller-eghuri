<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'invoice_number',
        'invoice_date',
        'payment_method',
        'payment_status',
        'total_amount',
        'due_amount',
        'paid_amount',
        'pdf_path',
        'shipping_address',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
