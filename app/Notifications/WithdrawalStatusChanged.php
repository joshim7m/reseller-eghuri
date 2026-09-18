<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WithdrawalStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Transaction $transaction,
        public string $status,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $amount = '৳'.number_format((float) $this->transaction->amount, 2);

        return [
            'title' => $this->status === 'accepted'
                ? 'Withdrawal Approved'
                : 'Withdrawal Rejected',
            'message' => $this->status === 'accepted'
                ? "Your withdrawal of {$amount} has been approved and processed."
                : "Your withdrawal of {$amount} was rejected and the amount has been returned to your wallet.",
            'withdrawal_id' => $this->transaction->id,
            'amount' => $this->transaction->amount,
            'payment_method' => $this->transaction->paymentmethod_name,
            'status' => $this->status,
            'link' => route('reseller-transactions'),
            'icon' => $this->status === 'accepted' ? 'payments' : 'undo',
        ];
    }
}
