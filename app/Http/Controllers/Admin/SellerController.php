<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class SellerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::whereIn('user_type', ['reseller', 'wholeseller'])
            ->with('userDetail');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('userDetail', fn ($cq) => $cq->where('mobile', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->boolean('status'));
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        if ($sortBy === 'user_type') {
            $query->orderBy('user_type', $sortOrder);
        } elseif ($sortBy === 'status') {
            $query->orderBy('status', $sortOrder);
        } else {
            $query->latest();
        }

        $sellers = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Sellers/Index', compact('sellers', 'sortBy', 'sortOrder'));
    }

    public function edit(User $user)
    {
        $user->load('userDetail');

        return Inertia::render('Admin/Sellers/Edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'mobile' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'user_type' => 'required|in:reseller,wholeseller',
            'status' => 'required|boolean',
            'password' => 'nullable|string|min:8',
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'user_type' => $validated['user_type'],
            'status' => $request->boolean('status'),
        ];

        if ($validated['password']) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        UserDetail::updateOrCreate(
            ['user_id' => $user->id],
            [
                'mobile' => $validated['mobile'],
                'company' => $validated['company'],
                'address' => $validated['address'],
            ],
        );

        return redirect()->route('admin.sellers.index')
            ->with('success', 'Seller "'.$user->name.'" updated successfully.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('admin.sellers.index')
            ->with('success', 'Seller deleted successfully.');
    }
}
