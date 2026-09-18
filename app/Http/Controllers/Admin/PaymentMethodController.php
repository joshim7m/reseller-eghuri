<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PaymentMethodController extends Controller
{
    public function index()
    {
        $paymentMethods = PaymentMethod::latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/PaymentMethods/Index', compact('paymentMethods'));
    }

    public function create()
    {
        return Inertia::render('Admin/PaymentMethods/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'nullable|string|max:255',
            'type' => ['required', 'in:withdrawal,ecommerce'],
            'account_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('payment_methods')->where(
                    fn ($query) => $query->where('type', $request->input('type')),
                ),
            ],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['image'] = $this->saveImage($request);

        PaymentMethod::create($validated);

        return redirect()->route('admin.payment-methods.index')
            ->with('success', 'Payment method added successfully.');
    }

    public function edit(PaymentMethod $paymentMethod)
    {
        return Inertia::render('Admin/PaymentMethods/Edit', compact('paymentMethod'));
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'provider' => 'nullable|string|max:255',
            'type' => ['required', 'in:withdrawal,ecommerce'],
            'account_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('payment_methods')
                    ->where(
                        fn ($query) => $query->where('type', $request->input('type')),
                    )
                    ->ignore($paymentMethod->id),
            ],
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'status' => 'required|in:active,inactive',
        ]);

        if ($request->hasFile('image')) {
            $this->deleteImage($paymentMethod);
            $validated['image'] = $this->saveImage($request);
        } else {
            unset($validated['image']);
        }

        $paymentMethod->update($validated);

        return redirect()->route('admin.payment-methods.index')
            ->with('success', 'Payment method updated successfully.');
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        $this->deleteImage($paymentMethod);
        $paymentMethod->delete();

        return redirect()->route('admin.payment-methods.index')
            ->with('success', 'Payment method deleted successfully.');
    }

    public function toggleStatus(PaymentMethod $paymentMethod)
    {
        $paymentMethod->update([
            'status' => $paymentMethod->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', 'Payment method status updated.');
    }

    private function saveImage(Request $request): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $file = $request->file('image');
        $filename = time().'_'.$file->getClientOriginalName();
        $file->move(public_path('images/payment-methods'), $filename);

        return 'images/payment-methods/'.$filename;
    }

    private function deleteImage(PaymentMethod $paymentMethod): void
    {
        if ($paymentMethod->image && file_exists(public_path($paymentMethod->image))) {
            unlink(public_path($paymentMethod->image));
        }
    }
}
