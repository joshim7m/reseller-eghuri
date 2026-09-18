<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount('permissions')->get();

        return Inertia::render('Admin/Roles/Index', compact('roles'));
    }

    public function edit(Role $role)
    {
        $modules = Module::with('permissions')->get();
        $role->load('permissions');

        return Inertia::render('Admin/Roles/Edit', compact('role', 'modules'));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $role->permissions()->sync($validated['permissions'] ?? []);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Permissions updated for "'.$role->name.'".');
    }
}
