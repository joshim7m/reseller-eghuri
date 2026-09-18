<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class WithdrawalController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->get('status'), ['pending', 'completed', 'cancelled'])
            ? $request->get('status')
            : null;

        $from = $request->date('from') ? $request->date('from')->startOfDay() : now()->subMonth()->startOfDay();
        $to = $request->date('to') ? $request->date('to')->endOfDay() : now()->endOfDay();

        $withdrawals = Transaction::where('transaction_name', 'Withdrawal')
            ->whereBetween('created_at', [$from, $to])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->get('search');

                $query->where(function ($q) use ($search) {
                    $q->whereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"))
                        ->orWhere('note', 'like', "%{$search}%");
                });
            })
            ->with('user')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $pendingCount = DB::table('transactions')
            ->where('transaction_name', 'Withdrawal')
            ->where('status', 'pending')
            ->count();

        return Inertia::render('Admin/Withdrawals/Index', compact('withdrawals', 'pendingCount'));
    }

    public function accept(Transaction $transaction)
    {
        if ($transaction->transaction_name !== 'Withdrawal') {
            abort(404);
        }

        app(WalletService::class)->acceptWithdrawal($transaction);

        return back()->with('success', 'Withdrawal accepted.');
    }

    public function reject(Transaction $transaction)
    {
        if ($transaction->transaction_name !== 'Withdrawal') {
            abort(404);
        }

        app(WalletService::class)->rejectWithdrawal($transaction);

        return back()->with('success', 'Withdrawal rejected. Amount restored to wallet.');
    }

    public function report(Request $request)
    {
        [$from, $to] = $this->reportRange($request);

        $withdrawals = Transaction::where('transaction_name', 'Withdrawal')
            ->whereBetween('created_at', [$from, $to])
            ->with('user')
            ->latest()
            ->get()
            ->map(fn (Transaction $transaction) => [
                ...$transaction->only(['id', 'amount', 'status', 'note', 'action_by', 'created_at']),
                'paymentmethod_name' => $transaction->paymentmethod_name,
                'reseller_name' => $transaction->user?->name ?? 'N/A',
                'reseller_email' => $transaction->user?->email,
            ]);

        return Inertia::render('Admin/Withdrawals/Report', [
            'withdrawals' => $withdrawals,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    public function exportReport(Request $request)
    {
        [$from, $to] = $this->reportRange($request);

        $query = Transaction::where('transaction_name', 'Withdrawal')->whereBetween('created_at', [$from, $to]);

        if ($request->filled('ids')) {
            $query->whereIn('id', (array) $request->input('ids'));
        }

        $withdrawals = $query->with('user')->latest()->get();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Withdrawal Report');

        $headers = ['Date', 'Reseller', 'Email', 'Method', 'Account Number', 'Amount', 'Status', 'Action By'];
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;
        foreach ($withdrawals as $withdrawal) {
            $accountNumber = preg_match('/(\d{6,})/', $withdrawal->note ?? '', $matches) ? $matches[1] : '';

            $sheet->fromArray([
                $withdrawal->created_at?->format('Y-m-d H:i'),
                $withdrawal->user?->name ?? 'N/A',
                $withdrawal->user?->email ?? '',
                $withdrawal->paymentmethod_name,
                $accountNumber,
                (float) $withdrawal->amount,
                $withdrawal->status,
                $withdrawal->action_by ?? '',
            ], null, "A{$row}");

            $sheet->getCell("E{$row}")->setValueExplicit($accountNumber, DataType::TYPE_STRING);

            $row++;
        }

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'withdrawal-report-'.now()->format('Y-m-d').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function reportRange(Request $request): array
    {
        $from = $request->date('from') ? $request->date('from')->startOfDay() : now()->subMonth()->startOfDay();
        $to = $request->date('to') ? $request->date('to')->endOfDay() : now()->endOfDay();

        return [$from, $to];
    }
}
