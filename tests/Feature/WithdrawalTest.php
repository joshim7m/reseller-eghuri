<?php

use App\Models\Module;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\WithdrawalStatusChanged;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification as NotificationFacade;

function withdrawalReseller(): User
{
    $role = Role::firstOrCreate(['slug' => 'reseller'], ['name' => 'Reseller']);

    return User::create([
        'name' => 'Withdraw Reseller',
        'email' => uniqid().'withdraw-reseller@test.dev',
        'password' => Hash::make('password'),
        'role_id' => $role->id,
        'user_type' => 'reseller',
        'status' => true,
        'email_verified_at' => now(),
    ]);
}

function withdrawalAdmin(): User
{
    $module = Module::firstOrCreate(['name' => 'Orders'], ['name' => 'Orders']);
    $permission = Permission::firstOrCreate(['slug' => 'manage-orders'], ['name' => 'Manage Orders', 'module_id' => $module->id]);
    $role = Role::create(['name' => 'Withdraw Manager '.uniqid(), 'slug' => 'withdraw-manager-'.uniqid()]);
    $role->permissions()->sync([$permission->id]);

    return User::create([
        'name' => 'Withdraw Admin',
        'email' => uniqid().'withdraw-admin@test.dev',
        'password' => Hash::make('password'),
        'role_id' => $role->id,
        'user_type' => 'admin',
        'status' => true,
        'email_verified_at' => now(),
    ]);
}

function fundedWallet(User $user, float $balance = 1000): Wallet
{
    return Wallet::create(['user_id' => $user->id, 'credit' => $balance, 'debit' => 0, 'balance' => $balance]);
}

function ensureWithdrawalMethod(): PaymentMethod
{
    return PaymentMethod::firstOrCreate(
        ['type' => 'withdrawal', 'name' => 'Bkash'],
        [
            'provider' => 'Bkash Ltd',
            'account_number' => '017'.str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT),
            'status' => 'active',
        ]
    );
}

function withdrawalPayload(): array
{
    return [
        'amount' => 500,
        'payment_method' => ensureWithdrawalMethod()->name,
        'account_number' => '01712345678',
        'password' => 'password',
    ];
}

it('allows a reseller to request a withdrawal and holds the balance', function () {
    $reseller = withdrawalReseller();
    $wallet = fundedWallet($reseller, 1000);

    $this->actingAs($reseller)
        ->post(route('reseller-orders.withdraw'), withdrawalPayload())
        ->assertRedirect()
        ->assertSessionHas('success');

    $wallet->refresh();

    expect((float) $wallet->balance)->toBe(500.0);

    $transaction = Transaction::where('user_id', $reseller->id)->latest()->first();

    expect($transaction->transaction_name)->toBe('Withdrawal')
        ->and($transaction->paymentmethod_name)->toBe('Bkash')
        ->and($transaction->type)->toBe('debit')
        ->and($transaction->status)->toBe('pending')
        ->and($transaction->amount)->toBe('500.00')
        ->and($transaction->note)->toContain('01712345678');
});

it('rejects a second withdrawal within 24 hours', function () {
    $reseller = withdrawalReseller();
    fundedWallet($reseller, 5000);

    $this->actingAs($reseller)
        ->post(route('reseller-orders.withdraw'), withdrawalPayload())
        ->assertSessionHas('success');

    $this->actingAs($reseller)
        ->post(route('reseller-orders.withdraw'), withdrawalPayload())
        ->assertSessionHasErrors('amount');

    expect(Transaction::where('user_id', $reseller->id)->where('transaction_name', 'Withdrawal')->count())->toBe(1)
        ->and((float) $reseller->wallet->refresh()->balance)->toBe(4500.0);
});

it('allows a withdrawal after the 24 hour cooldown has passed', function () {
    $reseller = withdrawalReseller();
    fundedWallet($reseller, 5000);

    $this->actingAs($reseller)
        ->post(route('reseller-orders.withdraw'), withdrawalPayload());

    Transaction::where('user_id', $reseller->id)
        ->where('transaction_name', 'Withdrawal')
        ->update(['created_at' => now()->subHours(25)]);

    $this->actingAs($reseller)
        ->post(route('reseller-orders.withdraw'), withdrawalPayload())
        ->assertSessionHas('success');

    expect(Transaction::where('user_id', $reseller->id)->where('transaction_name', 'Withdrawal')->count())->toBe(2);
});

it('rejects a withdrawal amount below the minimum', function () {
    $reseller = withdrawalReseller();
    fundedWallet($reseller, 1000);

    $this->actingAs($reseller)
        ->post(route('reseller-orders.withdraw'), [...withdrawalPayload(), 'amount' => 499])
        ->assertSessionHasErrors('amount');
});

