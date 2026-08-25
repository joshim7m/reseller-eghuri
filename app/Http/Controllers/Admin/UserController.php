<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        $users = User::whereIn('user_type', ['admin', 'manager'])
            ->with('role')
            ->latest()
            ->paginate(15);

        return Inertia::render('Admin/Users/Index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();

        return Inertia::render('Admin/Users/Create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role_id' => 'required|integer|exists:roles,id',
            'user_type' => 'required|in:admin,manager,customer,reseller,wholeseller',
            'status' => 'required|boolean',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
            'user_type' => $validated['user_type'],
            'status' => $request->boolean('status'),
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User "'.$validated['name'].'" created successfully.');
    }

    public function customers(Request $request)
    {
        $query = User::where('user_type', 'customer')
            ->with('userDetail');

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        if ($sortBy === 'user_type') {
            $query->orderBy('user_type', $sortOrder);
        } elseif ($sortBy === 'status') {
            $query->orderBy('status', $sortOrder);
        } else {
            $query->latest();
        }

        $customers = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Users/Customers', compact('customers', 'sortBy', 'sortOrder'));
    }

    public function edit(User $user)
    {
        $roles = Role::all();

        return Inertia::render('Admin/Users/Edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'role_id' => 'required|integer|exists:roles,id',
            'user_type' => 'required|in:admin,manager,customer,reseller,wholeseller',
            'status' => 'required|boolean',
            'password' => 'nullable|string|min:8',
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role_id' => $validated['role_id'],
            'user_type' => $validated['user_type'],
            'status' => $request->boolean('status'),
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'User "'.$user->name.'" updated successfully.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}
