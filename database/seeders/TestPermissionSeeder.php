<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;

class TestPermissionSeeder extends Seeder
{
    public function run()
    {
        // list of permissions we may need (idempotent)
        $perms = [
            'categories.read','categories.add','categories.edit','categories.delete',
            'subcategories.read','subcategories.add','subcategories.edit','subcategories.delete',
            'products.read','products.add','products.edit','products.delete',
            'purchases.read','purchases.add','purchases.edit','purchases.delete',
            'customers.read','customers.add','customers.edit','customers.delete',
            'warehouses.read','warehouses.add','warehouses.edit','warehouses.delete',
            'vendors.read','vendors.add','vendors.edit','vendors.delete',
            'sales.read','sales.add','sales.edit','sales.delete',
            'bookings.read','bookings.add','bookings.edit','bookings.delete',
            'assembly.read','assembly.add','assembly.edit','assembly.delete',
            'zones.read','zones.add','zones.edit','zones.delete',
            'brands.read','brands.add','brands.edit','brands.delete',
        ];

        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p]);
        }

        // create role with a subset of permissions
        $role = Role::firstOrCreate(['name' => 'test_role']);

        $assigned = [
            'categories.read','categories.add',
            'subcategories.read',
            'warehouses.read',
            'vendors.read',
            'purchases.read',
        ];

        $role->syncPermissions($assigned);

        // create or find test user
        $email = 'testuser@example.com';
        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = User::create([
                'name' => 'Test User',
                'email' => $email,
                'password' => Hash::make('password'),
            ]);
        }

        // assign role
        $user->assignRole($role->name);

        echo "Seeded test user: {$email} with role test_role\n";
    }
}

