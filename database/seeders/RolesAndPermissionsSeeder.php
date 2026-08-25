<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $modules = Module::upsert(
            [
                ['name' => 'Catalog'],
                ['name' => 'Orders'],
                ['name' => 'Users'],
                ['name' => 'Settings'],
                ['name' => 'Content'],
            ],
            ['name'],
            ['name']
        );

        $moduleIds = Module::pluck('id', 'name');

        $permissions = [
            ['name' => 'Manage Catalog', 'module_id' => $moduleIds['Catalog'], 'slug' => 'manage-catalog'],
            ['name' => 'View Orders', 'module_id' => $moduleIds['Orders'], 'slug' => 'view-orders'],
            ['name' => 'Manage Orders', 'module_id' => $moduleIds['Orders'], 'slug' => 'manage-orders'],
            ['name' => 'Checkout Order', 'module_id' => $moduleIds['Orders'], 'slug' => 'checkout-order'],
            ['name' => 'Manage Users', 'module_id' => $moduleIds['Users'], 'slug' => 'manage-users'],
            ['name' => 'Manage Settings', 'module_id' => $moduleIds['Settings'], 'slug' => 'manage-settings'],
            ['name' => 'Manage Content', 'module_id' => $moduleIds['Content'], 'slug' => 'manage-content'],
        ];

        Permission::upsert($permissions, ['slug'], ['name', 'module_id']);

        $admin = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
        $manager = Role::firstOrCreate(['slug' => 'manager'], ['name' => 'Manager']);
        $customer = Role::firstOrCreate(['slug' => 'customer'], ['name' => 'Customer']);

        $admin->permissions()->sync(Permission::pluck('id'));
        $manager->permissions()->sync(Permission::whereIn('slug', [
            'manage-catalog',
            'view-orders',
            'manage-orders',
            'manage-content',
        ])->pluck('id'));
        $customer->permissions()->sync(Permission::where('slug', 'checkout-order')->pluck('id'));
    }
}