it('rejects withdrawal amounts above the wallet balance', function () {
    $reseller = withdrawalReseller();
    fundedWallet($reseller, 800);

    $this->actingAs($reseller)
        ->post(route('reseller-orders.withdraw'), [...withdrawalPayload(), 'amount' => 900])
        ->assertSessionHasErrors('amount');

    expect((float) $reseller->wallet->balance)->toBe(800.0)
        ->and(Transaction::where('user_id', $reseller->id)->count())->toBe(0);
});

it('rejects withdrawals without a valid password', function () {
    $reseller = withdrawalReseller();
    fundedWallet($reseller, 1000);

    $this->actingAs($reseller)
        ->post(route('reseller-orders.withdraw'), [...withdrawalPayload(), 'password' => 'wrong-password'])
        ->assertSessionHasErrors('password');

    expect(Transaction::where('user_id', $reseller->id)->count())->toBe(0);
});

it('forbids non-resellers from requesting withdrawals', function () {
    $role = Role::firstOrCreate(
        ['slug' => 'customer'],
        ['name' => 'Customer']
    );
    $customer = User::factory()->create(['user_type' => 'customer', 'status' => true, 'role_id' => $role->id]);

    $this->actingAs($customer)
        ->post(route('reseller-orders.withdraw'), withdrawalPayload())
        ->assertForbidden();
});

it('searches an account number requiring a mobile number format', function () {
    $reseller = withdrawalReseller();
    fundedWallet($reseller, 1000);

    $this->actingAs($reseller)
        ->post(route('reseller-orders.withdraw'), [...withdrawalPayload(), 'account_number' => '1234567890'])
        ->assertSessionHasErrors('account_number');

    expect(Transaction::where('user_id', $reseller->id)->count())->toBe(0);
});

it('admin can accept a pending withdrawal and record the debit', function () {
    $reseller = withdrawalReseller();
    $wallet = fundedWallet($reseller, 1000);
    NotificationFacade::fake();

    $this->actingAs($reseller)
        ->post(route('reseller-orders.withdraw'), withdrawalPayload());

    $transaction = Transaction::where('transaction_name', 'Withdrawal')->first();
    $wallet->refresh();

    NotificationFacade::assertNothingSentTo($reseller);

    $this->actingAs(withdrawalAdmin())
        ->post(route('admin.withdrawals.accept', ['transaction' => $transaction->id]))
        ->assertRedirect()
        ->assertSessionHas('success');

    NotificationFacade::assertSentTo($reseller, WithdrawalStatusChanged::class, function ($notification) {
        return $notification->status === 'accepted';
    });

    $transaction->refresh();
    $wallet->refresh();

    expect($transaction->status)->toBe('completed')
        ->and($transaction->action_by)->toBe('Withdraw Admin')
        ->and((float) $wallet->balance)->toBe(500.0)
        ->and((float) $wallet->debit)->toBe(500.0)
        ->and((float) $wallet->credit)->toBe(1000.0);
});

it('admin can reject a pending withdrawal and restore the balance', function () {
    $reseller = withdrawalReseller();
    $wallet = fundedWallet($reseller, 1000);
    NotificationFacade::fake();

    $this->actingAs($reseller)
        ->post(route('reseller-orders.withdraw'), withdrawalPayload());

    $transaction = Transaction::where('transaction_name', 'Withdrawal')->first();

    $this->actingAs(withdrawalAdmin())
        ->post(route('admin.withdrawals.reject', ['transaction' => $transaction->id]))
        ->assertRedirect()
        ->assertSessionHas('success');

    NotificationFacade::assertSentTo($reseller, WithdrawalStatusChanged::class, function ($notification) {
        return $notification->status === 'rejected';
    });

    $transaction->refresh();
    $wallet->refresh();

    expect($transaction->status)->toBe('cancelled')
        ->and((float) $wallet->balance)->toBe(1000.0)
        ->and((float) $wallet->debit)->toBe(0.0)
        ->and((float) $wallet->credit)->toBe(1000.0);
});

it('ignores action on an already completed withdrawal', function () {
    $reseller = withdrawalReseller();
    fundedWallet($reseller, 1000);

    $this->actingAs($reseller)
        ->post(route('reseller-orders.withdraw'), withdrawalPayload());

    $transaction = Transaction::where('transaction_name', 'Withdrawal')->first();

    $this->actingAs(withdrawalAdmin())
        ->post(route('admin.withdrawals.accept', ['transaction' => $transaction->id]));

    $transaction->refresh();
    $balanceAfter = (float) $reseller->wallet->refresh()->balance;
    $debitAfter = (float) $reseller->wallet->debit;

    $this->actingAs(withdrawalAdmin())
        ->post(route('admin.withdrawals.reject', ['transaction' => $transaction->id]));

    $transaction->refresh();

    expect($transaction->status)->toBe('completed')
        ->and((float) $reseller->wallet->refresh()->balance)->toBe($balanceAfter)
        ->and((float) $reseller->wallet->debit)->toBe($debitAfter);
});

