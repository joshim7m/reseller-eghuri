<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::create([
            'role_id' => 1,
            'user_type' => 'admin',
            'name' => 'Sajib Hossain',
            'email' => 'sajibhossain523@gmail.com',
            'password' => bcrypt('Back28space@'),
            'status' => 1,
        ]);

        User::create([
            'role_id' => 1,
            'user_type' => 'admin',
            'name' => 'Joshim Uddin',
            'email' => 'joshimfv@gmail.com',
            'password' => bcrypt('histacin'),
            'status' => 1,
        ]);

        User::create([
            'role_id' => 2,
            'user_type' => 'manager',
            'name' => 'Manager User',
            'email' => 'manager@exe.com',
            'password' => bcrypt('histacin'),
            'status' => 1,
        ]);

        $sellers = [
            ['name' => 'Hasan',       'email' => 'hasan@example.com', 'user_type' => 'reseller',  'company' => 'Rahim Enterprise',  'mobile' => '01735365488', 'address' => '42 Gulshan Avenue, Dhaka 1212'],
            ['name' => 'Rasel Hasan',   'email' => 'rasel@example.com',  'user_type' => 'reseller',  'company' => 'Karim Traders',      'mobile' => '01912345678', 'address' => '15 Banani Road, Dhaka 1213'],
            ['name' => 'Fatima Begum',    'email' => 'fatima@example.com',  'user_type' => 'reseller',  'company' => 'Fatima Stores',     'mobile' => '01698765432', 'address' => '78 Mirpur Road, Dhaka 1216'],
        ];

        $customers = [
            ['name' => 'Sajia jahan',     'email' => 'jajia@example.com',    'mobile' => '01718654236', 'company' => null,         'address' => '12 Dhanmondi, Dhaka 1209'],
            ['name' => 'Sumaya Khatun',   'email' => 'Sumaya@example.com',   'mobile' => '01922222222', 'company' => null,         'address' => '45 Mohammadpur, Dhaka 1207'],
        ];

        foreach ($sellers as $data) {
            $user = User::create([
                'role_id' => 3,
                'user_type' => $data['user_type'],
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt('histacin'),
                'status' => 1,
            ]);

            $user->userDetail()->create([
                'mobile' => $data['mobile'],
                'company' => $data['company'],
                'address' => $data['address'],
            ]);
        }

        foreach ($customers as $data) {
            $user = User::create([
                'role_id' => 3,
                'user_type' => 'customer',
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt('histacin'),
                'status' => 1,
            ]);

            $user->userDetail()->create([
                'mobile' => $data['mobile'],
                'company' => $data['company'],
                'address' => $data['address'],
            ]);
        }
    }
}
