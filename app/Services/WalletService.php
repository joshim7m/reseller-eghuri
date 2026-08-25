<?php

namespace App\Services;

use App\Models\ResellerOrder;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class WalletService
{
    public function createPendingTransaction(ResellerOrder $order): void
    {
        $profit = $order->items->sum(function ($item) {
            return ($item->sale_price - $item->unit_price) * $item->quantity;
        });

        if ($profit <= 0) {
            return;
        }

        Transaction::create([
            'user_id' => $order->user_id,
            'reseller_order_id' => $order->id,
            'paymentmethod_name' => $order->payment_method,
            'transaction_name' => 'Reseller Order',
            'amount' => $profit,
            'type' => 'credit',
            'status' => 'pending',
            'note' => "Profit from order {$order->order_number}",
            'action_by' => null,
        ]);
    }

    public function completeTransaction(ResellerOrder $order): void
    {
        $transaction = Transaction::where('reseller_order_id', $order->id)
            ->where('status', 'pending')
            ->first();

        if (! $transaction) {
            return;
        }

        DB::transaction(function () use ($transaction, $order) {
            $transaction->update([
                'status' => 'completed',
                'action_by' => auth()->user()->name ?? null,
            ]);

            $wallet = Wallet::firstOrCreate(
                ['user_id' => $order->user_id],
                ['credit' => 0, 'debit' => 0, 'balance' => 0]
            );

            $wallet->update(['transaction_id' => $transaction->id]);
            $wallet->increment('balance', $transaction->amount);
            $wallet->increment('credit', $transaction->amount);
        });
    }

    public function cancelTransaction(ResellerOrder $order): void
    {
        $transaction = Transaction::where('reseller_order_id', $order->id)
            ->whereIn('status', ['pending', 'completed'])
            ->first();

        if (! $transaction) {
            return;
        }

        DB::transaction(function () use ($transaction) {
            $wasCompleted = $transaction->status === 'completed';

            $transaction->update([
                'status' => 'cancelled',
                'action_by' => auth()->user()->name ?? null,
            ]);

            if ($wasCompleted) {
                $wallet = Wallet::where('user_id', $transaction->user_id)->first();

                if ($wallet) {
                    $wallet->update(['transaction_id' => $transaction->id]);
                    $wallet->decrement('balance', $transaction->amount);
                    $wallet->decrement('credit', $transaction->amount);
                }
            }
        });
    }
}