it('shows withdrawal requests in the admin panel', function () {
    $reseller = withdrawalReseller();
    fundedWallet($reseller, 1000);

    $this->actingAs($reseller)
        ->post(route('reseller-orders.withdraw'), withdrawalPayload());

    $this->actingAs(withdrawalAdmin())
        ->get(route('admin.withdrawals.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Withdrawals/Index')
            ->has('withdrawals.data', 1)
            ->has('withdrawals.data.0.user')
            ->where('withdrawals.data.0.status', 'pending')
            ->where('pendingCount', 1)
        );
});

it('filters withdrawals by status in the admin panel', function () {
    $reseller = withdrawalReseller();
    fundedWallet($reseller, 2000);

    $this->actingAs($reseller)
        ->post(route('reseller-orders.withdraw'), withdrawalPayload());

    $transaction = Transaction::where('transaction_name', 'Withdrawal')->first();

    $this->actingAs(withdrawalAdmin())
        ->post(route('admin.withdrawals.reject', ['transaction' => $transaction->id]))
        ->assertSessionHas('success');

    $this->actingAs(withdrawalAdmin())
        ->get(route('admin.withdrawals.index', ['status' => 'pending']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Withdrawals/Index')
            ->has('withdrawals.data', 0)
        );
});

it('searches withdrawals by reseller name in the admin panel', function () {
    $reseller = withdrawalReseller();
    fundedWallet($reseller, 1000);

    $this->actingAs($reseller)
        ->post(route('reseller-orders.withdraw'), withdrawalPayload());

    $this->actingAs(withdrawalAdmin())
        ->get(route('admin.withdrawals.index', ['search' => 'withdraw']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('withdrawals.data', 1));

    $this->actingAs(withdrawalAdmin())
        ->get(route('admin.withdrawals.index', ['search' => 'no-match-name-xyz']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('withdrawals.data', 0));
});

it('searches withdrawals by mobile account number in the admin panel', function () {
    $reseller = withdrawalReseller();
    fundedWallet($reseller, 1000);

    $this->actingAs($reseller)
        ->post(route('reseller-orders.withdraw'), withdrawalPayload());

    $this->actingAs(withdrawalAdmin())
        ->get(route('admin.withdrawals.index', ['search' => '01712345678']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('withdrawals.data', 1));
});

it('only shows withdrawals from the last month by default', function () {
    $reseller = withdrawalReseller();
    fundedWallet($reseller, 1000);

    $this->actingAs($reseller)
        ->post(route('reseller-orders.withdraw'), withdrawalPayload());

    $transaction = Transaction::where('transaction_name', 'Withdrawal')->first();
    $transaction->timestamps = false;
    $transaction->created_at = now()->subMonths(2);
    $transaction->save();

    $this->actingAs(withdrawalAdmin())
        ->get(route('admin.withdrawals.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Withdrawals/Index')->has('withdrawals.data', 0));

    $this->actingAs(withdrawalAdmin())
        ->get(route('admin.withdrawals.index', ['from' => now()->subYear()->toDateString(), 'to' => now()->toDateString()]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Withdrawals/Index')->has('withdrawals.data', 1));
});

it('shows the withdrawals report within the date range', function () {
    $reseller = withdrawalReseller();
    fundedWallet($reseller, 1000);

    $this->actingAs($reseller)
        ->post(route('reseller-orders.withdraw'), withdrawalPayload());

    $this->actingAs(withdrawalAdmin())
        ->get(route('admin.withdrawals.report', ['from' => now()->subWeek()->toDateString(), 'to' => now()->toDateString()]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Withdrawals/Report')
            ->has('withdrawals', 1)
            ->where('withdrawals.0.status', 'pending')
            ->where('withdrawals.0.reseller_name', 'Withdraw Reseller')
        );

    $this->actingAs(withdrawalAdmin())
        ->get(route('admin.withdrawals.report', ['from' => now()->subYears(2)->toDateString(), 'to' => now()->subYear()->toDateString()]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/Withdrawals/Report')->has('withdrawals', 0));
});

it('exports the withdrawals report as an excel file', function () {
    $reseller = withdrawalReseller();
    fundedWallet($reseller, 1000);

    $this->actingAs($reseller)
        ->post(route('reseller-orders.withdraw'), withdrawalPayload());

    $this->actingAs(withdrawalAdmin())
        ->get(route('admin.withdrawals.export-report', ['from' => now()->subWeek()->toDateString(), 'to' => now()->toDateString()]))
        ->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});
