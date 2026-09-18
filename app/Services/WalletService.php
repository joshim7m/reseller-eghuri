<?php

namespace App\Services;

use App\Models\ResellerOrder;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\WithdrawalStatusChanged;
use Carbon\Carbon;
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

    public function withdrawalAvailableAt(User $user): ?Carbon
    {
        $lastWithdrawal = Transaction::where('user_id', $user->id)
            ->where('transaction_name', 'Withdrawal')
            ->latest()
            ->first();

        if ($lastWithdrawal && $lastWithdrawal->created_at?->isAfter(now()->subDay())) {
            return $lastWithdrawal->created_at->addDay();
        }

        return null;
    }

    public function requestWithdrawal(User $user, float $amount, string $paymentMethod, string $accountNumber): Transaction
    {
        $availableAt = $this->withdrawalAvailableAt($user);

        if ($availableAt) {
            $minutes = (int) ceil(now()->diffInMinutes($availableAt));

            abort(422, sprintf(
                'You can request one withdrawal every 24 hours. Your next withdrawal will be available in %d minute(s).',
                max($minutes, 1),
            ));
        }

        return DB::transaction(function () use ($user, $amount, $paymentMethod, $accountNumber) {
            $wallet = Wallet::where('user_id', $user->id)->lockForUpdate()->first();

            if (! $wallet || $wallet->balance < $amount) {
                abort(422, 'Insufficient wallet balance.');
            }

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'paymentmethod_name' => $paymentMethod,
                'transaction_name' => 'Withdrawal',
                'amount' => $amount,
                'type' => 'debit',
                'status' => 'pending',
                'note' => "Withdraw to {$paymentMethod} {$accountNumber}",
            ]);

            $wallet->update(['transaction_id' => $transaction->id]);
            $wallet->decrement('balance', $amount);

            return $transaction;
        });
    }

    public function acceptWithdrawal(Transaction $transaction): void
    {
        if ($transaction->transaction_name !== 'Withdrawal' || $transaction->status !== 'pending') {
            return;
        }

        DB::transaction(function () use ($transaction) {
            $transaction->update([
                'status' => 'completed',
                'action_by' => auth()->user()->name ?? null,
            ]);

            $wallet = Wallet::where('user_id', $transaction->user_id)
                ->lockForUpdate()
                ->first();

            if ($wallet) {
                $wallet->update(['transaction_id' => $transaction->id]);
                $wallet->increment('debit', $transaction->amount);
            }
        });

        $transaction->user?->notify(new WithdrawalStatusChanged($transaction, 'accepted'));
    }

    public function rejectWithdrawal(Transaction $transaction): void
    {
        if ($transaction->transaction_name !== 'Withdrawal' || $transaction->status !== 'pending') {
            return;
        }

        DB::transaction(function () use ($transaction) {
            $transaction->update([
                'status' => 'cancelled',
                'action_by' => auth()->user()->name ?? null,
            ]);

            $wallet = Wallet::where('user_id', $transaction->user_id)
                ->lockForUpdate()
                ->first();

            if ($wallet) {
                $wallet->update(['transaction_id' => $transaction->id]);
                $wallet->increment('balance', $transaction->amount);
            }
        });

        $transaction->user?->notify(new WithdrawalStatusChanged($transaction, 'rejected'));
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
